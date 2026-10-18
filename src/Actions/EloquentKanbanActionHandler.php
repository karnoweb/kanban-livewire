<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Actions;

use BackedEnum;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Karnoweb\Kanban\Contracts\ActionHandler;
use Karnoweb\Kanban\Data\{ActionCommand, ActionResult, BoardContext, BoardDefinition, BoardQuery, BulkItemResult};
use Karnoweb\Kanban\Enums\{ActionStatus, ActionType, WipMode};
use Karnoweb\Kanban\Models\{BoardSources, CardIdentity, KanbanModelDefinition};
use Karnoweb\Kanban\Providers\EloquentKanbanDataProvider;
use Throwable;

final class EloquentKanbanActionHandler implements ActionHandler
{
    public function __construct(
        private readonly BoardSources $sources,
        private readonly EloquentKanbanDataProvider $provider,
        private readonly \Karnoweb\Kanban\Contracts\ModelOperationStore $operations,
        private readonly Repository $cache,
    ) {}

    public function execute(ActionCommand $command, BoardContext $context): ActionResult
    {
        $board = $this->sources->board($context);
        if (! $board->settings->model->eloquent['enabled'] || $board->isReadOnly() || ($command->payload['boardId'] ?? $board->board->id) !== $board->board->id) {
            return new ActionResult(ActionStatus::FORBIDDEN, messageCode: 'forbidden');
        }
        if ($this->cache instanceof \Illuminate\Cache\Repository && $this->cache->getStore() instanceof \Illuminate\Cache\ArrayStore && ! app()->runningUnitTests()) {
            return new ActionResult(ActionStatus::DOMAIN_REJECTED, messageCode: 'shared_cache_required');
        }
        $lock = $this->cache->lock('kanban:write:'.hash('sha256', $context->scopeKey), 120);
        try {
            $lock->block(10);
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException) {
            return new ActionResult(ActionStatus::RETRYABLE_ERROR, messageCode: 'operation_busy');
        }
        try {
            $fingerprint = hash('sha256', json_encode([$command->type->value, $command->payload, $command->expectedVersion, $board->sources], JSON_THROW_ON_ERROR));
            $remembered = $this->operations->remembered($context, $command->operationId);
            if ($remembered !== null) {
                if (! hash_equals($remembered['fingerprint'], $fingerprint)) {
                    return new ActionResult(ActionStatus::CONFLICT, messageCode: 'operation_id_reused');
                }

                return $remembered['result'] ?? ActionResult::unknown($command->operationId);
            }
            $this->operations->pending($context, $command->operationId, $fingerprint);
            try {
                $result = $command->type === ActionType::ExecuteBulkAction
                    ? $this->bulk($command, $context, $board)
                    : $this->single($command, $context, $board);
            } catch (ModelActionRejected $rejected) {
                $result = $rejected->result;
            } catch (InvalidArgumentException) {
                $result = new ActionResult(ActionStatus::VALIDATION_ERROR, messageCode: 'invalid_input');
            } catch (\Illuminate\Validation\ValidationException $validation) {
                $result = new ActionResult(ActionStatus::VALIDATION_ERROR, messageCode: 'validation_error',
                    fieldErrors: array_map(fn ($messages) => $messages[0], $validation->errors()));
            } catch (Throwable) {
                // A host afterCommit hook may have failed after persistence. Never retry blindly.
                return ActionResult::unknown($command->operationId);
            }
            $this->operations->finish($context, $command->operationId, $fingerprint, $result);

            return $result;
        } finally {
            $lock->release();
        }
    }

    private function reject(ActionStatus $status, string $code, array $errors = []): never
    {
        throw new ModelActionRejected(new ActionResult($status, messageCode: $code, fieldErrors: $errors));
    }

    private function single(ActionCommand $command, BoardContext $context, BoardDefinition $board, bool $nested = false): ActionResult
    {
        if (in_array($command->type, [ActionType::CreateStage, ActionType::UpdateStage, ActionType::ReorderStage, ActionType::ArchiveStage], true)) {
            return ActionResult::unsupported();
        }
        $create = $command->type === ActionType::CreateCard;
        $id = null;
        if ($create) {
            $source = $command->payload['source'] ?? (count($board->sources ?? []) === 1 ? $board->sources[0] : null);
            if (! is_string($source) || ! in_array($source, $board->sources ?? [], true)) {
                $this->reject(ActionStatus::VALIDATION_ERROR, 'source_required');
            }
        } else {
            try {
                $id = CardIdentity::parse((string) ($command->payload['cardId'] ?? ''));
            } catch (InvalidArgumentException) {
                $this->reject(ActionStatus::NOT_FOUND, 'not_found');
            }
            $source = $id->source;
            if (! in_array($source, $board->sources ?? [], true)) {
                $this->reject(ActionStatus::NOT_FOUND, 'not_found');
            }
        }
        $definition = $this->sources->definitions($board, [$source])[$source];
        $connection = (new $definition->model)->getConnection();
        // This handler must own the commit before its result can be remembered.
        if ($connection->transactionLevel() > 0 && ! $nested) {
            $this->reject(ActionStatus::DOMAIN_REJECTED, 'outer_transaction_not_supported');
        }

        return $connection->transaction(function () use ($command, $context, $board, $definition, $id, $create, $connection) {
            $rebalanced = [];
            $record = $create ? new $definition->model : $this->provider->scoped($definition, $board, $context, in_array($command->type, [ActionType::RestoreCard, ActionType::DeleteCard], true))
                ->where($definition->fieldMap['id'], $id->recordId)->lockForUpdate()->first();
            if ($record === null) {
                $this->reject(ActionStatus::NOT_FOUND, 'not_found');
            }
            $ability = match ($command->type) {
                ActionType::CreateCard => 'create', ActionType::UpdateCard => 'update', ActionType::MoveCard => 'move',
                ActionType::ReorderCard => 'reorder', ActionType::ArchiveCard => 'archive', ActionType::RestoreCard => 'restore', ActionType::DeleteCard => 'delete',
                ActionType::ExecuteCardAction => 'action:'.($command->payload['actionId'] ?? ''),
                default => '',
            };
            if (! $definition->allows($ability, $create ? null : $record, $context)) {
                $this->reject(ActionStatus::FORBIDDEN, 'forbidden');
            }
            $feature = match ($command->type) {
                ActionType::CreateCard => $board->settings->features->createCard,
                ActionType::UpdateCard => $board->settings->features->updateCard,
                ActionType::ArchiveCard => $board->settings->features->archiveCard,
                ActionType::RestoreCard => $board->settings->features->restoreCard,
                ActionType::DeleteCard => $board->settings->features->deleteCard,
                ActionType::ReorderCard => $board->settings->features->reorderCards,
                ActionType::ExecuteCardAction => $board->settings->features->customActions,
                default => true,
            };
            if (! $feature) {
                $this->reject(ActionStatus::FORBIDDEN, 'feature_disabled');
            }
            $versionColumn = $definition->fieldMap['version'] ?? null;
            if (! $create && $versionColumn !== null && ($command->expectedVersion === null || (string) $record->getAttribute($versionColumn) !== $command->expectedVersion)) {
                $this->reject(ActionStatus::CONFLICT, 'version_conflict');
            }
            foreach (['validate', 'before'] as $phase) {
                if (isset($definition->hooks[$phase])) {
                    ($definition->hooks[$phase])($record, $command, $context);
                }
            }
            $nativeStage = $this->scalar($record->getAttribute($definition->fieldMap['stage']));
            $current = $this->sources->mappedStage($board, $definition->source, $nativeStage);
            if (array_key_exists('toGroup', $command->payload)) {
                $group = $command->payload['toGroup'];
                $column = $board->swimlane === null ? null : ($definition->fieldMap[$board->swimlane->field] ?? null);
                if (! $board->swimlane?->allowMove || $column === null || ! is_string($group)
                    || ! $definition->allows('moveGroup', $record, $context) || $definition->groupAuthorization === null
                    || ($definition->groupAuthorization)($record, $group, $context) !== true) {
                    $this->reject(ActionStatus::FORBIDDEN, 'group_move_denied');
                }
                $record->setAttribute($column, $group === '__ungrouped__' ? null : $group);
            }
            if (! $create) {
                $origin = $definition->stage($nativeStage, $board->board->id);
                if ($origin->readOnly || $origin->disabled) {
                    $this->reject(ActionStatus::FORBIDDEN, 'stage_read_only');
                }
            }
            if ($command->type === ActionType::DeleteCard) {
                $id = (new CardIdentity($definition->source, (string) $record->getAttribute($definition->fieldMap['id'])))->encode();
                $newVersion = $versionColumn === null ? null : (string) (((int) $record->getAttribute($versionColumn)) + 1);
                if ($record->delete() === false) {
                    $this->reject(ActionStatus::DOMAIN_REJECTED, 'delete_rejected');
                }
                if (isset($definition->hooks['after'])) {
                    ($definition->hooks['after'])($record, $command, $context);
                }
                $connection->afterCommit(fn () => app(\Karnoweb\Kanban\Support\ModelQueryCache::class)->invalidate($context));
                $connection->afterCommit(fn () => event(new \Karnoweb\Kanban\Events\KanbanModelChanged(
                    $context, $definition->source, $id, $command->operationId, $command->type->value, $newVersion,
                )));
                if (isset($definition->hooks['afterCommit'])) {
                    $connection->afterCommit(fn () => ($definition->hooks['afterCommit'])($record, $command, $context));
                }

                return ActionResult::success([$id], ['board'], $newVersion);
            }
            if ($create || in_array($command->type, [ActionType::MoveCard, ActionType::ReorderCard, ActionType::RestoreCard], true)) {
                $target = (string) ($command->payload['toStageId'] ?? $command->payload['stageId'] ?? $current);
                $native = $this->sources->nativeStages($board, $definition, $target);
                if (count($native) !== 1) {
                    $this->reject(ActionStatus::VALIDATION_ERROR, 'ambiguous_or_unknown_stage');
                }
                $stage = $definition->stage($native[0], $board->board->id);
                if (! $stage->visible || $stage->readOnly || $stage->disabled) {
                    $this->reject(ActionStatus::FORBIDDEN, 'stage_read_only');
                }
                if (! $create && in_array($command->type, [ActionType::MoveCard, ActionType::ReorderCard], true)) {
                    if (($command->payload['fromStageId'] ?? null) !== $current) {
                        $this->reject(ActionStatus::CONFLICT, 'stage_conflict');
                    }
                    if ($current !== $target) {
                        $allowed = $definition->transitionMap[$nativeStage] ?? $definition->stageDefinitions[$nativeStage]['transitions'] ?? [];
                        $allowed = array_map(fn ($v) => $v instanceof BackedEnum ? (string) $v->value : (string) $v, $allowed);
                        if (! in_array($native[0], $allowed, true) || ! app(TransitionPolicy::class)->allows($board->settings->interaction, $current, $target)) {
                            $this->reject(ActionStatus::FORBIDDEN, 'transition_denied');
                        }
                        $confirmation = app(ConfirmationResolver::class)->forTransition($board->settings->interaction, $current, $target);
                        $form = $command->payload['formData'] ?? [];
                        foreach ($confirmation['fields'] as $name => $field) {
                            if (($field['required'] ?? false) && trim((string) ($form[$name] ?? '')) === '') {
                                $this->reject(ActionStatus::VALIDATION_ERROR, 'confirmation_required', [$name => 'required']);
                            }
                        }
                    }
                }
                $capacity = $stage;
                foreach ($this->provider->stages($context, new BoardQuery) as $sharedStage) {
                    if ($sharedStage->id === $target) {
                        $capacity = $sharedStage;
                        break;
                    }
                }
                if (($create || $current !== $target || $command->type === ActionType::RestoreCard) && $board->settings->features->wip && $capacity->wipMode === WipMode::Strict && $capacity->wipLimit !== null) {
                    $counts = $this->provider->counts($context, new BoardQuery, false);
                    if (($counts->forStage($target)?->totalCount ?? 0) >= $capacity->wipLimit) {
                        $this->reject(ActionStatus::WIP_LIMIT, 'wip_limit');
                    }
                }
                $record->setAttribute($definition->fieldMap['stage'], $native[0]);
                $strategy = $this->sources->rankStrategy($board);
                $manualSupported = $strategy->supports($board, $this->sources->definitions($board));
                if ($command->type === ActionType::ReorderCard || ($manualSupported && (($command->payload['beforeCardId'] ?? null) !== null || ($command->payload['afterCardId'] ?? null) !== null))) {
                    $rebalanced = $strategy->assign($record, $definition, $board, $context, $command, $native[0]);
                } elseif (isset($definition->fieldMap['rank']) && ($create || $current !== $target)) {
                    $rank = $definition->fieldMap['rank'];
                    $max = $this->provider->scoped($definition, $board, $context)->where($definition->fieldMap['stage'], $native[0])->max($rank);
                    $record->setAttribute($rank, ((int) $max) + 1024);
                }
            }
            if ($create || $command->type === ActionType::UpdateCard) {
                $fields = array_intersect_key($command->payload, array_flip(['title', 'description', 'priority', 'dueAt', 'assignee']));
                $rules = $definition->validationRules;
                if (! $create) {
                    foreach ($rules as &$rule) {
                        $rule = array_merge(['sometimes'], is_array($rule) ? $rule : explode('|', $rule));
                    }
                    unset($rule);
                }
                $validator = Validator::make($fields, $rules);
                if ($validator->fails()) {
                    $this->reject(ActionStatus::VALIDATION_ERROR, 'validation_error', array_map(fn ($messages) => $messages[0], $validator->errors()->messages()));
                }
                foreach ($fields as $field => $value) {
                    if (isset($definition->fieldMap[$field])) {
                        $record->setAttribute($definition->fieldMap[$field], $value);
                    }
                }
            }
            if (in_array($command->type, [ActionType::ArchiveCard, ActionType::RestoreCard], true)) {
                $column = $definition->fieldMap['archivedAt'] ?? null;
                if ($column === null) {
                    $this->reject(ActionStatus::UNSUPPORTED, 'archive_storage_required');
                }
                $record->setAttribute($column, $command->type === ActionType::ArchiveCard ? now() : null);
            }
            if ($command->type === ActionType::ExecuteCardAction) {
                $callback = $definition->customActions[$command->payload['actionId'] ?? ''] ?? null;
                if ($callback === null) {
                    $this->reject(ActionStatus::UNSUPPORTED, 'unsupported');
                }
                $validator = Validator::make($command->payload['formData'] ?? [], $definition->actionRules[$command->payload['actionId']] ?? []);
                if ($validator->fails()) {
                    $this->reject(ActionStatus::VALIDATION_ERROR, 'validation_error', array_map(fn ($messages) => $messages[0], $validator->errors()->messages()));
                }
                $custom = $callback($record, $command, $context);
                if ($custom instanceof ActionResult && $custom->status !== ActionStatus::SUCCESS) {
                    throw new ModelActionRejected($custom);
                }
            }
            if ($versionColumn !== null) {
                $record->setAttribute($versionColumn, ((int) $record->getAttribute($versionColumn)) + 1);
            }
            $record->save();
            // Creation hooks must assign ownership so the saved row remains in the visibility scope.
            if (! $this->provider->scoped($definition, $board, $context, $command->type === ActionType::ArchiveCard)->where($definition->fieldMap['id'], $record->getAttribute($definition->fieldMap['id']))->exists()) {
                $this->reject(ActionStatus::FORBIDDEN, 'visibility_changed');
            }
            if (isset($definition->hooks['after'])) {
                ($definition->hooks['after'])($record, $command, $context);
            }
            $id = (new CardIdentity($definition->source, (string) $record->getAttribute($definition->fieldMap['id'])))->encode();
            $newVersion = $versionColumn === null ? null : (string) $record->getAttribute($versionColumn);
            $connection->afterCommit(fn () => app(\Karnoweb\Kanban\Support\ModelQueryCache::class)->invalidate($context));
            $connection->afterCommit(fn () => event(new \Karnoweb\Kanban\Events\KanbanModelChanged(
                $context, $definition->source, $id, $command->operationId, $command->type->value, $newVersion,
            )));
            if (isset($definition->hooks['afterCommit'])) {
                $connection->afterCommit(fn () => ($definition->hooks['afterCommit'])($record, $command, $context));
            }

            return ActionResult::success(array_merge([$id], $rebalanced), ['board'], $newVersion);
        });
    }

    private function scalar(mixed $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : (string) $value;
    }

    private function bulk(ActionCommand $command, BoardContext $context, BoardDefinition $board): ActionResult
    {
        if (! $board->settings->features->bulk) {
            $this->reject(ActionStatus::FORBIDDEN, 'feature_disabled');
        }
        $mode = $command->payload['mode'] ?? 'partial';
        if (! in_array($mode, ['partial', 'all-or-nothing'], true)) {
            $this->reject(ActionStatus::VALIDATION_ERROR, 'invalid_bulk_mode');
        }
        $ids = array_values(array_unique(array_filter(explode(',', (string) ($command->payload['ids'] ?? '')))));
        if ($ids === [] || count($ids) > $board->settings->security->bulkIds) {
            $this->reject(ActionStatus::VALIDATION_ERROR, 'bulk_size_invalid');
        }
        $type = match ($command->payload['action'] ?? '') {
            'archive' => ActionType::ArchiveCard, 'restore' => ActionType::RestoreCard, default => null,
        };
        if ($type === null) {
            return ActionResult::unsupported();
        }
        $versions = [];
        foreach (explode(',', (string) ($command->payload['expectedVersions'] ?? '')) as $encoded) {
            $separator = strrpos($encoded, ':');
            if ($separator !== false) {
                $versions[substr($encoded, 0, $separator)] = substr($encoded, $separator + 1);
            }
        }
        $run = function (bool $atomic) use ($ids, $versions, $command, $type, $context, $board): ActionResult {
            $items = []; $affected = [];
            foreach ($ids as $id) {
                try {
                    $identity = CardIdentity::parse($id);
                    if (! in_array($identity->source, $board->sources ?? [], true)) {
                        $this->reject(ActionStatus::NOT_FOUND, 'not_found');
                    }
                    $definition = $this->sources->definitions($board, [$identity->source])[$identity->source];
                    $record = $this->provider->scoped($definition, $board, $context, $type === ActionType::RestoreCard)
                        ->where($definition->fieldMap['id'], $identity->recordId)->first();
                    if ($record === null) {
                        $this->reject(ActionStatus::NOT_FOUND, 'not_found');
                    }
                    if (! $definition->allows('bulk', $record, $context)) {
                        $this->reject(ActionStatus::FORBIDDEN, 'forbidden');
                    }
                    $result = $this->single(new ActionCommand($command->operationId, $type, ['cardId' => $id], $versions[$id] ?? null), $context, $board, $atomic);
                } catch (ModelActionRejected $rejected) {
                    if ($atomic) {
                        throw $rejected;
                    }
                    $result = $rejected->result;
                } catch (InvalidArgumentException) {
                    if ($atomic) {
                        $this->reject(ActionStatus::NOT_FOUND, 'not_found');
                    }
                    $result = new ActionResult(ActionStatus::NOT_FOUND, messageCode: 'not_found');
                }
                $items[] = new BulkItemResult($id, $result->status, $result->messageCode);
                $affected = array_merge($affected, $result->affectedIds);
            }

            return new ActionResult(ActionStatus::SUCCESS, affectedIds: $affected, refreshHints: ['board'], itemResults: $items,
                successCount: count($affected), failureCount: count($ids) - count($affected));
        };
        if ($mode === 'partial') {
            return $run(false);
        }
        $connections = [];
        foreach ($ids as $id) {
            $identity = CardIdentity::parse($id);
            if (! in_array($identity->source, $board->sources ?? [], true)) {
                $this->reject(ActionStatus::NOT_FOUND, 'not_found');
            }
            $definition = $this->sources->definitions($board, [$identity->source])[$identity->source];
            $connection = (new $definition->model)->getConnection();
            $connections[spl_object_id($connection)] = $connection;
        }
        if (count($connections) !== 1) {
            $this->reject(ActionStatus::UNSUPPORTED, 'cross_database_atomic_bulk_requires_host_handler');
        }
        $connection = reset($connections);
        if ($connection->transactionLevel() > 0) {
            $this->reject(ActionStatus::DOMAIN_REJECTED, 'outer_transaction_not_supported');
        }

        return $connection->transaction(fn () => $run(true));
    }
}

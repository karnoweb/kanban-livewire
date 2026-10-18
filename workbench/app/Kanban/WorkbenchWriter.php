<?php

declare(strict_types=1);

namespace Workbench\App\Kanban;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Date;
use Karnoweb\Kanban\Contracts\ActionHandler;
use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\ActionResult;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BulkItemResult;
use Karnoweb\Kanban\Enums\ActionStatus;
use Karnoweb\Kanban\Enums\ActionType;
use Karnoweb\Kanban\Enums\WipMode;
use Karnoweb\Kanban\Realtime\RealtimePublisher;
use Workbench\App\Models\DemoCard;
use Workbench\App\Models\DemoOperation;
use Workbench\App\Models\DemoStage;

/**
 * Database-backed host handler. Every command runs in one transaction that
 * starts with a write lock (BEGIN IMMEDIATE on SQLite, row locks elsewhere),
 * checks the stored operation first (idempotency), and publishes realtime
 * events after commit.
 */
class WorkbenchWriter implements ActionHandler
{
    private function publisher(): ?RealtimePublisher
    {
        $container = Container::getInstance();

        return $container->bound(Dispatcher::class) || $container->bound('events')
            ? $container->make(RealtimePublisher::class)
            : null;
    }

    public function execute(ActionCommand $command, BoardContext $context): ActionResult
    {
        /** @var Connection $connection */
        $connection = DemoCard::resolveConnection();

        return $connection->transaction(function () use ($command, $context): ActionResult {
            // First statement is a write: it takes the lock before any read,
            // so concurrent writers wait (busy_timeout) instead of racing.
            // Also serialises SQLite when BEGIN IMMEDIATE is unavailable (PHP < 8.4).
            DemoStage::query()
                ->where('scope_key', $context->scopeKey)
                ->where('board_key', $context->boardKey)
                ->update(['updated_at' => Date::now()]);

            $stored = DemoOperation::query()
                ->where('scope_key', $context->scopeKey)
                ->where('operation_id', $command->operationId)
                ->first();

            if ($stored instanceof DemoOperation) {
                $data = json_decode((string) $stored->result, true) ?: [];

                return new ActionResult(
                    ActionStatus::from((string) $stored->status),
                    messageCode: $data['messageCode'] ?? null,
                    affectedIds: $data['affectedIds'] ?? [],
                    newVersion: $data['newVersion'] ?? null,
                    successCount: (int) ($data['successCount'] ?? 0),
                    failureCount: (int) ($data['failureCount'] ?? 0),
                );
            }

            if ($context->boardKey === 'crm.deals' && in_array($command->type, [ActionType::CreateCard, ActionType::CreateStage], true)) {
                return new ActionResult(ActionStatus::VALIDATION_ERROR, messageCode: 'validation_failed');
            }

            $result = match ($command->type) {
                ActionType::MoveCard, ActionType::ReorderCard => $this->move($command, $context),
                ActionType::CreateCard => $this->createCard($command, $context),
                ActionType::UpdateCard => $this->updateCard($command, $context),
                ActionType::ArchiveCard => $this->archiveCard($command, $context, true),
                ActionType::DeleteCard => $this->deleteCard($command, $context),
                ActionType::RestoreCard => $this->archiveCard($command, $context, false),
                ActionType::CreateStage => $this->createStage($command, $context),
                ActionType::UpdateStage => $this->updateStage($command, $context),
                ActionType::ReorderStage => $this->reorderStages($command, $context),
                ActionType::ArchiveStage => $this->archiveStage($command, $context),
                ActionType::ExecuteCardAction => $this->customAction($command, $context),
                ActionType::ExecuteBulkAction => $this->bulk($command, $context),
            };

            DemoOperation::query()->create([
                'scope_key' => $context->scopeKey,
                'operation_id' => $command->operationId,
                'status' => $result->status->value,
                'result' => json_encode([
                    'messageCode' => $result->messageCode,
                    'affectedIds' => $result->affectedIds,
                    'newVersion' => $result->newVersion,
                    'successCount' => $result->successCount,
                    'failureCount' => $result->failureCount,
                ], JSON_THROW_ON_ERROR),
            ]);

            return $result;
        });
    }

    private function card(BoardContext $context, string $id): ?DemoCard
    {
        $card = DemoCard::query()
            ->where('scope_key', $context->scopeKey)
            ->where('board_key', $context->boardKey)
            ->where('public_id', $id)
            ->first();

        return $card instanceof DemoCard ? $card : null;
    }

    private function stage(BoardContext $context, string $id): ?DemoStage
    {
        $stage = DemoStage::query()
            ->where('scope_key', $context->scopeKey)
            ->where('board_key', $context->boardKey)
            ->where('public_id', $id)
            ->first();

        return $stage instanceof DemoStage ? $stage : null;
    }

    private function activeCount(BoardContext $context, string $stageId): int
    {
        return DemoCard::query()
            ->where('scope_key', $context->scopeKey)
            ->where('board_key', $context->boardKey)
            ->where('stage_id', $stageId)
            ->where('archived', false)
            ->count();
    }

    private function wipFull(BoardContext $context, DemoStage $stage): bool
    {
        return $stage->wip_limit !== null
            && WipMode::tryFrom((string) $stage->wip_mode) === WipMode::Strict
            && $this->activeCount($context, (string) $stage->public_id) >= (int) $stage->wip_limit;
    }

    private function notFound(): ActionResult
    {
        return new ActionResult(ActionStatus::NOT_FOUND, messageCode: 'not_found');
    }

    private function wip(): ActionResult
    {
        return new ActionResult(ActionStatus::WIP_LIMIT, messageCode: 'wip_limit');
    }

    private function publish(BoardContext $context, string $type, ?string $entity = null, ?string $version = null, ?string $operationId = null): void
    {
        $this->publisher()?->publish($context, $type, $entity, $version, $operationId);
    }

    private function orderFor(BoardContext $context, string $stageId, ?string $beforeId, ?string $afterId, string $movingId): float
    {
        $siblings = DemoCard::query()
            ->where('scope_key', $context->scopeKey)
            ->where('board_key', $context->boardKey)
            ->where('stage_id', $stageId)
            ->where('archived', false)
            ->where('public_id', '!=', $movingId)
            ->orderBy('manual_order')
            ->orderBy('id')
            ->get();

        $orders = $siblings->pluck('manual_order')->map(static fn ($value): float => (float) $value)->values()->all();
        $ids = $siblings->pluck('public_id')->map(static fn ($value): string => (string) $value)->values()->all();

        if ($beforeId !== null && ($index = array_search($beforeId, $ids, true)) !== false) {
            $current = $orders[$index];

            return $index === 0 ? $current - 1.0 : ($orders[$index - 1] + $current) / 2;
        }

        if ($afterId !== null && ($index = array_search($afterId, $ids, true)) !== false) {
            $current = $orders[$index];

            return $index === count($orders) - 1 ? $current + 1.0 : ($current + $orders[$index + 1]) / 2;
        }

        return $orders === [] ? 1.0 : max($orders) + 1.0;
    }

    private function move(ActionCommand $command, BoardContext $context): ActionResult
    {
        $card = $this->card($context, (string) ($command->payload['cardId'] ?? ''));

        if ($card === null) {
            return $this->notFound();
        }

        if ($command->expectedVersion !== null && (string) $card->version !== $command->expectedVersion) {
            return new ActionResult(ActionStatus::CONFLICT, messageCode: 'conflict');
        }

        $from = (string) $card->stage_id;
        $to = (string) ($command->payload['toStageId'] ?? '');
        $target = $this->stage($context, $to);

        if ($target === null || (bool) $target->archived) {
            return $this->notFound();
        }

        if ($from !== $to && $this->wipFull($context, $target)) {
            return $this->wip();
        }

        if ($context->boardKey === 'crm.deals' && $from !== $to) {
            $allowed = ['new' => 'progress', 'progress' => 'negotiation', 'negotiation' => 'closed'];
            $transitionNote = $command->payload['formData']['note'] ?? null;
            if (($allowed[$from] ?? null) !== $to || ! is_string($transitionNote) || trim($transitionNote) === '' || strlen($transitionNote) > 5000) {
                return new ActionResult(ActionStatus::VALIDATION_ERROR, messageCode: 'validation_failed');
            }
        }

        $before = $command->payload['beforeCardId'] ?? null;
        $after = $command->payload['afterCardId'] ?? null;
        $card->manual_order = $this->orderFor(
            $context,
            $to,
            is_string($before) ? $before : null,
            is_string($after) ? $after : null,
            (string) $card->public_id,
        );

        $note = $command->payload['formData']['note'] ?? null;

        if (is_string($note) && $note !== '' && strlen($note) <= 5000) {
            $metadata = is_array($card->metadata) ? $card->metadata : [];
            $metadata['note'] = $note;
            $comments = is_array($metadata['comments'] ?? null) ? $metadata['comments'] : [];
            $comments[] = ['id' => $command->operationId, 'body' => $note, 'author' => $context->actorId, 'createdAt' => Date::now()->toIso8601String()];
            $comments = array_slice($comments, -100);
            while (count($comments) > 1 && strlen(json_encode([$metadata['checklist'] ?? [], $comments], JSON_THROW_ON_ERROR)) > 28000) {
                array_shift($comments);
            }
            $metadata['comments'] = $comments;
            $metadata['commentsCount'] = count($metadata['comments']);
            $card->metadata = $metadata;
        }

        $card->stage_id = $to;
        $card->version = (string) (((int) $card->version) + 1);
        $card->save();

        $this->publish($context, $from === $to ? 'CardReordered' : 'CardMoved', (string) $card->public_id, (string) $card->version, $command->operationId);

        return ActionResult::success([(string) $card->public_id], ['stage:'.$from, 'stage:'.$to, 'counts'], (string) $card->version);
    }

    private function createCard(ActionCommand $command, BoardContext $context): ActionResult
    {
        $title = trim((string) ($command->payload['title'] ?? ''));

        if ($title === '') {
            return new ActionResult(ActionStatus::VALIDATION_ERROR, messageCode: 'validation_error', fieldErrors: ['title' => 'required']);
        }

        $stage = $this->stage($context, (string) ($command->payload['stageId'] ?? ''));

        if ($stage === null) {
            return new ActionResult(ActionStatus::VALIDATION_ERROR, messageCode: 'validation_error', fieldErrors: ['stageId' => 'required']);
        }

        if ($this->wipFull($context, $stage)) {
            return $this->wip();
        }

        $id = 'card-'.bin2hex(random_bytes(6));
        $order = $this->orderFor($context, (string) $stage->public_id, null, null, $id);

        DemoCard::query()->create([
            'board_key' => $context->boardKey,
            'scope_key' => $context->scopeKey,
            'public_id' => $id,
            'stage_id' => (string) $stage->public_id,
            'title' => $title,
            'description' => $command->payload['description'] ?? null,
            'priority' => $command->payload['priority'] ?? null,
            'due_at' => $command->payload['dueAt'] ?? null,
            'version' => '1',
            'manual_order' => $order,
        ]);

        $this->publish($context, 'CardCreated', $id, '1', $command->operationId);

        return ActionResult::success([$id], ['stage:'.$stage->public_id, 'counts'], '1');
    }

    private function updateCard(ActionCommand $command, BoardContext $context): ActionResult
    {
        $card = $this->card($context, (string) ($command->payload['cardId'] ?? ''));

        if ($card === null) {
            return $this->notFound();
        }

        if ($command->expectedVersion !== null && (string) $card->version !== $command->expectedVersion) {
            return new ActionResult(ActionStatus::CONFLICT, messageCode: 'conflict');
        }

        if (array_key_exists('title', $command->payload) && trim((string) $command->payload['title']) === '') {
            return new ActionResult(ActionStatus::VALIDATION_ERROR, messageCode: 'validation_error', fieldErrors: ['title' => 'required']);
        }

        foreach (['title' => 'title', 'description' => 'description', 'priority' => 'priority', 'dueAt' => 'due_at'] as $field => $column) {
            if (array_key_exists($field, $command->payload)) {
                $card->{$column} = $command->payload[$field];
            }
        }

        $card->version = (string) (((int) $card->version) + 1);
        $card->save();
        $this->publish($context, 'CardUpdated', (string) $card->public_id, (string) $card->version, $command->operationId);

        return ActionResult::success([(string) $card->public_id], ['card:'.$card->public_id], (string) $card->version);
    }

    private function archiveCard(ActionCommand $command, BoardContext $context, bool $archive): ActionResult
    {
        $card = $this->card($context, (string) ($command->payload['cardId'] ?? ''));

        if ($card === null) {
            return $this->notFound();
        }

        if (! $archive) {
            $stageId = $command->payload['stageId'] ?? null;
            $target = $this->stage($context, is_string($stageId) && $stageId !== '' ? $stageId : (string) $card->stage_id);

            if ($target === null || (bool) $target->archived) {
                return new ActionResult(ActionStatus::VALIDATION_ERROR, messageCode: 'validation_error', fieldErrors: ['stageId' => 'required']);
            }

            if ($this->wipFull($context, $target)) {
                return $this->wip();
            }

            $card->stage_id = (string) $target->public_id;
        }

        $card->archived = $archive;
        $card->version = (string) (((int) $card->version) + 1);
        $card->save();
        $this->publish($context, $archive ? 'CardArchived' : 'CardRestored', (string) $card->public_id, (string) $card->version, $command->operationId);

        return ActionResult::success([(string) $card->public_id], ['counts', 'card:'.$card->public_id], (string) $card->version);
    }

    private function deleteCard(ActionCommand $command, BoardContext $context): ActionResult
    {
        $definition = app(\Karnoweb\Kanban\Support\BoardRegistry::class)->resolve($context->boardKey, $context);
        if (! $definition->settings->features->deleteCard) {
            return new ActionResult(ActionStatus::FORBIDDEN, messageCode: 'feature_disabled');
        }
        $card = $this->card($context, (string) ($command->payload['cardId'] ?? ''));
        if ($card === null || ! $card->archived) {
            return $this->notFound();
        }
        if ($command->expectedVersion === null || (string) $card->version !== $command->expectedVersion) {
            return new ActionResult(ActionStatus::CONFLICT, messageCode: 'conflict');
        }
        $id = (string) $card->public_id;
        $version = (string) (((int) $card->version) + 1);
        $card->delete();
        $this->publish($context, 'BoardInvalidated', null, null, $command->operationId);

        return ActionResult::success([$id], ['board'], $version);
    }

    private function createStage(ActionCommand $command, BoardContext $context): ActionResult
    {
        $title = trim((string) ($command->payload['title'] ?? ''));

        if ($title === '') {
            return new ActionResult(ActionStatus::VALIDATION_ERROR, messageCode: 'validation_error', fieldErrors: ['title' => 'required']);
        }

        $id = 'stage-'.bin2hex(random_bytes(4));
        $order = (int) DemoStage::query()->where('scope_key', $context->scopeKey)->where('board_key', $context->boardKey)->max('sort_order') + 1;
        $color = $command->payload['color'] ?? '#336699';

        DemoStage::query()->create([
            'board_key' => $context->boardKey,
            'scope_key' => $context->scopeKey,
            'public_id' => $id,
            'title' => $title,
            'sort_order' => $order,
            'color' => is_string($color) ? $color : '#336699',
        ]);
        $this->publish($context, 'StageCreated', $id, null, $command->operationId);

        return ActionResult::success([$id], ['board']);
    }

    private function updateStage(ActionCommand $command, BoardContext $context): ActionResult
    {
        $stage = $this->stage($context, (string) ($command->payload['stageId'] ?? ''));

        if ($stage === null) {
            return $this->notFound();
        }

        if (isset($command->payload['title'])) {
            $stage->title = (string) $command->payload['title'];
        }

        if (isset($command->payload['color']) && is_string($command->payload['color'])) {
            $stage->color = $command->payload['color'];
        }

        $stage->save();
        $this->publish($context, 'StageUpdated', (string) $stage->public_id, null, $command->operationId);

        return ActionResult::success([(string) $stage->public_id], ['board']);
    }

    private function reorderStages(ActionCommand $command, BoardContext $context): ActionResult
    {
        $ids = array_values(array_filter(explode(',', (string) ($command->payload['stageIds'] ?? ''))));

        foreach ($ids as $order => $id) {
            $stage = $this->stage($context, $id);

            if ($stage !== null) {
                $stage->sort_order = $order;
                $stage->save();
            }
        }

        $this->publish($context, 'StageReordered', null, null, $command->operationId);

        return ActionResult::success($ids, ['board']);
    }

    private function archiveStage(ActionCommand $command, BoardContext $context): ActionResult
    {
        $stage = $this->stage($context, (string) ($command->payload['stageId'] ?? ''));

        if ($stage === null) {
            return $this->notFound();
        }

        if ($this->activeCount($context, (string) $stage->public_id) > 0) {
            return new ActionResult(ActionStatus::DOMAIN_REJECTED, messageCode: 'stage_not_empty');
        }

        $stage->archived = true;
        $stage->save();
        $this->publish($context, 'StageArchived', (string) $stage->public_id, null, $command->operationId);

        return ActionResult::success([(string) $stage->public_id], ['board']);
    }

    private function customAction(ActionCommand $command, BoardContext $context): ActionResult
    {
        $card = $this->card($context, (string) ($command->payload['cardId'] ?? ''));

        if ($card === null) {
            return $this->notFound();
        }

        $metadata = is_array($card->metadata) ? $card->metadata : [];
        $metadata['lastAction'] = (string) ($command->payload['actionId'] ?? '');
        $card->metadata = $metadata;
        $card->version = (string) (((int) $card->version) + 1);
        $card->save();
        $this->publish($context, 'CardUpdated', (string) $card->public_id, (string) $card->version, $command->operationId);

        return ActionResult::success([(string) $card->public_id], ['card:'.$card->public_id], (string) $card->version);
    }

    private function bulk(ActionCommand $command, BoardContext $context): ActionResult
    {
        $ids = array_values(array_filter(explode(',', (string) ($command->payload['ids'] ?? ''))));
        $atomic = (string) ($command->payload['mode'] ?? '') === 'all-or-nothing';
        $items = [];
        $missing = [];

        foreach ($ids as $id) {
            if ($this->card($context, $id) === null) {
                $missing[] = $id;
                $items[] = new BulkItemResult($id, ActionStatus::NOT_FOUND, 'not_found');
            } else {
                $items[] = new BulkItemResult($id, ActionStatus::SUCCESS);
            }
        }

        if ($atomic && $missing !== []) {
            return new ActionResult(
                ActionStatus::DOMAIN_REJECTED,
                messageCode: 'bulk_rejected',
                itemResults: array_map(static fn (string $id): BulkItemResult => new BulkItemResult($id, ActionStatus::DOMAIN_REJECTED, 'rolled_back'), $ids),
                successCount: 0,
                failureCount: count($ids),
            );
        }

        $done = [];

        foreach ($ids as $id) {
            $card = $this->card($context, $id);

            if ($card === null) {
                continue;
            }

            $card->archived = true;
            $card->version = (string) (((int) $card->version) + 1);
            $card->save();
            $done[] = $id;
        }

        $this->publish($context, 'BoardInvalidated', null, null, $command->operationId);

        return new ActionResult(
            $missing === [] ? ActionStatus::SUCCESS : ActionStatus::DOMAIN_REJECTED,
            messageCode: 'bulk_completed',
            affectedIds: $done,
            refreshHints: ['counts'],
            itemResults: $items,
            successCount: count($done),
            failureCount: count($missing),
        );
    }
}

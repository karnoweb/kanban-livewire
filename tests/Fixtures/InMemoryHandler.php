<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Fixtures;

use Karnoweb\Kanban\Contracts\ActionHandler;
use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\ActionResult;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BulkItemResult;
use Karnoweb\Kanban\Enums\ActionStatus;
use Karnoweb\Kanban\Enums\ActionType;
use Karnoweb\Kanban\Enums\WipMode;

final class InMemoryHandler implements ActionHandler
{
    public function execute(ActionCommand $command, BoardContext $context): ActionResult
    {
        $operationKey = $context->scopeKey.'|'.$command->operationId;

        if (isset(MemoryStore::$operations[$operationKey])) {
            return $this->restore($operationKey);
        }

        MemoryStore::$calls[] = [
            'type' => $command->type->value,
            'actor' => $context->actorId,
            'scope' => $context->scopeKey,
            'board' => $context->boardKey,
            'payload' => $command->payload,
        ];

        $board = MemoryStore::board($context->scopeKey, $context->boardKey);

        if (in_array($context->actorId, $board['denyActors'], true)) {
            return $this->store($operationKey, new ActionResult(ActionStatus::FORBIDDEN, messageCode: 'forbidden'));
        }

        $result = match ($command->type) {
            ActionType::MoveCard, ActionType::ReorderCard => $this->move($board, $command),
            ActionType::CreateCard => $this->createCard($board, $command),
            ActionType::UpdateCard => $this->updateCard($board, $command),
            ActionType::ArchiveCard => $this->archiveCard($board, $command, true),
            ActionType::RestoreCard => $this->archiveCard($board, $command, false),
            ActionType::CreateStage => $this->createStage($board, $command),
            ActionType::UpdateStage => $this->updateStage($board, $command),
            ActionType::ReorderStage => $this->reorderStages($board, $command),
            ActionType::ArchiveStage => $this->archiveStage($board, $command),
            ActionType::ExecuteCardAction => $this->custom($board, $command),
            ActionType::ExecuteBulkAction => $this->bulk($board, $command),
        };

        MemoryStore::put($context->scopeKey, $context->boardKey, $board);

        if (MemoryStore::$returnUnknown) {
            $this->store($operationKey, new ActionResult(ActionStatus::SUCCESS, affectedIds: $result->affectedIds, newVersion: $result->newVersion));

            return new ActionResult(ActionStatus::UNKNOWN_OUTCOME, messageCode: 'unknown_outcome');
        }

        return $this->store($operationKey, $result);
    }

    /** @param array<string, mixed> $board */
    private function move(array &$board, ActionCommand $command): ActionResult
    {
        $cardId = (string) $command->payload['cardId'];
        $card = $board['cards'][$cardId] ?? null;

        if (! is_array($card)) {
            return new ActionResult(ActionStatus::NOT_FOUND, messageCode: 'not_found');
        }

        if ($command->expectedVersion !== null && (string) $card['version'] !== $command->expectedVersion) {
            return new ActionResult(ActionStatus::CONFLICT, messageCode: 'conflict');
        }

        $to = (string) $command->payload['toStageId'];
        $from = (string) $command->payload['fromStageId'];
        $stage = $board['stages'][$to] ?? null;

        if (! is_array($stage)) {
            return new ActionResult(ActionStatus::NOT_FOUND, messageCode: 'not_found');
        }

        if ($from !== $to && ($stage['wipLimit'] ?? null) !== null && MemoryStore::wipMode($stage) === WipMode::Strict) {
            $count = count(array_filter($board['cards'], static fn (array $item): bool => ! $item['archived'] && $item['stageId'] === $to));

            if ($count >= (int) $stage['wipLimit']) {
                return new ActionResult(ActionStatus::WIP_LIMIT, messageCode: 'wip_limit');
            }
        }

        $note = $command->payload['formData']['note'] ?? null;

        if (is_string($note) && $note !== '') {
            $card['metadata']['note'] = $note;
        }

        $card['stageId'] = $to;
        $card['version'] = (string) (((int) $card['version']) + 1);

        if ($command->type === ActionType::ReorderCard) {
            $before = $command->payload['beforeCardId'] ?? null;
            $card['manualOrder'] = is_string($before) ? '0'.$before : 'z'.$card['id'];
        }

        $board['cards'][$cardId] = $card;

        return ActionResult::success([$cardId], ['stage:'.$from, 'stage:'.$to, 'card:'.$cardId, 'counts'], $card['version']);
    }

    /** @param array<string, mixed> $board */
    private function createCard(array &$board, ActionCommand $command): ActionResult
    {
        $title = trim((string) ($command->payload['title'] ?? ''));

        if ($title === '') {
            return new ActionResult(ActionStatus::VALIDATION_ERROR, messageCode: 'validation_error', fieldErrors: ['title' => 'required']);
        }

        $stageId = (string) ($command->payload['stageId'] ?? '');
        $stage = $board['stages'][$stageId] ?? null;

        if (! is_array($stage)) {
            return new ActionResult(ActionStatus::VALIDATION_ERROR, messageCode: 'validation_error', fieldErrors: ['stageId' => 'required']);
        }

        if (($stage['wipLimit'] ?? null) !== null && MemoryStore::wipMode($stage) === WipMode::Strict) {
            $count = count(array_filter($board['cards'], static fn (array $item): bool => ! $item['archived'] && $item['stageId'] === $stageId));

            if ($count >= (int) $stage['wipLimit']) {
                return new ActionResult(ActionStatus::WIP_LIMIT, messageCode: 'wip_limit');
            }
        }

        $id = 'card-'.(count($board['cards']) + 1);
        $board['cards'][$id] = [
            'id' => $id,
            'stageId' => $stageId,
            'title' => $title,
            'description' => $command->payload['description'] ?? null,
            'priority' => $command->payload['priority'] ?? null,
            'assignees' => [],
            'badges' => [],
            'attachments' => [],
            'actions' => [],
            'metadata' => [],
            'dueAt' => $command->payload['dueAt'] ?? null,
            'createdAt' => gmdate('c'),
            'version' => '1',
            'manualOrder' => $id,
            'archived' => false,
            'permissions' => [],
        ];

        return ActionResult::success([$id], ['stage:'.$stageId, 'counts'], '1');
    }

    /** @param array<string, mixed> $board */
    private function updateCard(array &$board, ActionCommand $command): ActionResult
    {
        $id = (string) $command->payload['cardId'];

        if (! isset($board['cards'][$id])) {
            return new ActionResult(ActionStatus::NOT_FOUND, messageCode: 'not_found');
        }

        foreach (['title', 'description', 'priority', 'dueAt'] as $field) {
            if (array_key_exists($field, $command->payload)) {
                $board['cards'][$id][$field] = $command->payload[$field];
            }
        }

        $board['cards'][$id]['version'] = (string) (((int) $board['cards'][$id]['version']) + 1);

        return ActionResult::success([$id], ['card:'.$id], $board['cards'][$id]['version']);
    }

    /** @param array<string, mixed> $board */
    private function archiveCard(array &$board, ActionCommand $command, bool $archive): ActionResult
    {
        $id = (string) $command->payload['cardId'];

        if (! isset($board['cards'][$id])) {
            return new ActionResult(ActionStatus::NOT_FOUND, messageCode: 'not_found');
        }

        $board['cards'][$id]['archived'] = $archive;

        if (! $archive && is_string($command->payload['stageId'] ?? null) && $command->payload['stageId'] !== '') {
            $board['cards'][$id]['stageId'] = $command->payload['stageId'];
        }

        $board['cards'][$id]['version'] = (string) (((int) $board['cards'][$id]['version']) + 1);

        return ActionResult::success([$id], ['counts', 'card:'.$id], $board['cards'][$id]['version']);
    }

    /** @param array<string, mixed> $board */
    private function createStage(array &$board, ActionCommand $command): ActionResult
    {
        $title = trim((string) ($command->payload['title'] ?? ''));
        $color = (string) ($command->payload['color'] ?? '#336699');

        if ($title === '') {
            return new ActionResult(ActionStatus::VALIDATION_ERROR, fieldErrors: ['title' => 'required']);
        }

        $id = 'stage-'.(count($board['stages']) + 1);
        $board['stages'][$id] = [
            'id' => $id,
            'title' => $title,
            'order' => count($board['stages']),
            'color' => $color,
            'visible' => true,
            'readOnly' => false,
            'disabled' => false,
            'wipLimit' => null,
            'wipMode' => 'off',
            'archived' => false,
        ];

        return ActionResult::success([$id], ['board'], '1');
    }

    /** @param array<string, mixed> $board */
    private function updateStage(array &$board, ActionCommand $command): ActionResult
    {
        $id = (string) $command->payload['stageId'];

        if (! isset($board['stages'][$id])) {
            return new ActionResult(ActionStatus::NOT_FOUND, messageCode: 'not_found');
        }

        if (isset($command->payload['title'])) {
            $board['stages'][$id]['title'] = (string) $command->payload['title'];
        }

        if (isset($command->payload['color']) && is_string($command->payload['color'])) {
            $board['stages'][$id]['color'] = $command->payload['color'];
        }

        return ActionResult::success([$id], ['board']);
    }

    /** @param array<string, mixed> $board */
    private function reorderStages(array &$board, ActionCommand $command): ActionResult
    {
        $ids = array_values(array_filter(explode(',', (string) $command->payload['stageIds'])));

        foreach ($ids as $order => $id) {
            if (isset($board['stages'][$id])) {
                $board['stages'][$id]['order'] = $order;
            }
        }

        return ActionResult::success($ids, ['board']);
    }

    /** @param array<string, mixed> $board */
    private function archiveStage(array &$board, ActionCommand $command): ActionResult
    {
        $id = (string) $command->payload['stageId'];
        $active = count(array_filter($board['cards'], static fn (array $card): bool => ! $card['archived'] && $card['stageId'] === $id));

        if ($active > 0 && ! $board['allowArchiveNonEmpty']) {
            return new ActionResult(ActionStatus::DOMAIN_REJECTED, messageCode: 'stage_not_empty');
        }

        $board['stages'][$id]['archived'] = true;

        return ActionResult::success([$id], ['board']);
    }

    /** @param array<string, mixed> $board */
    private function custom(array &$board, ActionCommand $command): ActionResult
    {
        $id = (string) $command->payload['cardId'];

        if (! isset($board['cards'][$id])) {
            return new ActionResult(ActionStatus::NOT_FOUND, messageCode: 'not_found');
        }

        $board['cards'][$id]['metadata']['lastAction'] = $command->payload['actionId'] ?? '';

        return ActionResult::success([$id], ['card:'.$id]);
    }

    /** @param array<string, mixed> $board */
    private function bulk(array &$board, ActionCommand $command): ActionResult
    {
        $ids = array_values(array_filter(explode(',', (string) $command->payload['ids'])));
        $mode = (string) $command->payload['mode'];
        $items = [];
        $denied = [];

        foreach ($ids as $id) {
            if (! isset($board['cards'][$id]) || in_array($id, $board['bulkDeny'], true)) {
                $denied[] = $id;
                $items[] = new BulkItemResult($id, ActionStatus::FORBIDDEN, 'forbidden');
            } else {
                $items[] = new BulkItemResult($id, ActionStatus::SUCCESS);
            }
        }

        if ($mode === 'all-or-nothing' && $denied !== []) {
            return new ActionResult(
                ActionStatus::DOMAIN_REJECTED,
                messageCode: 'bulk_rejected',
                itemResults: array_map(static fn (string $id): BulkItemResult => new BulkItemResult($id, ActionStatus::DOMAIN_REJECTED, 'rolled_back'), $ids),
                successCount: 0,
                failureCount: count($ids),
            );
        }

        $success = 0;

        foreach ($ids as $id) {
            if (in_array($id, $denied, true) || ! isset($board['cards'][$id])) {
                continue;
            }

            $board['cards'][$id]['archived'] = true;
            $success++;
        }

        return new ActionResult(
            $denied === [] ? ActionStatus::SUCCESS : ActionStatus::DOMAIN_REJECTED,
            messageCode: 'bulk_completed',
            affectedIds: array_values(array_diff($ids, $denied)),
            refreshHints: ['counts'],
            itemResults: $items,
            successCount: $success,
            failureCount: count($denied),
        );
    }

    private function store(string $key, ActionResult $result): ActionResult
    {
        MemoryStore::$operations[$key] = [
            'status' => $result->status->value,
            'affectedIds' => $result->affectedIds,
            'newVersion' => $result->newVersion,
            'messageCode' => $result->messageCode,
            'fieldErrors' => $result->fieldErrors,
            'successCount' => $result->successCount,
            'failureCount' => $result->failureCount,
        ];

        return $result;
    }

    private function restore(string $key): ActionResult
    {
        $stored = MemoryStore::$operations[$key];

        return new ActionResult(
            ActionStatus::from($stored['status']),
            messageCode: $stored['messageCode'],
            fieldErrors: $stored['fieldErrors'],
            affectedIds: $stored['affectedIds'],
            newVersion: $stored['newVersion'],
            successCount: $stored['successCount'],
            failureCount: $stored['failureCount'],
        );
    }
}

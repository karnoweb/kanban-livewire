<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Karnoweb\Kanban\Contracts\DataProvider;
use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\ActionResult;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardDefinition;
use Karnoweb\Kanban\Data\BoardQuery;
use Karnoweb\Kanban\Enums\ActionStatus;
use Karnoweb\Kanban\Enums\ActionType;

/**
 * Server-side gate: a disabled feature or an unusable target can never reach the host handler,
 * regardless of which Livewire method was invoked from the client.
 */
final class CommandPolicy
{
    public function check(ActionCommand $command, BoardContext $context, BoardDefinition $definition, DataProvider $provider): ?ActionResult
    {
        $features = $definition->settings->features;

        $enabled = match ($command->type) {
            ActionType::MoveCard => true,
            ActionType::ReorderCard => $features->reorderCards,
            ActionType::CreateCard => $features->createCard,
            ActionType::UpdateCard => $features->updateCard,
            ActionType::ArchiveCard => $features->archiveCard,
            ActionType::RestoreCard => $features->restoreCard,
            ActionType::DeleteCard => $features->deleteCard,
            ActionType::CreateStage => $features->createStage,
            ActionType::UpdateStage => $features->updateStage,
            ActionType::ReorderStage => $features->reorderStages,
            ActionType::ArchiveStage => $features->archiveStage,
            ActionType::ExecuteCardAction => $features->customActions,
            ActionType::ExecuteBulkAction => $features->bulk,
        };

        if (! $enabled) {
            return new ActionResult(ActionStatus::FORBIDDEN, messageCode: 'feature_disabled');
        }

        if ($command->type === ActionType::ExecuteBulkAction) {
            $ids = array_filter(explode(',', (string) ($command->payload['ids'] ?? '')), static fn (string $id): bool => $id !== '');

            if ($ids === [] || count($ids) > $definition->settings->security->bulkIds) {
                return new ActionResult(ActionStatus::VALIDATION_ERROR, messageCode: 'bulk_size_invalid');
            }
        }

        $target = match ($command->type) {
            ActionType::MoveCard, ActionType::ReorderCard => $command->payload['toStageId'] ?? null,
            ActionType::CreateCard => $command->payload['stageId'] ?? null,
            default => null,
        };

        if (is_string($target) && $target !== '') {
            $stage = null;

            foreach ($provider->stages($context, new BoardQuery) as $candidate) {
                if ($candidate->id === $target) {
                    $stage = $candidate;
                    break;
                }
            }

            if ($stage === null || $stage->archived) {
                return new ActionResult(ActionStatus::NOT_FOUND, messageCode: 'not_found');
            }

            if ($stage->readOnly || $stage->disabled) {
                return new ActionResult(ActionStatus::FORBIDDEN, messageCode: 'stage_read_only');
            }
        }

        return null;
    }
}

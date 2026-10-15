<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Actions;

use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\BoardDefinition;
use Karnoweb\Kanban\Enums\ActionType;

final class StageCoordinator
{
    /** @param array<string, mixed> $fields */
    public function create(string $operationId, array $fields): ActionCommand
    {
        return new ActionCommand($operationId, ActionType::CreateStage, $fields);
    }

    /** @param array<string, mixed> $fields */
    public function update(string $operationId, string $stageId, array $fields, BoardDefinition $definition): ActionCommand
    {
        $allowed = [];

        foreach ($fields as $key => $value) {
            if ($definition->stageForm->allows((string) $key)) {
                $allowed[$key] = $value;
            }
        }

        $allowed['stageId'] = $stageId;

        return new ActionCommand($operationId, ActionType::UpdateStage, $allowed);
    }

    /** @param list<string> $stageIds */
    public function reorder(string $operationId, array $stageIds): ActionCommand
    {
        return new ActionCommand($operationId, ActionType::ReorderStage, ['stageIds' => implode(',', $stageIds)]);
    }

    public function archive(string $operationId, string $stageId): ActionCommand
    {
        return new ActionCommand($operationId, ActionType::ArchiveStage, ['stageId' => $stageId]);
    }
}

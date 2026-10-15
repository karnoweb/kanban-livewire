<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Actions;

use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\BoardDefinition;
use Karnoweb\Kanban\Enums\ActionType;

final class CardCoordinator
{
    /** @param array<string, mixed> $fields */
    public function create(string $operationId, array $fields, BoardDefinition $definition): ActionCommand
    {
        return new ActionCommand($operationId, ActionType::CreateCard, $this->onlyAllowed($fields, $definition));
    }

    /** @param array<string, mixed> $fields */
    public function update(string $operationId, string $cardId, array $fields, BoardDefinition $definition): ActionCommand
    {
        $payload = $this->onlyAllowed($fields, $definition);
        $payload['cardId'] = $cardId;

        return new ActionCommand($operationId, ActionType::UpdateCard, $payload);
    }

    public function archive(string $operationId, string $cardId): ActionCommand
    {
        return new ActionCommand($operationId, ActionType::ArchiveCard, ['cardId' => $cardId]);
    }

    public function restore(string $operationId, string $cardId, ?string $stageId): ActionCommand
    {
        return new ActionCommand($operationId, ActionType::RestoreCard, [
            'cardId' => $cardId,
            'stageId' => $stageId,
        ]);
    }

    public function custom(string $operationId, string $cardId, string $actionId, array $fields): ActionCommand
    {
        return new ActionCommand($operationId, ActionType::ExecuteCardAction, [
            'cardId' => $cardId,
            'actionId' => $actionId,
            'fields' => implode(',', array_map('strval', $fields)),
        ]);
    }

    /** @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function onlyAllowed(array $fields, BoardDefinition $definition): array
    {
        $allowed = [];

        foreach ($fields as $key => $value) {
            if ($definition->cardForm->allows((string) $key)) {
                $allowed[$key] = $value;
            }
        }

        return $allowed;
    }
}

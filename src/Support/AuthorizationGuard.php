<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Karnoweb\Kanban\Contracts\DataProvider;
use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardDefinition;
use Karnoweb\Kanban\Enums\ActionType;
use Karnoweb\Kanban\Exceptions\BoardAccessDenied;

final class AuthorizationGuard
{
    public function authorize(ActionCommand $command, BoardContext $context, BoardDefinition $definition, DataProvider $provider): void
    {
        if ($context->boardKey !== $definition->key || ($command->payload['boardId'] ?? $definition->board->id) !== $definition->board->id) {
            throw new BoardAccessDenied;
        }

        $cardId = $command->payload['cardId'] ?? null;

        if (is_string($cardId) && $cardId !== '' && $command->type !== ActionType::CreateCard) {
            $card = $provider->card($context, $cardId);

            if ($card !== null && ($card->boardId !== $definition->board->id)) {
                throw new BoardAccessDenied;
            }
        }
    }
}

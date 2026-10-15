<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Karnoweb\Kanban\Contracts\ActionHandler;
use Karnoweb\Kanban\Contracts\DataProvider;
use Karnoweb\Kanban\Data\BoardDefinition;
use Karnoweb\Kanban\Data\CardData;
use Karnoweb\Kanban\Data\StageData;
use Karnoweb\Kanban\Exceptions\InvalidBoardDefinition;
use Karnoweb\Kanban\Providers\InlineDataProvider;

final class BoardDefinitionValidator
{
    public function validate(BoardDefinition $definition): BoardDefinition
    {
        $key = trim($definition->key);

        if ($key === '' || $definition->board->id === '') {
            throw new InvalidBoardDefinition('Board key is required.', $definition->key);
        }

        if (! is_a($definition->providerClass, DataProvider::class, true)) {
            throw new InvalidBoardDefinition('Provider must implement DataProvider.', $key);
        }

        if (! is_a($definition->handlerClass, ActionHandler::class, true)) {
            throw new InvalidBoardDefinition('Handler must implement ActionHandler.', $key);
        }

        if ($definition->providerClass === InlineDataProvider::class && $definition->inlineStages === [] && $definition->inlineCards !== []) {
            throw new InvalidBoardDefinition('Inline cards require stages.', $key);
        }

        $stageIds = [];

        foreach ($definition->inlineStages as $stage) {
            if (! $stage instanceof StageData || $stage->boardId !== $definition->board->id) {
                throw new InvalidBoardDefinition('Inline stage does not belong to this board.', $key);
            }

            if (isset($stageIds[$stage->id])) {
                throw new InvalidBoardDefinition('Stage ids must be unique.', $key);
            }

            $stageIds[$stage->id] = true;
        }

        $cardIds = [];

        foreach ($definition->inlineCards as $card) {
            if (! $card instanceof CardData || $card->boardId !== $definition->board->id) {
                throw new InvalidBoardDefinition('Inline card does not belong to this board.', $key);
            }

            if ($stageIds !== [] && ! isset($stageIds[$card->stageId])) {
                throw new InvalidBoardDefinition('Inline card stage is unknown.', $key);
            }

            if (isset($cardIds[$card->id])) {
                throw new InvalidBoardDefinition('Card ids must be unique inside a board.', $key);
            }

            $cardIds[$card->id] = true;
        }

        $resolver = new ViewResolver;

        foreach ($definition->views as $name => $view) {
            $resolver->assertAllowed($name, $view);
        }

        return $definition;
    }
}

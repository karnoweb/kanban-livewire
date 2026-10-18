<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Fixtures;

use Karnoweb\Kanban\Contracts\BoardFactory;
use Karnoweb\Kanban\Data\{BoardContext, BoardData, BoardDefinition};
use Karnoweb\Kanban\Support\BoardBuilder;

final class CardDetailsBoardFactory implements BoardFactory
{
    public function make(BoardContext $context): BoardDefinition
    {
        $row = MemoryStore::board($context->scopeKey, $context->boardKey);

        return (new BoardBuilder)
            ->key($context->boardKey)
            ->board(new BoardData($context->boardKey, $row['title'], $context->scopeKey))
            ->provider(InMemoryProvider::class)
            ->handler(InMemoryHandler::class)
            ->settings($row['settings'])
            ->detailsProvider(TestCardDetailsProvider::class)
            ->build();
    }
}

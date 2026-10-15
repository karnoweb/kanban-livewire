<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Fixtures;

use Karnoweb\Kanban\Contracts\BoardFactory;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardData;
use Karnoweb\Kanban\Data\BoardDefinition;
use Karnoweb\Kanban\Data\KanbanSettings;
use Karnoweb\Kanban\Support\BoardBuilder;

final class InMemoryBoardFactory implements BoardFactory
{
    public function make(BoardContext $context): BoardDefinition
    {
        $row = MemoryStore::board($context->scopeKey, $context->boardKey);

        return (new BoardBuilder)
            ->key($context->boardKey)
            ->board(new BoardData($context->boardKey, $row['title'], $context->scopeKey, version: (string) ($row['version'] ?? '1')))
            ->provider(InMemoryProvider::class)
            ->handler(InMemoryHandler::class)
            ->settings(KanbanSettings::fromArray($row['settings']))
            ->build();
    }
}

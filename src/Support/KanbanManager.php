<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardDefinition;

final class KanbanManager
{
    public function init(): BoardBuilder
    {
        return new BoardBuilder;
    }

    public function resolveBoard(string $key, BoardContext $context): BoardDefinition
    {
        return app(BoardRegistry::class)->resolve($key, $context);
    }
}

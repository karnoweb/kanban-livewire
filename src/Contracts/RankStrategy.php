<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Contracts;

use Illuminate\Database\Eloquent\Model;
use Karnoweb\Kanban\Data\{ActionCommand, BoardContext, BoardDefinition};
use Karnoweb\Kanban\Models\KanbanModelDefinition;

interface RankStrategy
{
    public function supports(BoardDefinition $board, array $definitions): bool;

    /** Called inside a transaction and scope write lock. Returns rebalanced card ids. */
    public function assign(Model $record, KanbanModelDefinition $definition, BoardDefinition $board, BoardContext $context, ActionCommand $command, string $stage): array;
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Concerns;

use Karnoweb\Kanban\Models\KanbanModelDefinition;

trait HasKanbanCards
{
    abstract public static function kanbanDefinition(): KanbanModelDefinition;
}

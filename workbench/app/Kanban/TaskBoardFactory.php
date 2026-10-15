<?php

declare(strict_types=1);

namespace Workbench\App\Kanban;

final class TaskBoardFactory extends WorkbenchBoardFactory
{
    protected function key(): string
    {
        return 'tasks.board';
    }

    protected function title(): string
    {
        return 'Tasks';
    }

    protected function provider(): string
    {
        return TaskProvider::class;
    }

    protected function handler(): string
    {
        return TaskHandler::class;
    }

    protected function settings(): array
    {
        return ['interaction' => ['transition_policy' => 'allow'], 'features' => ['wip' => true, 'bulk' => true, 'create_card' => true, 'create_stage' => true]];
    }
}

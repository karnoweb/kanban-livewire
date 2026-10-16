<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Fixtures;

use Karnoweb\Kanban\Contracts\BoardFactory;
use Karnoweb\Kanban\Data\{BoardContext, BoardData, BoardDefinition};
use Karnoweb\Kanban\Support\BoardBuilder;

final class EnterpriseBoardFactory implements BoardFactory
{
    public function make(BoardContext $context): BoardDefinition
    {
        $builder = (new BoardBuilder)->key($context->boardKey)
            ->board(new BoardData($context->boardKey, 'Enterprise', $context->scopeKey))
            ->settings(array_replace_recursive(['interaction' => ['transition_policy' => 'allow']], config('kanban_test.settings', [])));
        if (config('kanban_test.swimlanes', false)) {
            $builder->swimlanes(new \Karnoweb\Kanban\Data\SwimlaneDefinition);
        }

        return match ($context->boardKey) {
            'empty' => $builder->sources([])->build(),
            'tickets' => $builder->sources(['tickets'])->build(),
            'classes' => $builder->sources(['classes'])->build(),
            'mixed' => $builder->sources(['tasks', 'tickets'])->stageMap([
                'tasks' => ['todo' => 'todo', 'doing' => 'doing', 'done' => 'done'],
                'tickets' => ['open' => config('kanban_test.ticket_open_stage', 'todo'), 'closed' => 'done'],
            ])->build(),
            default => $builder->sources(['tasks'])->build(),
        };
    }
}

<?php

declare(strict_types=1);

namespace Workbench\App\Kanban;

use Karnoweb\Kanban\Contracts\BoardFactory;
use Karnoweb\Kanban\Data\{BoardContext, BoardData, BoardDefinition, SwimlaneDefinition};
use Karnoweb\Kanban\Support\BoardBuilder;

final class EloquentBoardFactory implements BoardFactory
{
    public function make(BoardContext $context): BoardDefinition
    {
        $source = match ($context->boardKey) {
            'models.tickets' => 'tickets', 'models.classes' => 'classes', default => 'tasks',
        };
        $mixed = $context->boardKey === 'models.mixed';
        $compact = $context->boardKey === 'models.compact';
        $builder = (new BoardBuilder)->key($context->boardKey)
            ->board(new BoardData($context->boardKey, 'Eloquent models', $context->scopeKey))
            ->sources($mixed ? ['tasks', 'tickets', 'classes'] : [$source])
            ->swimlanes(new SwimlaneDefinition(allowMove: true))
            ->settings([
                'views' => ['default' => $compact ? 'compact' : 'board', 'allowed' => ['board', 'list', 'compact']],
                'pagination' => ['page_size' => $compact ? 5 : 25],
                'interaction' => ['transition_policy' => 'allow'],
                'appearance' => ['direction' => 'rtl', 'theme' => 'system'],
                'features' => ['toolbar' => ! $compact],
            ]);
        if ($mixed) {
            $builder->stageMap([
                'tasks' => ['todo' => 'todo', 'doing' => 'doing', 'done' => 'done'],
                'tickets' => ['open' => 'todo', 'answering' => 'doing', 'closed' => 'done'],
                'classes' => ['pending' => 'todo', 'review' => 'doing', 'approved' => 'done'],
            ]);
        }

        return $builder->build();
    }
}

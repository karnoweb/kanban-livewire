<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Models;

use InvalidArgumentException;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardDefinition;
use Karnoweb\Kanban\Support\BoardRegistry;

final class BoardSources
{
    public function __construct(private readonly BoardRegistry $boards, private readonly KanbanModelRegistry $models) {}

    public function board(BoardContext $context): BoardDefinition
    {
        return $this->boards->resolve($context->boardKey, $context);
    }

    /** Rebuild definitions for this call; never cache across requests or actors. */
    public function definitions(BoardDefinition $board, ?array $selection = null): array
    {
        $allowed = $board->sources ?? [];
        if ($selection !== null && array_diff($selection, $allowed) !== []) {
            throw new InvalidArgumentException('Source selection is outside this board.');
        }
        $definitions = [];
        foreach ($selection ?? $allowed as $source) {
            $definitions[$source] = $this->models->definition($source);
        }

        return $definitions;
    }

    public function mappedStage(BoardDefinition $board, string $source, string $stage): string
    {
        return (string) ($board->stageMap[$source][$stage] ?? $stage);
    }

    public function nativeStages(BoardDefinition $board, KanbanModelDefinition $definition, string $stage): array
    {
        return array_values(array_filter(array_map('strval', array_keys($definition->stageDefinitions)),
            fn ($value) => $this->mappedStage($board, $definition->source, $value) === $stage));
    }

    public function rankStrategy(BoardDefinition $board): \Karnoweb\Kanban\Contracts\RankStrategy
    {
        $strategy = $board->settings->model->ordering['strategy'];
        $class = in_array($strategy, ['rank', 'sparse_numeric'], true) ? \Karnoweb\Kanban\Contracts\RankStrategy::class : $strategy;
        if (! is_a($class, \Karnoweb\Kanban\Contracts\RankStrategy::class, true)) {
            throw new InvalidArgumentException('Ordering strategy must implement RankStrategy.');
        }

        return app($class);
    }
}

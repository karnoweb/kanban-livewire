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

        if ($definition->detailsProviderClass !== null && ! is_a($definition->detailsProviderClass, \Karnoweb\Kanban\Contracts\CardDetailsProvider::class, true)) {
            throw new InvalidBoardDefinition('Details provider must implement CardDetailsProvider.', $key);
        }

        if ($definition->providerClass === InlineDataProvider::class && $definition->inlineStages === [] && $definition->inlineCards !== []) {
            throw new InvalidBoardDefinition('Inline cards require stages.', $key);
        }

        $stageIds = [];

        if ($definition->sources !== null) {
            $registry = app(\Karnoweb\Kanban\Models\KanbanModelRegistry::class);
            foreach ($definition->sources as $source) {
                \Karnoweb\Kanban\Models\CardIdentity::assertSource($source);
                if (! $registry->has($source)) {
                    throw new InvalidBoardDefinition('Board contains an unregistered source.', $key);
                }
                if (! $registry->isEloquent($source)) {
                    if ($definition->providerClass === \Karnoweb\Kanban\Providers\EloquentKanbanDataProvider::class) {
                        throw new InvalidBoardDefinition('Non-Eloquent sources require an explicit combined provider.', $key);
                    }
                    continue;
                }
                $model = $registry->definition($source);
                if ($model->stageDefinitions === []) {
                    throw new InvalidBoardDefinition('A model source must declare its stages.', $key);
                }
                if (count($definition->sources) > 1 || isset($definition->stageMap[$source])) {
                    $map = $definition->stageMap[$source] ?? [];
                    if (array_diff(array_keys($model->stageDefinitions), array_keys($map)) !== [] || count($map) !== count($model->stageDefinitions)) {
                        throw new InvalidBoardDefinition('Mixed sources require a complete, explicit stageMap.', $key);
                    }
                    foreach ($map as $mapped) {
                        if (! is_string($mapped)) {
                            throw new InvalidBoardDefinition('Mapped stage ids must be strings.', $key);
                        }
                        \Karnoweb\Kanban\Support\ValueGuard::id($mapped, 'stageMap');
                    }
                }
            }
        }

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

        foreach ($definition->detailSectionViews as $key => $view) {
            if (! is_string($key) || preg_match('/^[a-z][a-z0-9_.-]{0,63}$/D', $key) !== 1) {
                throw new InvalidBoardDefinition('Detail section key is invalid.', $definition->key);
            }
            $resolver->assertAllowed('detail-section', $view);
        }

        return $definition;
    }
}

<?php

declare(strict_types=1);

namespace Workbench\App\Kanban;

use Karnoweb\Kanban\Contracts\ActionHandler;
use Karnoweb\Kanban\Contracts\BoardFactory;
use Karnoweb\Kanban\Contracts\DataProvider;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardData;
use Karnoweb\Kanban\Data\BoardDefinition;
use Karnoweb\Kanban\Support\BoardBuilder;

abstract class WorkbenchBoardFactory implements BoardFactory
{
    abstract protected function key(): string;

    abstract protected function title(): string;

    /** @return array<string, mixed> */
    protected function settings(): array
    {
        return ['interaction' => ['transition_policy' => 'deny']];
    }

    /** @return array<string, mixed> */
    public function resolvedSettings(): array
    {
        $defaults = array_replace_recursive([
            'features' => ['delete_card' => true],
            'appearance' => ['theme' => 'light', 'direction' => 'ltr'],
            'views' => [
                'default' => 'board',
                'allowed' => ['board', 'list'],
                'density' => 'comfortable',
                'detail_surface' => 'modal',
            ],
        ], $this->settings());

        try {
            $overrides = session('workbench.playground.'.$this->key(), []);
        } catch (\Throwable) {
            $overrides = [];
        }
        if (! is_array($overrides) || $overrides === []) {
            return $defaults;
        }

        $merged = array_replace_recursive($defaults, $overrides);
        if (isset($overrides['views']['allowed']) && is_array($overrides['views']['allowed'])) {
            $merged['views']['allowed'] = array_values($overrides['views']['allowed']);
        }

        return $merged;
    }

    public function make(BoardContext $context): BoardDefinition
    {
        return (new BoardBuilder)
            ->key($this->key())
            ->board(new BoardData($this->key(), $this->title(), $context->scopeKey))
            ->provider($this->provider())
            ->handler($this->handler())
            ->settings($this->resolvedSettings())
            ->build();
    }

    /** @return class-string<DataProvider> */
    abstract protected function provider(): string;

    /** @return class-string<ActionHandler> */
    abstract protected function handler(): string;
}

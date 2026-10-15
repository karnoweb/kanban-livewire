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

    public function make(BoardContext $context): BoardDefinition
    {
        return (new BoardBuilder)
            ->key($this->key())
            ->board(new BoardData($this->key(), $this->title(), $context->scopeKey))
            ->provider($this->provider())
            ->handler($this->handler())
            ->settings(array_replace_recursive($this->settings(), [
                'realtime' => ['enabled' => true],
                'features' => ['realtime' => true],
            ]))
            ->build();
    }

    /** @return class-string<DataProvider> */
    abstract protected function provider(): string;

    /** @return class-string<ActionHandler> */
    abstract protected function handler(): string;
}

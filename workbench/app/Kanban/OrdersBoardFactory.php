<?php

declare(strict_types=1);

namespace Workbench\App\Kanban;

final class OrdersBoardFactory extends WorkbenchBoardFactory
{
    protected function key(): string
    {
        return 'orders.board';
    }

    protected function title(): string
    {
        return 'Orders';
    }

    protected function provider(): string
    {
        return OrdersProvider::class;
    }

    protected function handler(): string
    {
        return OrdersHandler::class;
    }

    protected function settings(): array
    {
        return [
            'interaction' => [
                'transition_policy' => 'deny',
                'transitions' => ['review' => ['finance'], 'finance' => ['shipped']],
            ],
            'features' => [
                'create_stage' => false,
                'bulk' => false,
                'wip' => false,
                'realtime' => false,
            ],
        ];
    }
}

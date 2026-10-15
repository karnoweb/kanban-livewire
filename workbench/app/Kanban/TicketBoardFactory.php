<?php

declare(strict_types=1);

namespace Workbench\App\Kanban;

final class TicketBoardFactory extends WorkbenchBoardFactory
{
    protected function key(): string
    {
        return 'tickets.board';
    }

    protected function title(): string
    {
        return 'Tickets';
    }

    protected function provider(): string
    {
        return TicketProvider::class;
    }

    protected function handler(): string
    {
        return TicketHandler::class;
    }

    protected function settings(): array
    {
        return [
            'interaction' => ['transition_policy' => 'deny', 'transitions' => ['open' => ['answering'], 'answering' => ['waiting', 'closed']]],
            'realtime' => ['enabled' => true],
            'features' => ['realtime' => true],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Workbench\App\Kanban;

final class CrmBoardFactory extends WorkbenchBoardFactory
{
    protected function key(): string
    {
        return 'crm.deals';
    }

    protected function title(): string
    {
        return 'CRM';
    }

    protected function provider(): string
    {
        return CrmProvider::class;
    }

    protected function handler(): string
    {
        return CrmHandler::class;
    }

    protected function settings(): array
    {
        return [
            'interaction' => [
                'transition_policy' => 'deny',
                'transitions' => ['new' => ['progress'], 'progress' => ['negotiation'], 'negotiation' => ['closed']],
                'confirmation_rules' => ['negotiation>closed' => ['mode' => 'form', 'fields' => ['note' => ['required' => true]]]],
            ],
            'features' => [
                'create_card' => true,
                'create_stage' => false,
                'bulk' => false,
                'filters' => false,
                'realtime' => false,
            ],
        ];
    }
}

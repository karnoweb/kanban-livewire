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

    public function resolvedSettings(): array
    {
        $settings = parent::resolvedSettings();
        // Workflow constraints belong to the host, not the appearance playground.
        $settings['features']['create_card'] = false;
        $settings['features']['create_stage'] = false;
        $settings['features']['bulk'] = false;
        $settings['interaction'] = $this->settings()['interaction'];

        return $settings;
    }

    protected function settings(): array
    {
        return [
            'interaction' => [
                'transition_policy' => 'deny',
                'transitions' => ['new' => ['progress'], 'progress' => ['negotiation'], 'negotiation' => ['closed']],
                'confirmation_rules' => array_fill_keys(['new>progress', 'progress>negotiation', 'negotiation>closed'], ['mode' => 'form', 'fields' => ['note' => ['required' => true]]]),
            ],
            'features' => [
                'create_card' => false,
                'create_stage' => false,
                'bulk' => false,
                'filters' => true,
                'realtime' => false,
            ],
        ];
    }
}

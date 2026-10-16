<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Livewire;

use Karnoweb\Kanban\Enums\ViewMode;

/** Same state machine and provider, with an explicitly enabled compact presentation. */
final class CompactBoard extends Board
{
    public function mount(string $boardKey): void
    {
        parent::mount($boardKey);
        if (! $this->accessDenied) {
            if (! $this->definition()->settings->views->allows(ViewMode::Compact)) {
                throw new \InvalidArgumentException('Enable compact in this board allowed views before using kanban-compact.');
            }
            $this->view = 'compact';
            $this->reload();
        }
    }
}

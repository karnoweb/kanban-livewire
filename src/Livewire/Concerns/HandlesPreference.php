<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Livewire\Concerns;

use Karnoweb\Kanban\Enums\ViewMode;
use Karnoweb\Kanban\Support\PreferenceManager;

trait HandlesPreference
{
    public function rememberCurrentView(): void
    {
        $mode = ViewMode::tryFrom($this->view);

        if ($mode !== null) {
            app(PreferenceManager::class)->rememberView($this->context(), $mode);
        }
    }
}

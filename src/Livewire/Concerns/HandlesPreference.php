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

    public function setTheme(string $theme): void
    {
        if (! in_array($theme, ['light', 'dark', 'system'], true)) {
            return;
        }

        $this->theme = $theme;
        app(PreferenceManager::class)->rememberTheme($this->context(), $theme);
    }

    public function setDirection(string $direction): void
    {
        if (! in_array($direction, ['ltr', 'rtl'], true)) {
            return;
        }

        $this->dir = $direction;
        app(PreferenceManager::class)->rememberDirection($this->context(), $direction);
    }

    public function setDensity(string $density): void
    {
        if (! in_array($density, ['compact', 'comfortable'], true)) {
            return;
        }

        $this->density = $density;
        app(PreferenceManager::class)->rememberDensity($this->context(), $density);
    }

    public function dismissNotice(): void
    {
        $this->notice = [];
    }
}

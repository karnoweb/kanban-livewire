<?php

declare(strict_types=1);

namespace Karnoweb\Kanban;

use Illuminate\Support\ServiceProvider;
use Karnoweb\Kanban\Contracts\AuditSink;
use Karnoweb\Kanban\Contracts\Monitor;
use Karnoweb\Kanban\Contracts\PreferenceStore;
use Karnoweb\Kanban\Livewire\Board;
use Karnoweb\Kanban\Contracts\ScopeResolver;
use Karnoweb\Kanban\Preferences\SessionPreferenceStore;
use Karnoweb\Kanban\Support\BoardRegistry;
use Karnoweb\Kanban\Support\ContextFactory;
use Karnoweb\Kanban\Support\KanbanManager;
use Karnoweb\Kanban\Support\NullAuditSink;
use Karnoweb\Kanban\Support\NullMonitor;
use Karnoweb\Kanban\Support\UnconfiguredScopeResolver;
use Livewire\Livewire;

final class KanbanServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/kanban.php', 'kanban');
        $this->app->singleton(BoardRegistry::class);
        $this->app->singleton(KanbanManager::class);
        $this->app->alias(KanbanManager::class, 'kanban');
        $this->app->singleton(ScopeResolver::class, UnconfiguredScopeResolver::class);
        $this->app->singleton(ContextFactory::class);
        $this->app->singleton(Monitor::class, NullMonitor::class);
        $this->app->singleton(AuditSink::class, NullAuditSink::class);
        $this->app->singleton(PreferenceStore::class, SessionPreferenceStore::class);
        $this->app->singleton(\Karnoweb\Kanban\Support\IdempotencyGuard::class);
        $this->app->singleton(\Karnoweb\Kanban\Realtime\EventDeduplicator::class);
        $this->app->singleton(\Karnoweb\Kanban\Realtime\RealtimeBridge::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'kanban');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'kanban');

        $this->publishes([
            __DIR__.'/../config/kanban.php' => config_path('kanban.php'),
        ], 'kanban-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/kanban'),
        ], 'kanban-views');

        $this->publishes([
            __DIR__.'/../resources/dist' => public_path('vendor/kanban'),
        ], 'kanban-assets');

        Livewire::component('kanban-board', Board::class);
    }
}

<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use Karnoweb\Kanban\Contracts\ScopeResolver;
use Karnoweb\Kanban\Realtime\ChannelAuthorizer;
use Karnoweb\Kanban\Support\BoardRegistry;
use Workbench\App\Kanban\CrmBoardFactory;
use Workbench\App\Kanban\OrdersBoardFactory;
use Workbench\App\Kanban\TaskBoardFactory;
use Workbench\App\Kanban\TicketBoardFactory;
use Workbench\App\Kanban\WorkbenchScopeResolver;
use Workbench\App\Models\DemoUser;

final class KanbanBoardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ScopeResolver::class, WorkbenchScopeResolver::class);

        config([
            'auth.providers.users.model' => DemoUser::class,
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => env('WORKBENCH_DB', dirname(__DIR__, 2).'/database/workbench.sqlite'),
            'database.connections.sqlite.busy_timeout' => 10000,
            'database.connections.sqlite.transaction_mode' => 'IMMEDIATE',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
    }

    public function boot(BoardRegistry $registry): void
    {
        $registry->register('crm.deals', CrmBoardFactory::class);
        $registry->register('tickets.board', TicketBoardFactory::class);
        $registry->register('tasks.board', TaskBoardFactory::class);
        $registry->register('orders.board', OrdersBoardFactory::class);

        Broadcast::channel('kanban.{scopeHash}.{board}', static fn ($user, string $scopeHash, string $board): bool => app(ChannelAuthorizer::class)->allows($scopeHash, $board));
    }
}

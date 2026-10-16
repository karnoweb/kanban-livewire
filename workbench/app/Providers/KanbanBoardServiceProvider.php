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

        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/examples.php', 'workbench.examples');

        config([
            'auth.providers.users.model' => DemoUser::class,
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => env('WORKBENCH_DB', getenv('WORKBENCH_DB') ?: dirname(__DIR__, 2).'/database/workbench.sqlite'),
            'database.connections.sqlite.busy_timeout' => 10000,
            'database.connections.sqlite.transaction_mode' => 'IMMEDIATE',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        // Testbench disables putenv adapters for HTTP requests; preserve explicit test ports.
        if (($port = getenv('KANBAN_E2E_REVERB_PORT')) !== false && ctype_digit($port)) {
            config(['broadcasting.connections.reverb.options.port' => (int) $port]);
        }
    }

    public function boot(BoardRegistry $registry): void
    {
        $registry->register('crm.deals', CrmBoardFactory::class);
        $registry->register('tickets.board', TicketBoardFactory::class);
        $registry->register('tasks.board', TaskBoardFactory::class);
        $registry->register('orders.board', OrdersBoardFactory::class);
        $models = app(\Karnoweb\Kanban\Models\KanbanModelRegistry::class);
        $models->register('tasks', \Workbench\App\Models\KanbanTask::class);
        $models->register('tickets', \Workbench\App\Models\KanbanTicket::class);
        $models->register('classes', \Workbench\App\Models\KanbanEducationalClass::class);
        foreach (['models.tasks', 'models.tickets', 'models.classes', 'models.mixed', 'models.compact'] as $key) {
            $registry->register($key, \Workbench\App\Kanban\EloquentBoardFactory::class);
        }
        \Illuminate\Support\Facades\Event::listen(\Karnoweb\Kanban\Events\KanbanModelChanged::class, function ($change): void {
            $context = $change->context;
            $definition = app(BoardRegistry::class)->resolve($context->boardKey, $context);
            if ($definition->settings->realtime->enabled || $definition->settings->features->realtime) {
                app(\Karnoweb\Kanban\Realtime\RealtimePublisher::class)->publish(
                    $context, 'CardUpdated', $change->cardId, $change->version, $change->operationId,
                );
            }
        });

        Broadcast::channel('kanban.{scopeHash}.{board}', static fn ($user, string $scopeHash, string $board): bool => app(ChannelAuthorizer::class)->allows($scopeHash, $board));
    }
}

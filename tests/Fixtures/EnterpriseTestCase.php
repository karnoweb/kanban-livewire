<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Karnoweb\Kanban\Actions\EloquentKanbanActionHandler;
use Karnoweb\Kanban\Data\{ActionCommand, BoardContext, BoardData, BoardQuery, KanbanSettings};
use Karnoweb\Kanban\Enums\{ActionStatus, ActionType};
use Karnoweb\Kanban\Models\{CardIdentity, KanbanModelDefinition, KanbanModelRegistry};
use Karnoweb\Kanban\Providers\EloquentKanbanDataProvider;
use Karnoweb\Kanban\Support\{BoardBuilder, BoardRegistry};
use Karnoweb\Kanban\Tests\Fixtures\{EnterpriseBoardFactory, EnterpriseTask, EnterpriseTicket};
use Karnoweb\Kanban\Tests\TestCase;

abstract class EnterpriseTestCase extends TestCase
{
    protected EloquentKanbanDataProvider $provider;
    protected BoardContext $context;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]);
        $app['config']->set('cache.default', 'array');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('enterprise_tasks', function (Blueprint $table): void {
            $table->id(); $table->string('title'); $table->string('status')->default('todo');
            $table->string('description')->nullable(); $table->string('owner_id'); $table->bigInteger('rank')->default(1024);
            $table->integer('version')->default(1); $table->string('priority')->nullable();
            $table->timestamp('archived_at')->nullable(); $table->integer('amount')->default(0); $table->string('team')->nullable();
            $table->index(['owner_id', 'status', 'rank', 'id']);
        });
        Schema::create('enterprise_tickets', function (Blueprint $table): void {
            $table->id(); $table->string('subject'); $table->string('state')->default('open'); $table->string('owner_id');
        });
        Schema::create('enterprise_classes', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('approval_state')->default('pending'); $table->string('owner_id');
        });
        $registry = app(KanbanModelRegistry::class);
        $registry->register('tasks', EnterpriseTask::class);
        $registry->register('tickets', EnterpriseTicket::class);
        $registry->register('classes', EnterpriseClass::class);
        foreach (['tasks', 'tickets', 'classes', 'mixed', 'empty', 'other'] as $key) {
            app(BoardRegistry::class)->register($key, EnterpriseBoardFactory::class);
        }
        $this->context = new BoardContext('tasks', 'workspace-a', 'alice', 'instance-a');
        $this->provider = app(EloquentKanbanDataProvider::class);
    }

    protected function task(array $fields = []): EnterpriseTask
    {
        return EnterpriseTask::create(array_replace(['title' => 'Test', 'owner_id' => 'alice', 'status' => 'todo', 'rank' => 1024], $fields));
    }

    protected function command(ActionType $type, array $payload, ?string $version = '1', ?string $operation = null): \Karnoweb\Kanban\Data\ActionResult
    {
        return app(EloquentKanbanActionHandler::class)->execute(new ActionCommand($operation ?? bin2hex(random_bytes(12)), $type, $payload, $version), $this->context);
    }

}

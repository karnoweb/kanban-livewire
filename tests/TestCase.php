<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests;

use Karnoweb\Kanban\KanbanServiceProvider;
use Karnoweb\Kanban\Support\BoardRegistry;
use Karnoweb\Kanban\Support\ContextFactory;
use Karnoweb\Kanban\Tests\Fixtures\FakeScopeResolver;
use Karnoweb\Kanban\Tests\Fixtures\InMemoryBoardFactory;
use Karnoweb\Kanban\Tests\Fixtures\MemoryStore;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected FakeScopeResolver $scope;

    protected function setUp(): void
    {
        parent::setUp();
        MemoryStore::reset();
        $this->scope = new FakeScopeResolver;
        $this->app->instance(FakeScopeResolver::class, $this->scope);
        $this->app->instance(\Karnoweb\Kanban\Contracts\ScopeResolver::class, $this->scope);
        $this->app->instance(ContextFactory::class, new ContextFactory($this->scope));
        $this->app->instance(BoardRegistry::class, new BoardRegistry($this->app));
        $this->app->forgetInstance(\Karnoweb\Kanban\Support\IdempotencyGuard::class);
        $this->app->forgetInstance(\Karnoweb\Kanban\Realtime\EventDeduplicator::class);
        $this->app->forgetInstance(\Karnoweb\Kanban\Realtime\RealtimeBridge::class);
    }

    protected function getPackageProviders($app): array
    {
        return [
            KanbanServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('view.paths', array_merge(
            $app['config']->get('view.paths', []),
            [dirname(__DIR__).'/resources/views'],
        ));
    }

    protected function registerBoard(string $key): void
    {
        $this->app->make(BoardRegistry::class)->register($key, InMemoryBoardFactory::class);
    }
}

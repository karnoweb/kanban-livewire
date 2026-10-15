<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Workbench;

use Karnoweb\Kanban\KanbanServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\DemoUser;
use Workbench\App\Providers\KanbanBoardServiceProvider;
use Workbench\Database\Seeders\KanbanDemoSeeder;

abstract class WorkbenchTestCase extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        putenv('WORKBENCH_DB=:memory:');
        $_ENV['WORKBENCH_DB'] = ':memory:';
        $_SERVER['WORKBENCH_DB'] = ':memory:';
    }

    protected function getPackageProviders($app): array
    {
        return [LivewireServiceProvider::class, KanbanServiceProvider::class, KanbanBoardServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('broadcasting.default', 'null');
        $app['config']->set('queue.default', 'sync');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__, 2).'/workbench/database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(KanbanDemoSeeder::class);
    }

    protected function loginAs(string $name): DemoUser
    {
        $user = DemoUser::query()->where('name', $name)->firstOrFail();
        $this->actingAs($user);

        return $user;
    }
}

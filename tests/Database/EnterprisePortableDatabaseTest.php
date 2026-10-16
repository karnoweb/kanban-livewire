<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Database;

use Illuminate\Support\Facades\Schema;
use Karnoweb\Kanban\Data\BoardQuery;
use Karnoweb\Kanban\Enums\{ActionStatus, ActionType};
use Karnoweb\Kanban\Tests\Fixtures\{EnterpriseTask, EnterpriseTestCase};

/** Explicit opt-in only: this test never connects to an application's normal database. */
final class EnterprisePortableDatabaseTest extends EnterpriseTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $driver = getenv('KANBAN_TEST_DB_DRIVER');
        $database = getenv('KANBAN_TEST_DB_DATABASE');
        if (! in_array($driver, ['mysql', 'pgsql'], true) || ! is_string($database) || preg_match('/^kanban_agent_test_[a-z0-9_]+$/D', $database) !== 1) {
            throw new \RuntimeException('Explicit isolated kanban_agent_test_* database is required.');
        }
        $app['config']->set('database.connections.testing', [
            'driver' => $driver, 'host' => '127.0.0.1', 'port' => $driver === 'mysql' ? 3306 : 5432,
            'database' => $database, 'username' => $driver === 'mysql' ? 'root' : 'postgres',
            'password' => getenv('KANBAN_TEST_DB_PASSWORD') ?: '', 'charset' => $driver === 'mysql' ? 'utf8mb4' : 'utf8',
            'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'schema' => 'public', 'sslmode' => 'prefer',
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['enterprise_classes', 'enterprise_tickets', 'enterprise_tasks'] as $table) { Schema::dropIfExists($table); }
        parent::tearDown();
    }

    public function test_scoped_read_move_version_and_sql_metrics_on_the_real_engine(): void
    {
        $this->task(['amount' => 10]); $this->task(['owner_id' => 'bob', 'amount' => 900]);
        self::assertSame(1, $this->provider->counts($this->context, new BoardQuery)->boardTotal);
        self::assertSame(['tasks.amount' => 10.0], $this->provider->metrics($this->context, 'todo', new BoardQuery));
        $result = $this->command(ActionType::MoveCard, ['cardId' => 'tasks:1', 'fromStageId' => 'todo', 'toStageId' => 'doing']);
        self::assertSame(ActionStatus::SUCCESS, $result->status);
        self::assertSame('2', $result->newVersion);
        self::assertSame('doing', EnterpriseTask::find(1)->status);
    }

    public function test_binary_text_keyset_order_is_stable_under_engine_collation(): void
    {
        $titles = ['alpha', 'Alpha', '2', '10', 'قرارداد'];
        foreach ($titles as $title) { $this->task(['title' => $title]); }
        $loaded = []; $cursor = null;
        do {
            $page = $this->provider->listCards($this->context, new BoardQuery(limit: 1, cursor: $cursor, sort: 'title'));
            $loaded = array_merge($loaded, array_column($page->items, 'title')); $cursor = $page->nextCursor;
        } while ($page->hasMore);
        usort($titles, 'strcmp');
        self::assertSame($titles, $loaded);
    }

    public function test_atomic_bulk_rolls_back_on_foreign_owner_and_wip_is_enforced(): void
    {
        $this->task(); $this->task(['owner_id' => 'bob']);
        $result = $this->command(ActionType::ExecuteBulkAction, ['ids' => 'tasks:1,tasks:2', 'action' => 'archive', 'mode' => 'all-or-nothing', 'expectedVersions' => 'tasks:1:1,tasks:2:1']);
        self::assertSame(ActionStatus::NOT_FOUND, $result->status);
        self::assertNull(EnterpriseTask::find(1)->archived_at);
        $this->task(['status' => 'doing']); $this->task(['status' => 'doing']);
        self::assertSame(ActionStatus::WIP_LIMIT, $this->command(ActionType::MoveCard, ['cardId' => 'tasks:1', 'fromStageId' => 'todo', 'toStageId' => 'doing'])->status);
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Database\Schema\Blueprint;
use Karnoweb\Kanban\Concerns\HasKanbanCards;
use Karnoweb\Kanban\Data\{BoardContext, BoardQuery};
use Karnoweb\Kanban\Enums\{ActionStatus, ActionType};
use Karnoweb\Kanban\Models\{BoardSources, KanbanModelDefinition, KanbanModelRegistry};
use Karnoweb\Kanban\Providers\EloquentKanbanDataProvider;
use Karnoweb\Kanban\Support\{BoardRegistry, ModelCursor};
use Karnoweb\Kanban\Tests\Fixtures\{EnterpriseTask, EnterpriseTestCase};

final class EnterpriseAcceptanceTest extends EnterpriseTestCase
{
    public function test_cast_backed_enum_generates_stages_in_declared_order(): void
    {
        $registry = new KanbanModelRegistry;
        $registry->register('tasks', EnumTask::class);
        $definition = $registry->definition('tasks');
        self::assertSame(['todo', 'doing', 'done'], array_keys($definition->stageDefinitions));
        self::assertSame('Doing', $definition->stage('doing', 'board')->title);
    }

    public function test_each_column_has_an_independent_cursor_and_constant_count(): void
    {
        foreach (range(1, 3) as $i) { $this->task(); $this->task(['status' => 'doing']); }
        $todo = $this->provider->stageCards($this->context, 'todo', new BoardQuery(limit: 1));
        $doing = $this->provider->stageCards($this->context, 'doing', new BoardQuery(limit: 1));
        self::assertNotSame($todo->nextCursor, $doing->nextCursor);
        $next = $this->provider->stageCards($this->context, 'todo', new BoardQuery(limit: 1, cursor: $todo->nextCursor));
        self::assertSame(3, $next->totalCount);
        self::assertNotSame($todo->items[0]->id, $next->items[0]->id);
        self::assertSame(3, $doing->totalCount);
    }

    public function test_nullable_and_descending_sort_pages_do_not_duplicate_or_omit(): void
    {
        foreach ([null, 'high', 'high', 'low', null] as $priority) { $this->task(['priority' => $priority]); }
        foreach (['asc', 'desc'] as $direction) {
            $ids = []; $cursor = null;
            do {
                $page = $this->provider->listCards($this->context, new BoardQuery(limit: 1, cursor: $cursor, sort: 'priority', direction: $direction));
                $ids = array_merge($ids, array_column($page->items, 'id')); $cursor = $page->nextCursor;
            } while ($page->hasMore);
            self::assertCount(5, array_unique($ids));
        }
    }

    public function test_source_without_rank_uses_stable_ids_but_disables_manual_order(): void
    {
        $context = new BoardContext('tickets', 'workspace-a', 'alice', 'tickets');
        self::assertFalse($this->provider->capabilities($context)['manualOrder']);
        self::assertSame('rank_storage_required', $this->provider->capabilities($context)['reorderReason']);
    }

    public function test_eager_loaded_assignees_have_a_constant_query_count_for_twenty_five_cards(): void
    {
        Schema::create('enterprise_owners', fn (Blueprint $table) => [$table->string('id')->primary(), $table->string('name')]);
        DB::table('enterprise_owners')->insert(['id' => 'alice', 'name' => 'Alice']);
        foreach (range(1, 100) as $i) { $this->task(); }
        $registry = new KanbanModelRegistry; $registry->register('tasks', EagerTask::class);
        $provider = new EloquentKanbanDataProvider(new BoardSources(app(BoardRegistry::class), $registry), new ModelCursor);
        DB::enableQueryLog(); DB::flushQueryLog();
        $page = $provider->listCards($this->context, new BoardQuery);
        self::assertCount(25, $page->items);
        self::assertCount(3, DB::getQueryLog()); // Count + bounded cards + eager owners.
        self::assertSame('Alice', $page->items[0]->metadata['owner']);
        DB::flushQueryLog();
        $largePage = $provider->listCards($this->context, new BoardQuery(limit: 100));
        self::assertCount(100, $largePage->items);
        self::assertCount(3, DB::getQueryLog());
    }

    public function test_multisource_create_accepts_only_the_explicit_selected_source(): void
    {
        $this->context = new BoardContext('mixed', 'workspace-a', 'alice', 'mixed');
        $result = $this->command(ActionType::CreateCard, ['source' => 'tasks', 'stageId' => 'todo', 'title' => 'Mixed created']);
        self::assertSame(ActionStatus::SUCCESS, $result->status);
        self::assertSame('tasks:1', $result->affectedIds[0]);
    }

    public function test_cross_swimlane_move_is_denied_without_explicit_domain_guard(): void
    {
        config(['kanban_test.swimlanes' => true]); $this->task(['team' => 'A']);
        $result = $this->command(ActionType::MoveCard, ['cardId' => 'tasks:1', 'fromStageId' => 'todo', 'toStageId' => 'todo', 'toGroup' => 'B']);
        self::assertSame('group_move_denied', $result->messageCode);
        self::assertSame('A', EnterpriseTask::find(1)->team);
    }

    public function test_calendar_and_kanban_traits_share_one_domain_record_without_package_dependency(): void
    {
        $this->task(['title' => 'Shared record']);
        $record = CalendarTask::find(1);
        self::assertSame('Shared record', $record->calendarTitle());
        self::assertSame('Shared record', $this->provider->card($this->context, 'tasks:1')->title);
        self::assertArrayNotHasKey('karnoweb/laravel-livewire-calendar', json_decode(file_get_contents(dirname(__DIR__, 2).'/composer.json'), true)['require']);
    }

    public function test_manual_move_with_ten_thousand_neighbors_writes_one_row(): void
    {
        for ($start = 0; $start < 10000; $start += 500) {
            $rows = [];
            for ($i = $start; $i < $start + 500; $i++) { $rows[] = ['title' => 'Task '.$i, 'owner_id' => 'alice', 'status' => 'todo', 'rank' => ($i + 1) * 1024]; }
            DB::table('enterprise_tasks')->insert($rows);
        }
        DB::enableQueryLog(); DB::flushQueryLog();
        $result = $this->command(ActionType::ReorderCard, ['cardId' => 'tasks:10000', 'fromStageId' => 'todo', 'toStageId' => 'todo', 'afterCardId' => 'tasks:1', 'beforeCardId' => 'tasks:2']);
        self::assertSame(ActionStatus::SUCCESS, $result->status);
        self::assertCount(1, array_filter(DB::getQueryLog(), fn ($q) => str_starts_with($q['query'], 'update')));
    }

    public function test_class_only_board_never_queries_tasks_or_tickets(): void
    {
        \Karnoweb\Kanban\Tests\Fixtures\EnterpriseClass::create(['name' => 'Class', 'owner_id' => 'alice']);
        $context = new BoardContext('classes', 'workspace-a', 'alice', 'class');
        DB::enableQueryLog(); DB::flushQueryLog();
        self::assertSame('Class', $this->provider->listCards($context, new BoardQuery)->items[0]->title);
        self::assertStringNotContainsString('enterprise_tasks', json_encode(DB::getQueryLog()));
        self::assertStringNotContainsString('enterprise_tickets', json_encode(DB::getQueryLog()));
    }

    public function test_mixed_source_keyset_pagination_returns_every_id_once(): void
    {
        foreach (range(1, 11) as $i) {
            $this->task(['rank' => $i * 1024]);
            \Karnoweb\Kanban\Tests\Fixtures\EnterpriseTicket::create(['subject' => 'Ticket '.$i, 'owner_id' => 'alice']);
        }
        $context = new BoardContext('mixed', 'workspace-a', 'alice', 'mixed');
        $ids = []; $cursor = null;
        do {
            $page = $this->provider->listCards($context, new BoardQuery(limit: 3, cursor: $cursor));
            $ids = array_merge($ids, array_column($page->items, 'id')); $cursor = $page->nextCursor;
        } while ($page->hasMore);
        self::assertCount(22, $ids); self::assertCount(22, array_unique($ids));
    }

    public function test_model_broadcast_rejects_a_foreign_source_and_redacts_row_identifiers(): void
    {
        \Illuminate\Support\Facades\Event::fake([\Karnoweb\Kanban\Realtime\KanbanBroadcastEvent::class]);
        $publisher = app(\Karnoweb\Kanban\Realtime\RealtimePublisher::class);
        $event = $publisher->publish($this->context, 'CardUpdated', 'tasks:1', '2', 'publish-one');
        self::assertSame('BoardInvalidated', $event->eventType);
        self::assertNull($event->entityId); self::assertNull($event->entityVersion);
        try { $publisher->publish($this->context, 'CardUpdated', 'tickets:1'); self::fail(); }
        catch (\InvalidArgumentException) { self::assertTrue(true); }
        \Illuminate\Support\Facades\Event::assertDispatchedTimes(\Karnoweb\Kanban\Realtime\KanbanBroadcastEvent::class, 1);
    }

    public function test_cancelling_a_confirmation_has_no_database_side_effect(): void
    {
        config(['kanban_test.settings' => ['interaction' => ['confirmation' => 'generic']]]);
        $this->task(); $this->scope->actor = 'alice';
        \Livewire\Livewire::test(\Karnoweb\Kanban\Livewire\Board::class, ['boardKey' => 'tasks'])
            ->call('requestMove', 'tasks:1', 'todo', 'doing', null, null, null, '1')
            ->assertSet('pending.mode', 'generic')->call('cancelPending')->assertSet('pending', null);
        self::assertSame('todo', EnterpriseTask::find(1)->status);
    }

    public function test_two_instances_keep_actor_scoped_card_state_separate(): void
    {
        $this->task(['title' => 'Alice card']); $this->task(['owner_id' => 'bob', 'title' => 'Bob card']);
        $this->scope->actor = 'alice';
        $alice = \Livewire\Livewire::test(\Karnoweb\Kanban\Livewire\Board::class, ['boardKey' => 'tasks'])->assertSee('Alice card')->assertDontSee('Bob card');
        $this->scope->actor = 'bob';
        $bob = \Livewire\Livewire::test(\Karnoweb\Kanban\Livewire\Board::class, ['boardKey' => 'tasks'])->assertSee('Bob card')->assertDontSee('Alice card');
        self::assertNotSame($alice->get('instanceId'), $bob->get('instanceId'));
    }

    public function test_mapped_column_wip_cannot_be_bypassed_by_a_source_without_its_own_limit(): void
    {
        config(['kanban_test.ticket_open_stage' => 'doing']);
        $this->task(['status' => 'doing']); $this->task(['status' => 'doing']);
        $this->context = new BoardContext('mixed', 'workspace-a', 'alice', 'mixed');
        $result = $this->command(ActionType::CreateCard, ['source' => 'tickets', 'stageId' => 'doing', 'title' => 'Ticket']);
        self::assertSame(ActionStatus::WIP_LIMIT, $result->status);
        self::assertSame(0, \Karnoweb\Kanban\Tests\Fixtures\EnterpriseTicket::count());
    }
}

enum AcceptanceStatus: string
{
    case Todo = 'todo';
    case Doing = 'doing';
    case Done = 'done';
}

final class EnumTask extends EnterpriseTask
{
    protected $casts = ['status' => AcceptanceStatus::class];

    public static function kanbanDefinition(): KanbanModelDefinition
    {
        return KanbanModelDefinition::make('tasks', static::class);
    }
}

final class EagerTask extends EnterpriseTask
{
    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AcceptanceOwner::class, 'owner_id');
    }

    public static function kanbanDefinition(): KanbanModelDefinition
    {
        return parent::kanbanDefinition()->with(['owner'])->present(fn (Model $record) => ['metadata' => ['owner' => $record->owner->name]]);
    }
}

final class AcceptanceOwner extends Model
{
    protected $table = 'enterprise_owners';
    public $incrementing = false;
    protected $keyType = 'string';
}

/** Host-side calendar contract stand-in; no third-party Calendar package is installed. */
trait HostCalendarEvents
{
    public function calendarTitle(): string { return $this->title; }
}

final class CalendarTask extends EnterpriseTask
{
    use HostCalendarEvents;
}

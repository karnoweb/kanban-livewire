<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

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

class EnterpriseModelsTest extends \Karnoweb\Kanban\Tests\Fixtures\EnterpriseTestCase
{
    public function test_trait_model_can_be_displayed_through_the_existing_factory(): void
    {
        $task = $this->task();
        $page = $this->provider->stageCards($this->context, 'todo', new BoardQuery);
        self::assertSame('tasks:'.$task->id, $page->items[0]->id);
        self::assertSame(1, $page->totalCount);
        self::assertCount(3, $this->provider->stages($this->context, new BoardQuery));
    }

    public function test_read_scope_applies_to_pages_counts_details_and_metrics(): void
    {
        $this->task(['amount' => 12]);
        $foreign = $this->task(['owner_id' => 'bob', 'amount' => 900]);
        self::assertCount(1, $this->provider->listCards($this->context, new BoardQuery)->items);
        self::assertSame(1, $this->provider->counts($this->context, new BoardQuery)->boardTotal);
        self::assertNull($this->provider->card($this->context, 'tasks:'.$foreign->id));
        self::assertSame(['tasks.amount' => 12.0], $this->provider->metrics($this->context, 'todo', new BoardQuery));
    }

    public function test_other_registered_sources_are_never_queried(): void
    {
        $this->task();
        DB::enableQueryLog();
        $this->provider->stageCards($this->context, 'todo', new BoardQuery);
        $this->provider->counts($this->context, new BoardQuery);
        self::assertStringNotContainsString('enterprise_tickets', json_encode(DB::getQueryLog()));
        self::assertStringNotContainsString('enterprise_classes', json_encode(DB::getQueryLog()));
    }

    public function test_empty_allowlist_returns_zero_data(): void
    {
        $this->task();
        $context = new BoardContext('empty', 'workspace-a', 'alice', 'empty');
        DB::enableQueryLog(); DB::flushQueryLog();
        self::assertSame([], $this->provider->listCards($context, new BoardQuery)->items);
        self::assertSame(0, $this->provider->counts($context, new BoardQuery)->boardTotal);
        self::assertSame([], DB::getQueryLog());
    }

    public function test_mixed_sources_have_namespaced_ids_and_explicit_mapped_stages(): void
    {
        $this->task();
        EnterpriseTicket::create(['subject' => 'Ticket', 'owner_id' => 'alice']);
        $context = new BoardContext('mixed', 'workspace-a', 'alice', 'mixed');
        $page = $this->provider->stageCards($context, 'todo', new BoardQuery);
        self::assertEqualsCanonicalizing(['tasks:1', 'tickets:1'], array_column($page->items, 'id'));
        self::assertSame(2, $this->provider->counts($context, new BoardQuery)->forStage('todo')->totalCount);
    }

    public function test_source_filter_cannot_expand_board_permissions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->provider->listCards($this->context, new BoardQuery(filters: ['source' => 'tickets']));
    }

    public function test_unknown_and_raw_card_ids_are_indistinguishable_from_missing(): void
    {
        $this->task();
        foreach (['1', 'tickets:1', 'tasks:999', 'App\\Model:1', 'tasks:1:2'] as $id) {
            self::assertNull($this->provider->card($this->context, $id));
        }
    }

    public function test_keyset_pages_have_no_duplicates_with_tied_ranks(): void
    {
        foreach (range(1, 17) as $i) { $this->task(['title' => (string) $i]); }
        $ids = []; $cursor = null;
        do {
            $page = $this->provider->stageCards($this->context, 'todo', new BoardQuery(limit: 3, cursor: $cursor));
            $ids = array_merge($ids, array_column($page->items, 'id'));
            $cursor = $page->nextCursor;
        } while ($page->hasMore);
        self::assertCount(17, array_unique($ids));
        self::assertSame(array_map(fn ($i) => 'tasks:'.$i, range(1, 17)), $ids);
    }

    public function test_cursor_is_bound_to_every_security_and_query_dimension(): void
    {
        foreach (range(1, 4) as $i) { $this->task(); }
        $cursor = $this->provider->stageCards($this->context, 'todo', new BoardQuery(limit: 1))->nextCursor;
        $attempts = [
            [new BoardContext('other', 'workspace-a', 'alice', 'a'), 'todo', new BoardQuery(limit: 1, cursor: $cursor)],
            [new BoardContext('tasks', 'workspace-b', 'alice', 'a'), 'todo', new BoardQuery(limit: 1, cursor: $cursor)],
            [new BoardContext('tasks', 'workspace-a', 'bob', 'a'), 'todo', new BoardQuery(limit: 1, cursor: $cursor)],
            [$this->context, 'doing', new BoardQuery(limit: 1, cursor: $cursor)],
            [$this->context, 'todo', new BoardQuery(limit: 1, cursor: $cursor, search: 'other')],
            [$this->context, 'todo', new BoardQuery(limit: 1, cursor: $cursor, direction: 'desc')],
            [$this->context, 'todo', new BoardQuery(limit: 1, cursor: $cursor, filters: ['priority' => 'high'])],
            [$this->context, 'todo', new BoardQuery(limit: 1, cursor: $cursor.'x')],
        ];
        foreach ($attempts as [$context, $stage, $query]) {
            try { $this->provider->stageCards($context, $stage, $query); self::fail('Cursor accepted across security/query boundary.'); }
            catch (InvalidArgumentException $e) { self::assertStringContainsString('Cursor', $e->getMessage()); }
        }
    }

    public function test_search_and_filters_are_applied_before_counts_and_pages(): void
    {
        $this->task(['title' => 'قرارداد جدید', 'priority' => 'high']);
        $this->task(['title' => 'قرارداد قدیم', 'priority' => 'low']);
        $this->task(['title' => 'Other', 'priority' => 'high']);
        $query = new BoardQuery(search: 'قرارداد', filters: ['priority' => 'high']);
        self::assertCount(1, $this->provider->listCards($this->context, $query)->items);
        self::assertSame(1, $this->provider->counts($this->context, $query)->boardTotal);
        self::assertSame(1, $this->provider->counts($this->context, new BoardQuery(search: 'other'))->boardTotal);
        self::assertSame(0, $this->provider->counts($this->context, new BoardQuery(filters: ['assignee' => 'bob', 'status' => 'todo']))->boardTotal);
    }

    public function test_unknown_filter_is_rejected_instead_of_becoming_sql(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->provider->listCards($this->context, new BoardQuery(filters: ['owner_id' => 'bob']));
    }

    public function test_missing_visibility_is_deny_by_default(): void
    {
        $this->task();
        $definition = KanbanModelDefinition::make('tasks', EnterpriseTask::class);
        self::assertSame(0, $definition->query($this->context)->count());
        self::assertFalse($definition->allows('move', $this->task(), $this->context));
    }

    public function test_replacing_the_visibility_builder_is_rejected_instead_of_reading_unscoped_rows(): void
    {
        $this->task(['owner_id' => 'bob']);
        $definition = KanbanModelDefinition::make('tasks', EnterpriseTask::class)->visibleQuery(fn () => EnterpriseTask::query()->where('owner_id', 'alice'));
        $this->expectException(InvalidArgumentException::class);
        $definition->query($this->context)->get();
    }

    public function test_mixed_board_requires_complete_explicit_stage_mapping(): void
    {
        $this->expectException(\Karnoweb\Kanban\Exceptions\InvalidBoardDefinition::class);
        (new BoardBuilder)->key('invalid')->board(new BoardData('invalid', 'Invalid', 'a'))->sources(['tasks', 'tickets'])->build();
    }

    public function test_registry_rejects_models_without_trait_and_duplicate_sources(): void
    {
        try { (new KanbanModelRegistry)->register('plain', Model::class); self::fail(); }
        catch (InvalidArgumentException) { self::assertTrue(true); }
        $this->expectException(InvalidArgumentException::class);
        app(KanbanModelRegistry::class)->register('tasks', EnterpriseTask::class);
    }

    public function test_definitions_are_rebuilt_and_never_shared_between_requests(): void
    {
        $registry = app(KanbanModelRegistry::class);
        $first = $registry->definition('tasks'); $first->stageDefinitions = [];
        self::assertCount(3, $registry->definition('tasks')->stageDefinitions);
    }

    public function test_valid_move_persists_version_and_operation_status(): void
    {
        $this->task();
        $result = $this->command(ActionType::MoveCard, ['cardId' => 'tasks:1', 'fromStageId' => 'todo', 'toStageId' => 'doing'], operation: 'move-1');
        self::assertSame(ActionStatus::SUCCESS, $result->status);
        self::assertSame('doing', EnterpriseTask::find(1)->status);
        self::assertSame('2', $result->newVersion);
        self::assertSame(ActionStatus::SUCCESS, $this->provider->operationStatus($this->context, 'move-1')->status);
    }

    public function test_duplicate_operations_do_not_write_twice(): void
    {
        $this->task();
        $payload = ['cardId' => 'tasks:1', 'fromStageId' => 'todo', 'toStageId' => 'doing'];
        self::assertSame(ActionStatus::SUCCESS, $this->command(ActionType::MoveCard, $payload, operation: 'same')->status);
        self::assertSame(ActionStatus::SUCCESS, $this->command(ActionType::MoveCard, $payload, operation: 'same')->status);
        self::assertSame(2, EnterpriseTask::find(1)->version);
        self::assertNull($this->provider->operationStatus(new BoardContext('tasks', 'workspace-a', 'bob', 'b'), 'same'));
    }

    public function test_reused_operation_id_for_different_payload_is_a_conflict(): void
    {
        $this->task();
        $this->command(ActionType::UpdateCard, ['cardId' => 'tasks:1', 'title' => 'First'], operation: 'same');
        self::assertSame(ActionStatus::CONFLICT, $this->command(ActionType::UpdateCard, ['cardId' => 'tasks:1', 'title' => 'Second'], operation: 'same')->status);
        self::assertSame('First', EnterpriseTask::find(1)->title);
    }

    public function test_stale_and_missing_versions_cannot_overwrite(): void
    {
        $this->task(['version' => 5]);
        foreach (['1', null] as $version) {
            self::assertSame(ActionStatus::CONFLICT, $this->command(ActionType::UpdateCard, ['cardId' => 'tasks:1', 'title' => 'Tampered'], $version)->status);
        }
        self::assertSame('Test', EnterpriseTask::find(1)->title);
    }

    public function test_domain_transition_matrix_cannot_be_bypassed(): void
    {
        $this->task();
        $result = $this->command(ActionType::MoveCard, ['cardId' => 'tasks:1', 'fromStageId' => 'todo', 'toStageId' => 'done']);
        self::assertSame(ActionStatus::FORBIDDEN, $result->status);
        self::assertSame('todo', EnterpriseTask::find(1)->status);
    }

    public function test_foreign_source_and_foreign_owner_writes_are_not_found(): void
    {
        $this->task(['owner_id' => 'bob']);
        foreach (['tasks:1', 'tickets:1', 'tasks:999'] as $id) {
            self::assertSame(ActionStatus::NOT_FOUND, $this->command(ActionType::UpdateCard, ['cardId' => $id, 'title' => 'Tampered'])->status);
        }
    }

    public function test_strict_capacity_rejects_the_next_move(): void
    {
        $this->task(['status' => 'doing']); $this->task(['status' => 'doing']); $this->task();
        self::assertSame(ActionStatus::WIP_LIMIT, $this->command(ActionType::MoveCard, ['cardId' => 'tasks:3', 'fromStageId' => 'todo', 'toStageId' => 'doing'])->status);
        self::assertSame('todo', EnterpriseTask::find(3)->status);
    }

    public function test_create_resolves_single_source_and_domain_hook_assigns_owner(): void
    {
        $result = $this->command(ActionType::CreateCard, ['title' => 'Created', 'stageId' => 'todo']);
        self::assertSame(ActionStatus::SUCCESS, $result->status);
        self::assertSame('alice', EnterpriseTask::first()->owner_id);
    }

    public function test_multi_source_create_requires_source(): void
    {
        $this->context = new BoardContext('mixed', 'workspace-a', 'alice', 'mixed');
        $result = $this->command(ActionType::CreateCard, ['title' => 'Created', 'stageId' => 'todo']);
        self::assertSame('source_required', $result->messageCode);
        self::assertSame(0, EnterpriseTask::count());
    }

    public function test_invalid_create_rolls_back(): void
    {
        self::assertSame(ActionStatus::VALIDATION_ERROR, $this->command(ActionType::CreateCard, ['title' => '', 'stageId' => 'todo'])->status);
        self::assertSame(0, EnterpriseTask::count());
    }

    public function test_archive_and_restore_share_the_same_visibility_scope(): void
    {
        $this->task();
        self::assertSame(ActionStatus::SUCCESS, $this->command(ActionType::ArchiveCard, ['cardId' => 'tasks:1'])->status);
        self::assertNull($this->provider->card($this->context, 'tasks:1'));
        self::assertSame(1, $this->provider->archives($this->context, new BoardQuery)->totalCount);
        self::assertSame(ActionStatus::SUCCESS, $this->command(ActionType::RestoreCard, ['cardId' => 'tasks:1'], '2')->status);
        self::assertSame(0, $this->provider->archives($this->context, new BoardQuery)->totalCount);
    }

    public function test_reorder_updates_only_the_moving_record(): void
    {
        $this->task(['rank' => 1024]); $this->task(['rank' => 2048]); $this->task(['rank' => 3072]);
        DB::enableQueryLog(); DB::flushQueryLog();
        $result = $this->command(ActionType::ReorderCard, ['cardId' => 'tasks:3', 'fromStageId' => 'todo', 'toStageId' => 'todo', 'afterCardId' => 'tasks:1', 'beforeCardId' => 'tasks:2']);
        self::assertSame(ActionStatus::SUCCESS, $result->status);
        self::assertSame(1536, EnterpriseTask::find(3)->rank);
        self::assertCount(1, array_filter(DB::getQueryLog(), fn ($q) => str_starts_with($q['query'], 'update')));
    }

    public function test_mixed_manual_order_requires_shared_adapter(): void
    {
        $this->task(); $this->context = new BoardContext('mixed', 'workspace-a', 'alice', 'mixed');
        $result = $this->command(ActionType::ReorderCard, ['cardId' => 'tasks:1', 'fromStageId' => 'todo', 'toStageId' => 'todo']);
        self::assertSame(ActionStatus::UNSUPPORTED, $result->status);
        self::assertSame('rank_storage_required', $result->messageCode);
    }

    public function test_settings_security_group_and_appearance_aliases_are_applied(): void
    {
        $settings = KanbanSettings::fromArray(['security' => ['bulk_ids' => 7, 'metadata_bytes' => 1024, 'rate_limit_per_minute' => 5], 'appearance' => ['column_width' => '20rem', 'density' => 'compact']]);
        self::assertSame(7, $settings->security->bulkIds);
        self::assertSame(1024, $settings->security->metadataBytes);
        self::assertSame(5, $settings->security->rateLimitPerMinute);
        self::assertSame('20rem', $settings->views->columnWidth);
        self::assertSame('compact', $settings->views->density);
        self::assertSame(100, KanbanSettings::fromArray(['security' => ['bulk_ids' => 999]])->security->bulkIds);
    }
}

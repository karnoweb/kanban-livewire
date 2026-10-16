<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Illuminate\Support\Facades\{DB, Event};
use Karnoweb\Kanban\Data\{BoardContext, BoardQuery, KanbanSettings};
use Karnoweb\Kanban\Enums\{ActionStatus, ActionType};
use Karnoweb\Kanban\Events\KanbanModelChanged;
use Karnoweb\Kanban\Livewire\Board;
use Karnoweb\Kanban\Tests\Fixtures\{EnterpriseTask, EnterpriseTestCase};
use Livewire\Livewire;

final class EnterpriseActionsAndViewsTest extends EnterpriseTestCase
{
    public function test_dense_rank_collision_rebalances_only_local_neighbors(): void
    {
        $this->task(['rank' => 1]); $this->task(['rank' => 2]); $this->task(['rank' => 2000]);
        $result = $this->command(ActionType::ReorderCard, ['cardId' => 'tasks:3', 'fromStageId' => 'todo', 'toStageId' => 'todo', 'afterCardId' => 'tasks:1', 'beforeCardId' => 'tasks:2']);
        self::assertSame(ActionStatus::SUCCESS, $result->status);
        self::assertSame([1, 3, 2], EnterpriseTask::orderBy('rank')->pluck('id')->all());
        self::assertSame(3, EnterpriseTask::distinct()->count('rank'));
    }

    public function test_cross_source_anchor_does_not_modify_a_record(): void
    {
        $this->task();
        $result = $this->command(ActionType::ReorderCard, ['cardId' => 'tasks:1', 'fromStageId' => 'todo', 'toStageId' => 'todo', 'beforeCardId' => 'tickets:2']);
        self::assertSame(ActionStatus::VALIDATION_ERROR, $result->status);
        self::assertSame(1, EnterpriseTask::find(1)->version);
    }

    public function test_equal_rank_anchors_rebalance_without_reversing_the_requested_neighbors(): void
    {
        $this->task(['rank' => 1]); $this->task(['rank' => 1]); $this->task(['rank' => 2048]);
        $result = $this->command(ActionType::ReorderCard, ['cardId' => 'tasks:3', 'fromStageId' => 'todo', 'toStageId' => 'todo', 'afterCardId' => 'tasks:1', 'beforeCardId' => 'tasks:2']);
        self::assertSame(ActionStatus::SUCCESS, $result->status);
        self::assertSame([1, 3, 2], EnterpriseTask::orderBy('rank')->pluck('id')->all());
    }

    public function test_after_commit_event_is_dispatched_once_at_transaction_level_zero(): void
    {
        $this->task(); $events = [];
        Event::listen(KanbanModelChanged::class, function ($event) use (&$events): void {
            $events[] = [$event, DB::transactionLevel()];
        });
        $payload = ['cardId' => 'tasks:1', 'title' => 'Changed'];
        $this->command(ActionType::UpdateCard, $payload, operation: 'event-one');
        $this->command(ActionType::UpdateCard, $payload, operation: 'event-one');
        self::assertCount(1, $events);
        self::assertSame(0, $events[0][1]);
        self::assertSame('tasks:1', $events[0][0]->cardId);
    }

    public function test_rollback_emits_no_domain_event(): void
    {
        Event::fake([KanbanModelChanged::class]);
        $this->command(ActionType::CreateCard, ['title' => '', 'stageId' => 'todo']);
        Event::assertNotDispatched(KanbanModelChanged::class);
    }

    public function test_partial_bulk_reports_unauthorized_items_without_writing_them(): void
    {
        $this->task(); $this->task(['owner_id' => 'bob']);
        $result = $this->command(ActionType::ExecuteBulkAction, ['ids' => 'tasks:1,tasks:2,tickets:1', 'mode' => 'partial', 'action' => 'archive', 'expectedVersions' => 'tasks:1:1,tasks:2:1']);
        self::assertSame(1, $result->successCount);
        self::assertSame(2, $result->failureCount);
        self::assertNotNull(EnterpriseTask::find(1)->archived_at);
        self::assertNull(EnterpriseTask::find(2)->archived_at);
    }

    public function test_atomic_bulk_rolls_back_all_items_on_one_failure(): void
    {
        $this->task(); $this->task(['owner_id' => 'bob']);
        Event::fake([KanbanModelChanged::class]);
        $result = $this->command(ActionType::ExecuteBulkAction, ['ids' => 'tasks:1,tasks:2', 'mode' => 'all-or-nothing', 'action' => 'archive', 'expectedVersions' => 'tasks:1:1,tasks:2:1']);
        self::assertSame(ActionStatus::NOT_FOUND, $result->status);
        self::assertNull(EnterpriseTask::find(1)->archived_at);
        Event::assertNotDispatched(KanbanModelChanged::class);
    }

    public function test_atomic_bulk_commits_all_valid_items(): void
    {
        $this->task(); $this->task();
        $result = $this->command(ActionType::ExecuteBulkAction, ['ids' => 'tasks:1,tasks:2', 'mode' => 'all-or-nothing', 'action' => 'archive', 'expectedVersions' => 'tasks:1:1,tasks:2:1']);
        self::assertSame(ActionStatus::SUCCESS, $result->status);
        self::assertSame(2, $result->successCount);
        self::assertSame(2, EnterpriseTask::whereNotNull('archived_at')->count());
    }

    public function test_confirmation_note_is_required_again_by_the_handler(): void
    {
        config(['kanban_test.settings' => ['interaction' => ['confirmation_rules' => ['todo>doing' => ['mode' => 'form', 'fields' => ['note' => ['required' => true]]]]]]]);
        $this->task();
        $payload = ['cardId' => 'tasks:1', 'fromStageId' => 'todo', 'toStageId' => 'doing'];
        self::assertSame(ActionStatus::VALIDATION_ERROR, $this->command(ActionType::MoveCard, $payload)->status);
        self::assertSame(ActionStatus::SUCCESS, $this->command(ActionType::MoveCard, $payload + ['formData' => ['note' => 'Approved']])->status);
    }

    public function test_custom_actions_use_declared_callbacks_and_structured_form_data(): void
    {
        config(['kanban_test.custom' => true]);
        $this->task();
        $result = $this->command(ActionType::ExecuteCardAction, ['cardId' => 'tasks:1', 'actionId' => 'approve', 'formData' => ['note' => 'OK']]);
        self::assertSame(ActionStatus::SUCCESS, $result->status);
        self::assertSame('Approved: OK', EnterpriseTask::find(1)->title);
        self::assertSame(ActionStatus::UNSUPPORTED, $this->command(ActionType::ExecuteCardAction, ['cardId' => 'tasks:1', 'actionId' => 'delete'], '2')->status);
    }

    public function test_cached_counts_are_invalidated_after_writes_and_scoped_by_actor(): void
    {
        config(['kanban_test.settings' => ['cache' => ['enabled' => true, 'ttl' => 30]]]);
        $this->task(); $query = new BoardQuery;
        self::assertSame(1, $this->provider->counts($this->context, $query)->boardTotal);
        self::assertSame(0, $this->provider->counts(new BoardContext('tasks', 'workspace-a', 'bob', 'b'), $query)->boardTotal);
        $this->command(ActionType::ArchiveCard, ['cardId' => 'tasks:1']);
        self::assertSame(0, $this->provider->counts($this->context, $query)->boardTotal);
    }

    public function test_swimlane_groups_and_counts_use_visibility_queries(): void
    {
        config(['kanban_test.swimlanes' => true]);
        $this->task(['team' => 'A']); $this->task(['team' => 'B']); $this->task(['owner_id' => 'bob', 'team' => 'Private']);
        $groups = $this->provider->groups($this->context, new BoardQuery);
        self::assertSame(['A', 'B'], array_column($groups['items'], 'id'));
        self::assertSame(1, $this->provider->counts($this->context, new BoardQuery(filters: ['group' => 'A']))->boardTotal);
        self::assertCount(1, $this->provider->listCards($this->context, new BoardQuery(filters: ['group' => 'A']))->items);
    }

    public function test_swimlane_group_limit_is_applied_without_loading_all_groups(): void
    {
        config(['kanban_test.swimlanes' => true, 'kanban.swimlanes.max_groups' => 2]);
        foreach (['A', 'B', 'C'] as $team) { $this->task(['team' => $team]); }
        $groups = $this->provider->groups($this->context, new BoardQuery);
        self::assertCount(2, $groups['items']); self::assertTrue($groups['hasMore']);
    }

    public function test_model_adapter_can_be_disabled_fail_closed(): void
    {
        config(['kanban_test.settings' => ['eloquent' => ['enabled' => false]]]);
        $this->task();
        self::assertSame([], $this->provider->listCards($this->context, new BoardQuery)->items);
        self::assertNull($this->provider->card($this->context, 'tasks:1'));
        self::assertSame(ActionStatus::FORBIDDEN, $this->command(ActionType::ArchiveCard, ['cardId' => 'tasks:1'])->status);
    }

    public function test_unsafe_model_settings_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        KanbanSettings::fromArray(['eloquent' => ['cursor' => ['signed' => false]]]);
    }

    private function board(): \Livewire\Features\SupportTesting\Testable
    {
        $this->scope->actor = 'alice';

        return Livewire::test(Board::class, ['boardKey' => 'tasks']);
    }

    public function test_board_renders_model_cards_and_real_metrics(): void
    {
        $this->task(['title' => 'Visible task', 'amount' => 42]);
        $this->board()->assertSee('Visible task')->assertSee('tasks.amount')->assertSee('42');
    }

    public function test_list_and_compact_use_the_same_provider(): void
    {
        config(['kanban_test.settings' => ['views' => ['allowed' => ['board', 'list', 'compact']]]]);
        $this->task();
        $this->board()->call('switchView', 'list')->assertSet('view', 'list')->assertSee('Test')
            ->call('switchView', 'compact')->assertSet('view', 'compact')->assertSee('Test');
    }

    public function test_source_filter_is_hidden_for_a_single_source(): void
    {
        $this->task();
        $this->board()->assertDontSeeHtml('data-kb-part="filter-source"');
    }

    public function test_collapse_preference_survives_remount(): void
    {
        $this->task();
        $this->board()->call('toggleStage', 'todo')->assertSet('collapsedStages', ['todo']);
        $this->board()->assertSet('collapsedStages', ['todo']);
    }

    public function test_keyboard_move_includes_the_loaded_version(): void
    {
        $this->task();
        $this->board()->call('moveCardToStage', 'tasks:1', 'doing')->assertSet('notice.status', 'success');
        self::assertSame('doing', EnterpriseTask::find(1)->status);
    }

    public function test_disabled_feature_is_enforced_by_direct_handler_calls(): void
    {
        config(['kanban_test.settings' => ['features' => ['archive_card' => false]]]);
        $this->task();
        self::assertSame(ActionStatus::FORBIDDEN, $this->command(ActionType::ArchiveCard, ['cardId' => 'tasks:1'])->status);
        self::assertNull(EnterpriseTask::find(1)->archived_at);
    }

    public function test_loaded_cards_remain_reachable_in_bounded_render_windows(): void
    {
        foreach (range(1, 75) as $i) { $this->task(['title' => 'Card '.$i]); }
        $board = $this->board()->call('loadMore', 'todo')->call('loadMore', 'todo')
            ->assertSet('columns.0.loadedCount', 75)->assertSet('columns.0.renderedCount', 50)
            ->assertSeeHtml('data-kb-part="window-next"');
        $board->call('shiftStageWindow', 'todo', 1)->assertSet('columns.0.windowStart', 25)->assertSee('Card 75')
            ->call('shiftStageWindow', 'todo', -1)->assertSet('columns.0.windowStart', 0)->assertSee('Card 1');
    }

    public function test_metrics_use_all_matching_rows_instead_of_only_loaded_cards(): void
    {
        foreach (range(1, 26) as $i) { $this->task(['amount' => 1]); }
        self::assertCount(25, $this->provider->stageCards($this->context, 'todo', new BoardQuery)->items);
        self::assertSame(['tasks.amount' => 26.0], $this->provider->metrics($this->context, 'todo', new BoardQuery));
    }

    public function test_edit_form_updates_through_the_versioned_handler_and_cancel_has_no_effect(): void
    {
        $this->task();
        $board = $this->board()->call('editCard', 'tasks:1')->assertSet('editingVersion', '1')
            ->set('cardForm.title', 'Edited title')->call('submitCardForm')->assertSet('notice.status', 'success');
        self::assertSame('Edited title', EnterpriseTask::find(1)->title);
        self::assertSame(2, EnterpriseTask::find(1)->version);
        $board->call('editCard', 'tasks:1')->set('cardForm.title', 'Discarded')->call('cancelCardForm');
        self::assertSame('Edited title', EnterpriseTask::find(1)->title);
    }

    public function test_custom_action_revalidates_its_domain_form(): void
    {
        config(['kanban_test.custom' => true]); $this->task();
        $result = $this->command(ActionType::ExecuteCardAction, ['cardId' => 'tasks:1', 'actionId' => 'approve', 'formData' => []]);
        self::assertSame(ActionStatus::VALIDATION_ERROR, $result->status);
        self::assertArrayHasKey('note', $result->fieldErrors);
        self::assertSame('Test', EnterpriseTask::find(1)->title);
    }

    public function test_process_local_array_cache_is_rejected_outside_unit_tests(): void
    {
        $this->task();
        $this->app->detectEnvironment(fn () => 'production');
        $result = $this->command(ActionType::ArchiveCard, ['cardId' => 'tasks:1']);
        self::assertSame('shared_cache_required', $result->messageCode);
        self::assertNull(EnterpriseTask::find(1)->archived_at);
    }
}

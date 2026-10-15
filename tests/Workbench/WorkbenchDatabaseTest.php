<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Workbench;

use Illuminate\Support\Facades\Event;
use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Enums\ActionStatus;
use Karnoweb\Kanban\Livewire\Board;
use Karnoweb\Kanban\Realtime\ChannelAuthorizer;
use Karnoweb\Kanban\Realtime\KanbanBroadcastEvent;
use Karnoweb\Kanban\Realtime\RealtimePublisher;
use Karnoweb\Kanban\Support\ScopeHasher;
use Livewire\Livewire;
use Workbench\App\Kanban\TaskHandler;
use Workbench\App\Kanban\TaskProvider;
use Workbench\App\Models\DemoCard;
use Workbench\App\Models\DemoOperation;
use Workbench\App\Models\DemoStage;

final class WorkbenchDatabaseTest extends WorkbenchTestCase
{
    private function context(string $tenant = 'tenant-a', string $user = '1'): BoardContext
    {
        return new BoardContext('tasks.board', $tenant, $user, 'test');
    }

    private function exec(string $type, array $payload, ?string $operation = null, string $tenant = 'tenant-a', ?string $version = null): \Karnoweb\Kanban\Data\ActionResult
    {
        return app(TaskHandler::class)->execute(
            ActionCommand::allowlisted($operation ?? 'op-'.bin2hex(random_bytes(4)), $type, $payload, $version),
            $this->context($tenant),
        );
    }

    private function stageOf(string $card, string $tenant = 'tenant-a'): string
    {
        return (string) DemoCard::query()->where('scope_key', $tenant)->where('board_key', 'tasks.board')->where('public_id', $card)->value('stage_id');
    }

    public function test_board_renders_only_the_resolved_tenant(): void
    {
        $this->loginAs('alice');
        Livewire::test(Board::class, ['boardKey' => 'tasks.board'])
            ->assertSee('tenant-a tasks.board card 1')
            ->assertDontSee('tenant-b tasks.board card 1');

        $this->loginAs('carol');
        Livewire::test(Board::class, ['boardKey' => 'tasks.board'])
            ->assertSee('tenant-b tasks.board card 1')
            ->assertDontSee('tenant-a tasks.board card 1');
    }

    public function test_move_persists_bumps_version_and_publishes_after_commit(): void
    {
        $seen = [];
        Event::listen(KanbanBroadcastEvent::class, function (KanbanBroadcastEvent $event) use (&$seen): void {
            $seen[] = [
                'payload' => $event->payload,
                'level' => \Illuminate\Support\Facades\DB::transactionLevel(),
                'stage' => $this->stageOf('101'),
            ];
        });
        $result = $this->exec('moveCard', ['cardId' => '101', 'fromStageId' => 'todo', 'toStageId' => 'done']);

        $this->assertSame(ActionStatus::SUCCESS, $result->status);
        $this->assertSame('done', $this->stageOf('101'));
        $this->assertSame('2', $result->newVersion);
        $this->assertSame('todo', $this->stageOf('101', 'tenant-b'));
        $this->assertCount(1, $seen);
        $this->assertSame(0, $seen[0]['level'], 'event must be delivered after commit');
        $this->assertSame('done', $seen[0]['stage']);
        $this->assertSame(['schemaVersion', 'eventId', 'operationId', 'eventType', 'scopeHash', 'boardId', 'entityId', 'entityVersion', 'occurredAt'], array_keys($seen[0]['payload']));
        $this->assertSame('CardMoved', $seen[0]['payload']['eventType']);
        $this->assertSame('2', $seen[0]['payload']['entityVersion']);
    }

    public function test_same_operation_id_is_idempotent(): void
    {
        $first = $this->exec('moveCard', ['cardId' => '101', 'fromStageId' => 'todo', 'toStageId' => 'done'], 'op-same');
        $second = $this->exec('moveCard', ['cardId' => '101', 'fromStageId' => 'todo', 'toStageId' => 'done'], 'op-same');

        $this->assertSame(ActionStatus::SUCCESS, $second->status);
        $this->assertSame($first->newVersion, $second->newVersion);
        $this->assertSame('2', (string) DemoCard::query()->where('scope_key', 'tenant-a')->where('board_key', 'tasks.board')->where('public_id', '101')->value('version'));
        $this->assertSame(1, DemoOperation::query()->where('operation_id', 'op-same')->count());

        $status = app(TaskProvider::class)->operationStatus($this->context(), 'op-same');
        $this->assertSame(ActionStatus::SUCCESS, $status?->status);
    }

    public function test_version_conflict_is_reported_and_nothing_changes(): void
    {
        $result = $this->exec('moveCard', ['cardId' => '101', 'fromStageId' => 'todo', 'toStageId' => 'done'], null, 'tenant-a', '9');

        $this->assertSame(ActionStatus::CONFLICT, $result->status);
        $this->assertSame('todo', $this->stageOf('101'));
    }

    public function test_strict_wip_blocks_the_fourth_card(): void
    {
        foreach (['101', '102', '103'] as $id) {
            $this->assertSame(ActionStatus::SUCCESS, $this->exec('moveCard', ['cardId' => $id, 'fromStageId' => 'todo', 'toStageId' => 'doing'])->status);
        }

        $created = $this->exec('createCard', ['title' => 'extra', 'stageId' => 'todo']);
        $this->assertSame(ActionStatus::SUCCESS, $created->status);
        $blocked = $this->exec('moveCard', ['cardId' => $created->affectedIds[0], 'fromStageId' => 'todo', 'toStageId' => 'doing']);

        $this->assertSame(ActionStatus::WIP_LIMIT, $blocked->status);
        $this->assertSame('todo', $this->stageOf($created->affectedIds[0]));
        $this->assertSame(3, DemoCard::query()->where('scope_key', 'tenant-a')->where('board_key', 'tasks.board')->where('stage_id', 'doing')->count());
    }

    public function test_reorder_uses_anchors_inside_a_stage(): void
    {
        $this->exec('reorderCard', ['cardId' => '103', 'fromStageId' => 'todo', 'toStageId' => 'todo', 'beforeCardId' => '101']);
        $order = DemoCard::query()->where('scope_key', 'tenant-a')->where('board_key', 'tasks.board')->where('stage_id', 'todo')->orderBy('manual_order')->pluck('public_id')->all();
        $this->assertSame(['103', '101', '102'], $order);

        $this->exec('reorderCard', ['cardId' => '103', 'fromStageId' => 'todo', 'toStageId' => 'todo', 'afterCardId' => '102']);
        $order = DemoCard::query()->where('scope_key', 'tenant-a')->where('board_key', 'tasks.board')->where('stage_id', 'todo')->orderBy('manual_order')->pluck('public_id')->all();
        $this->assertSame(['101', '102', '103'], $order);
    }

    public function test_card_and_stage_crud_archive_and_restore(): void
    {
        $created = $this->exec('createCard', ['title' => 'Fresh', 'stageId' => 'todo', 'priority' => 'high']);
        $id = $created->affectedIds[0];
        $this->assertSame('Fresh', DemoCard::query()->where('public_id', $id)->where('scope_key', 'tenant-a')->value('title'));

        $this->assertSame(ActionStatus::VALIDATION_ERROR, $this->exec('createCard', ['title' => '  ', 'stageId' => 'todo'])->status);
        $this->assertSame(ActionStatus::SUCCESS, $this->exec('updateCard', ['cardId' => $id, 'title' => 'Renamed'])->status);
        $this->assertSame(ActionStatus::SUCCESS, $this->exec('archiveCard', ['cardId' => $id])->status);
        $this->assertTrue((bool) DemoCard::query()->where('public_id', $id)->where('scope_key', 'tenant-a')->value('archived'));
        $this->assertSame(ActionStatus::SUCCESS, $this->exec('restoreCard', ['cardId' => $id, 'stageId' => 'todo'])->status);
        $this->assertFalse((bool) DemoCard::query()->where('public_id', $id)->where('scope_key', 'tenant-a')->value('archived'));

        $stage = $this->exec('createStage', ['title' => 'Later', 'color' => '#112233']);
        $stageId = $stage->affectedIds[0];
        $this->assertSame(ActionStatus::SUCCESS, $this->exec('updateStage', ['stageId' => $stageId, 'title' => 'Soon'])->status);
        $this->assertSame('Soon', DemoStage::query()->where('public_id', $stageId)->where('scope_key', 'tenant-a')->value('title'));
        $this->assertSame(ActionStatus::DOMAIN_REJECTED, $this->exec('archiveStage', ['stageId' => 'todo'])->status);
        $this->assertSame(ActionStatus::SUCCESS, $this->exec('archiveStage', ['stageId' => $stageId])->status);
        $this->assertSame(ActionStatus::SUCCESS, $this->exec('reorderStage', ['stageIds' => 'done,doing,todo'])->status);
        $this->assertSame(['done', 'doing', 'todo'], DemoStage::query()->where('scope_key', 'tenant-a')->where('board_key', 'tasks.board')->where('archived', false)->orderBy('sort_order')->pluck('public_id')->all());
    }

    public function test_bulk_partial_and_atomic(): void
    {
        $partial = $this->exec('executeBulkAction', ['ids' => '101,102,missing', 'mode' => 'partial']);
        $this->assertSame(ActionStatus::DOMAIN_REJECTED, $partial->status);
        $this->assertSame(2, $partial->successCount);
        $this->assertSame(1, $partial->failureCount);

        $atomic = $this->exec('executeBulkAction', ['ids' => '103,missing', 'mode' => 'all-or-nothing']);
        $this->assertSame(ActionStatus::DOMAIN_REJECTED, $atomic->status);
        $this->assertSame(0, $atomic->successCount);
        $this->assertFalse((bool) DemoCard::query()->where('public_id', '103')->where('scope_key', 'tenant-a')->where('board_key', 'tasks.board')->value('archived'));
    }

    public function test_same_card_id_in_two_tenants_never_crosses(): void
    {
        $this->exec('moveCard', ['cardId' => '101', 'fromStageId' => 'todo', 'toStageId' => 'done'], null, 'tenant-b');

        $this->assertSame('done', $this->stageOf('101', 'tenant-b'));
        $this->assertSame('todo', $this->stageOf('101', 'tenant-a'));
    }

    public function test_livewire_component_moves_through_the_database(): void
    {
        $this->loginAs('alice');
        Livewire::test(Board::class, ['boardKey' => 'tasks.board'])
            ->call('requestMove', '101', 'todo', 'done', null, null, null, '1');

        $this->assertSame('done', $this->stageOf('101'));
        $this->assertSame('todo', $this->stageOf('101', 'tenant-b'));
    }

    public function test_channel_authorizer_allows_only_the_resolved_scope(): void
    {
        $this->loginAs('alice');
        $hasher = app(ScopeHasher::class);
        $authorizer = app(ChannelAuthorizer::class);
        $channels = app(\Karnoweb\Kanban\Realtime\ChannelNameFactory::class);
        $enc = $channels->encode('tasks.board');

        $this->assertTrue($authorizer->allows($hasher->hash('tenant-a'), $enc));
        $this->assertFalse($authorizer->allows($hasher->hash('tenant-b'), $enc));
        $this->assertFalse($authorizer->allows(str_repeat('0', 64), $enc));
        // sanitised/legacy name, unregistered board and garbage never authorise
        $this->assertFalse($authorizer->allows($hasher->hash('tenant-a'), 'tasks-board'));
        $this->assertFalse($authorizer->allows($hasher->hash('tenant-a'), $channels->encode('unknown.board')));
        $this->assertFalse($authorizer->allows($hasher->hash('tenant-a'), '!!'));

        auth()->logout();
        $this->assertFalse($authorizer->allows($hasher->hash('tenant-a'), $enc));
    }

    public function test_publisher_builds_minimal_payload(): void
    {
        Event::fake([KanbanBroadcastEvent::class]);
        $event = app(RealtimePublisher::class)->publish($this->context(), 'BoardInvalidated');

        $this->assertSame('BoardInvalidated', $event->eventType);
        Event::assertDispatched(KanbanBroadcastEvent::class, static fn (KanbanBroadcastEvent $e): bool => str_starts_with($e->channel, 'private-kanban.') && ! array_key_exists('title', $e->payload));
    }
}

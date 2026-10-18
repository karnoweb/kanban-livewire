<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Karnoweb\Kanban\Enums\{ActionStatus, ActionType};
use Karnoweb\Kanban\Events\KanbanModelChanged;
use Karnoweb\Kanban\Tests\Fixtures\{EnterpriseTask, EnterpriseTestCase};

final class DeleteArchivedCardTest extends EnterpriseTestCase
{
    public function test_delete_is_opt_in_and_cannot_delete_active_or_foreign_cards(): void
    {
        $archived = $this->task(['archived_at' => now()]);
        self::assertSame(ActionStatus::FORBIDDEN, $this->command(ActionType::DeleteCard, ['cardId' => 'tasks:1'])->status);
        self::assertNotNull($archived->fresh());
        config(['kanban_test.settings.features.delete_card' => true]);
        $this->task();
        $this->task(['owner_id' => 'bob', 'archived_at' => now()]);
        self::assertSame(ActionStatus::NOT_FOUND, $this->command(ActionType::DeleteCard, ['cardId' => 'tasks:2'])->status);
        self::assertSame(ActionStatus::NOT_FOUND, $this->command(ActionType::DeleteCard, ['cardId' => 'tasks:3'])->status);
        self::assertSame(3, EnterpriseTask::count());
    }

    public function test_stale_delete_does_not_remove_the_card(): void
    {
        config(['kanban_test.settings.features.delete_card' => true]);
        $this->task(['archived_at' => now(), 'version' => 3]);
        self::assertSame(ActionStatus::CONFLICT, $this->command(ActionType::DeleteCard, ['cardId' => 'tasks:1'], version: '2')->status);
        self::assertSame(1, EnterpriseTask::count());
    }

    public function test_delete_is_idempotent_and_emits_one_committed_event(): void
    {
        config(['kanban_test.settings.features.delete_card' => true]);
        $this->task(['archived_at' => now()]);
        $events = [];
        Event::listen(KanbanModelChanged::class, function ($event) use (&$events): void {
            $events[] = [$event, \Illuminate\Support\Facades\DB::transactionLevel()];
        });
        $first = $this->command(ActionType::DeleteCard, ['cardId' => 'tasks:1'], operation: 'delete-once');
        $again = $this->command(ActionType::DeleteCard, ['cardId' => 'tasks:1'], operation: 'delete-once');
        self::assertSame(ActionStatus::SUCCESS, $first->status);
        self::assertSame(ActionStatus::SUCCESS, $again->status);
        self::assertSame(0, EnterpriseTask::count());
        self::assertCount(1, $events);
        self::assertSame(0, $events[0][1]);
    }
}

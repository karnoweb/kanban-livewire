<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Exceptions\UnknownBoard;
use Karnoweb\Kanban\Support\BoardRegistry;
use Karnoweb\Kanban\Tests\Fixtures\MemoryStore;
use Karnoweb\Kanban\Tests\Fixtures\SampleDataFactory;
use Karnoweb\Kanban\Tests\TestCase;

final class BoardRegistryTest extends TestCase
{
    public function test_two_boards_resolve_independently_on_later_requests(): void
    {
        MemoryStore::seed('tenant-a', 'crm.deals', SampleDataFactory::standard());
        MemoryStore::seed('tenant-a', 'tasks.board', array_replace(SampleDataFactory::standard(), ['title' => 'Tasks']));
        $this->registerBoard('crm.deals');
        $this->registerBoard('tasks.board');
        $registry = $this->app->make(BoardRegistry::class);
        $crm = $registry->resolve('crm.deals', new BoardContext('crm.deals', 'tenant-a', 'user-a', 'i1'));
        $tasks = $registry->resolve('tasks.board', new BoardContext('tasks.board', 'tenant-a', 'user-a', 'i2'));

        $this->assertSame('Sample', $crm->board->title);
        $this->assertSame('Tasks', $tasks->board->title);
        $this->assertNotSame($crm, $registry->resolve('crm.deals', new BoardContext('crm.deals', 'tenant-a', 'user-a', 'i3')));
    }

    public function test_unknown_key_fails_closed(): void
    {
        $this->expectException(UnknownBoard::class);
        $this->app->make(BoardRegistry::class)->resolve('missing', new BoardContext('missing', 'tenant-a', 'user-a', 'i1'));
    }

    public function test_duplicate_registration_is_rejected(): void
    {
        $this->registerBoard('crm.deals');
        $this->expectException(\Karnoweb\Kanban\Exceptions\InvalidBoardDefinition::class);
        $this->registerBoard('crm.deals');
    }
}

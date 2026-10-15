<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Livewire\Board;
use Karnoweb\Kanban\Tests\TestCase;

final class ComponentRegistrationTest extends TestCase
{
    public function test_kanban_board_alias_resolves_to_the_package_component(): void
    {
        $factory = app('livewire.factory');

        $this->assertTrue($factory->exists('kanban-board'));
        $this->assertSame(Board::class, $factory->resolveComponentClass('kanban-board'));
    }
}

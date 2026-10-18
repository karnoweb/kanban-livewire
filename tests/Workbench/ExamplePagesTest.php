<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Workbench;

use Karnoweb\Kanban\Livewire\Board;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;

final class ExamplePagesTest extends WorkbenchTestCase
{
    /** @return array<string, list<string>> */
    public static function examplePages(): array
    {
        return [
            'tasks' => ['tasks', 'Tasks', 'Task manager'],
            'crm' => ['crm', 'CRM Deals', 'CRM'],
            'tickets' => ['tickets', 'Support Tickets', 'Ticketing'],
            'orders' => ['orders', 'Orders', 'Orders & approvals'],
        ];
    }

    #[DataProvider('examplePages')]
    public function test_example_page_renders_board_with_distinct_config(string $path, string $title, string $marker): void
    {
        $this->loginAs('alice');

        $this->get('/'.$path)
            ->assertOk()
            ->assertSee($title)
            ->assertSee($marker)
            ->assertSee('data-kb-part="toolbar"', false)
            ->assertDontSee('data-kb-part="theme-switch"', false)
            ->assertSee('action="/workbench/theme"', false)
            ->assertDontSee('data-kb-part="dir-switch"', false)
            ->assertSee('data-kb-part="density-toggle"', false)
            ->assertSee('Enterprise SLA');
    }

    public function test_theme_direction_and_density_switches_update_board_state(): void
    {
        $this->loginAs('alice');

        Livewire::test(Board::class, ['boardKey' => 'tasks.board'])
            ->call('setTheme', 'dark')
            ->assertSet('theme', 'dark')
            ->assertSeeHtml('data-theme="dark"')
            ->call('setDirection', 'rtl')
            ->assertSet('dir', 'rtl')
            ->assertSeeHtml('dir="rtl"')
            ->call('setDensity', 'compact')
            ->assertSet('density', 'compact')
            ->assertSeeHtml('data-density="compact"');
    }

    public function test_disallowed_transition_emits_rejected_event_and_renders_notice_banner(): void
    {
        $this->loginAs('alice');

        Livewire::test(Board::class, ['boardKey' => 'crm.deals'])
            ->call('requestMove', '101', 'new', 'closed')
            ->assertDispatched('kanban:move-rejected')
            ->assertSeeHtml('data-kb-part="notice"')
            ->assertSeeHtml('data-code="transition_denied"');
    }

    public function test_workbench_playground_fab_toggles_board_settings(): void
    {
        $this->loginAs('alice');

        $this->post('/workbench/playground', [
            'board_key' => 'tasks.board',
            'features' => [
                'toolbar' => '1',
                'search' => '0',
                'filters' => '0',
                'theme_switch' => '0',
                'direction_switch' => '1',
                'density_switch' => '0',
            ],
            'appearance' => ['theme' => 'dark', 'direction' => 'rtl'],
            'views' => ['default' => 'board', 'allowed_mode' => 'board', 'density' => 'compact', 'detail_surface' => 'drawer'],
        ])->assertRedirect();

        $this->get('/tasks')
            ->assertOk()
            ->assertSee('data-kb-part="workbench-playground"', false)
            ->assertSee('data-theme="dark"', false)
            ->assertSee('dir="rtl"', false)
            ->assertSee('data-density="compact"', false)
            ->assertDontSee('data-kb-part="filters"', false)
            ->assertDontSee('data-kb-part="search"', false)
            ->assertDontSee('data-kb-part="theme-switch"', false);

        $this->post('/workbench/playground', [
            'board_key' => 'tasks.board',
            'preset' => 'full_bleed',
        ])->assertRedirect();

        $this->get('/tasks')
            ->assertOk()
            ->assertSee('data-kb-part="board"', false)
            ->assertDontSee('data-kb-part="toolbar"', false)
            ->assertDontSee('data-kb-part="filters"', false)
            ->assertDontSee('data-kb-part="search"', false)
            ->assertDontSee('class="kb-wb-top"', false);
    }
    public function test_header_theme_applies_to_the_host_and_all_demo_boards(): void
    {
        $this->loginAs('alice');
        $this->post('/workbench/theme', ['theme' => 'dark'])->assertRedirect();
        $this->get('/crm')->assertOk()->assertSee('data-theme="dark"', false);
        Livewire::test(Board::class, ['boardKey' => 'tasks.board'])->assertSet('theme', 'dark');
        $this->post('/workbench/theme', ['theme' => 'invalid'])->assertSessionHasErrors('theme');
    }

}

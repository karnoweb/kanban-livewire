<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Workbench;

use Karnoweb\Kanban\Livewire\Board;
use Livewire\Livewire;
use Workbench\App\Models\DemoCard;

final class JalaliPresentationTest extends WorkbenchTestCase
{
    public function test_iranian_dates_render_in_board_list_details_and_comments_without_changing_source_values(): void
    {
        $this->loginAs('alice');
        app()->setLocale('fa_IR');
        $record = DemoCard::where('scope_key', 'tenant-a')->where('board_key', 'tasks.board')->where('public_id', '101')->firstOrFail();
        $meta = $record->metadata;
        $meta['comments'] = [['body' => 'A comment', 'createdAt' => '2024-03-19T21:00:00Z']];
        $record->metadata = $meta;
        $record->created_at = '2024-03-19 21:00:00';
        $record->save();
        Livewire::test(Board::class, ['boardKey' => 'tasks.board'])
            ->assertSee('۱۴۰۵/۰۸/۲۳')
            ->assertSeeHtml('datetime="2026-11-14"')
            ->call('switchView', 'list')->assertSee('۱۴۰۵/۰۸/۲۳')
            ->call('openDetails', '101')->assertSee('۱۴۰۳/۰۱/۰۱ ۰۰:۳۰');
        self::assertSame('2026-11-14', $record->fresh()->due_at);
    }

    public function test_per_board_locale_can_be_configured_and_invalid_timezone_is_rejected(): void
    {
        $this->loginAs('alice');
        $this->post('/workbench/playground', [
            'board_key' => 'tasks.board',
            'appearance' => ['dates' => ['locale' => 'fa_IR', 'calendar' => 'auto']],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->get('/tasks')->assertOk()->assertSee('۱۴۰۵/۰۸/۲۳');
        $this->post('/workbench/playground', [
            'board_key' => 'tasks.board', 'appearance' => ['dates' => ['timezone' => 'invalid']],
        ])->assertSessionHasErrors('appearance.dates.timezone');
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Data\CardDetailSectionData;
use Karnoweb\Kanban\Exceptions\InvalidBoardDefinition;
use Karnoweb\Kanban\Livewire\Board;
use Karnoweb\Kanban\Support\BoardRegistry;
use Karnoweb\Kanban\Tests\Fixtures\{CardDetailsBoardFactory, MemoryStore, SampleDataFactory, TestCardDetailsProvider};
use Karnoweb\Kanban\Tests\TestCase;
use Livewire\Livewire;

final class CardDetailsProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TestCardDetailsProvider::reset();
    }

    public function test_sections_load_only_when_expanded_and_paginate_inside_the_drawer(): void
    {
        $this->board();
        $component = Livewire::test(Board::class, ['boardKey' => 'details.board'])
            ->call('openDetails', 'new-1', 'drawer')
            ->assertSee('Comments')
            ->assertSee('Load section');
        self::assertSame(0, TestCardDetailsProvider::$loads);

        $component->call('loadDetailSection', 'comments')
            ->assertSee('Initial comment')
            ->assertSee('&lt;script&gt;escaped&lt;/script&gt;', escape: false)
            ->assertSee('Load more');
        self::assertSame(1, TestCardDetailsProvider::$loads);

        $component->call('loadDetailSection', 'comments', 'next-page')->assertSee('Next comment');
        self::assertSame(2, TestCardDetailsProvider::$loads);
    }

    public function test_revoked_section_permission_removes_previously_loaded_content(): void
    {
        $this->board();
        $component = Livewire::test(Board::class, ['boardKey' => 'details.board'])
            ->call('openDetails', 'new-1', 'drawer')
            ->call('loadDetailSection', 'comments')
            ->assertSee('Initial comment');

        TestCardDetailsProvider::$canView = false;
        $component->call('loadDetailSection', 'comments')->assertDontSee('Initial comment');
        self::assertSame(1, TestCardDetailsProvider::$loads);
    }

    public function test_actions_are_exposed_only_when_advertised_and_return_standard_results(): void
    {
        $this->board();
        $component = Livewire::test(Board::class, ['boardKey' => 'details.board'])
            ->call('openDetails', 'new-1', 'drawer')
            ->call('loadDetailSection', 'comments')
            ->call('performDetailAction', 'comments', 'unknown')
            ->assertDontSee('success');
        self::assertSame(0, TestCardDetailsProvider::$actions);

        $component->call('performDetailAction', 'comments', 'refresh');
        self::assertSame(1, TestCardDetailsProvider::$actions);
        self::assertSame('success', $component->get('notice.status'));
    }

    public function test_section_data_requires_visible_permission_and_rejects_unbounded_items(): void
    {
        $hidden = new CardDetailSectionData('comments', 'Comments');
        self::assertFalse($hidden->canView());
        $this->expectException(\InvalidArgumentException::class);
        new CardDetailSectionData('comments', 'Comments', items: array_fill(0, 101, ['label' => 'item']));
    }

    public function test_board_builder_rejects_an_invalid_details_provider(): void
    {
        $this->expectException(InvalidBoardDefinition::class);
        (new \Karnoweb\Kanban\Support\BoardBuilder)->detailsProvider(self::class);
    }

    public function test_section_links_reject_script_schemes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CardDetailSectionData('related', 'Related', items: [['label' => 'Unsafe', 'url' => 'javascript:alert(1)']]);
    }

    public function test_all_nine_section_kinds_remain_lazy(): void
    {
        $this->board();
        TestCardDetailsProvider::$keys = ['description', 'subtasks', 'dependencies', 'checklist', 'comments', 'activity', 'attachments', 'related', 'custom'];
        $component = Livewire::test(Board::class, ['boardKey' => 'details.board'])->call('openDetails', 'new-1', 'drawer');
        foreach (TestCardDetailsProvider::$keys as $key) {
            $component->assertSeeHtml('data-section="'.$key.'"');
        }
        self::assertSame(0, TestCardDetailsProvider::$loads);
        $component->call('loadDetailSection', 'subtasks');
        self::assertSame(1, TestCardDetailsProvider::$loads);
    }

    public function test_a_deleted_card_clears_base_and_section_data(): void
    {
        $this->board();
        $component = Livewire::test(Board::class, ['boardKey' => 'details.board'])
            ->call('openDetails', 'new-1', 'drawer')->call('loadDetailSection', 'comments');
        $board = MemoryStore::board($this->scope->scope, 'details.board');
        unset($board['cards']['new-1']);
        MemoryStore::put($this->scope->scope, 'details.board', $board);
        $component->call('loadDetailSection', 'comments')->assertSet('detail', null)->assertSet('detailSectionPages', []);
    }

    public function test_comment_visibility_does_not_grant_attachment_visibility(): void
    {
        $this->board();
        TestCardDetailsProvider::$keys = ['comments', 'attachments'];
        TestCardDetailsProvider::$deniedKeys = ['attachments'];
        $component = Livewire::test(Board::class, ['boardKey' => 'details.board'])
            ->call('openDetails', 'new-1', 'drawer')
            ->assertSeeHtml('data-section="comments"')
            ->assertDontSeeHtml('data-section="attachments"');
        $component->call('loadDetailSection', 'attachments');
        self::assertSame(0, TestCardDetailsProvider::$loads);
        $component->call('loadDetailSection', 'comments');
        self::assertSame(1, TestCardDetailsProvider::$loads);
    }

    public function test_unknown_actions_do_not_report_success_and_keep_the_retry_identity(): void
    {
        $this->board();
        TestCardDetailsProvider::$unknown = true;
        $component = Livewire::test(Board::class, ['boardKey' => 'details.board'])
            ->call('openDetails', 'new-1', 'drawer')->call('loadDetailSection', 'comments')
            ->call('performDetailAction', 'comments', 'refresh');
        self::assertSame('unknown_outcome', $component->get('notice')['status']);
        $component->call('performDetailAction', 'comments', 'refresh');
        self::assertCount(2, TestCardDetailsProvider::$operationIds);
        self::assertSame(TestCardDetailsProvider::$operationIds[0], TestCardDetailsProvider::$operationIds[1]);
    }

    public function test_disabled_details_cannot_be_opened_by_a_direct_call(): void
    {
        $this->board();
        $board = MemoryStore::board($this->scope->scope, 'details.board');
        $board['settings']['features']['details'] = false;
        MemoryStore::put($this->scope->scope, 'details.board', $board);
        Livewire::test(Board::class, ['boardKey' => 'details.board'])->call('openDetails', 'new-1', 'drawer')->assertSet('detail', null);
        self::assertSame(0, TestCardDetailsProvider::$loads);
    }

    private function board(): void
    {
        MemoryStore::seed($this->scope->scope, 'details.board', SampleDataFactory::standard(1));
        $this->app->make(BoardRegistry::class)->register('details.board', CardDetailsBoardFactory::class);
    }
}

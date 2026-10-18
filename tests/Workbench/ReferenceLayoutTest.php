<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Workbench;

use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardQuery;
use Karnoweb\Kanban\Enums\ActionStatus;
use Karnoweb\Kanban\Livewire\Board;
use Livewire\Livewire;
use Workbench\App\Kanban\CrmBoardFactory;
use Workbench\App\Kanban\CrmHandler;
use Workbench\App\Kanban\CrmProvider;
use Workbench\App\Kanban\TaskProvider;
use Workbench\App\Models\DemoCard;

final class ReferenceLayoutTest extends WorkbenchTestCase
{
    private function crm(): BoardContext
    {
        return new BoardContext('crm.deals', 'tenant-a', '1', 'test');
    }

    public function test_crm_constraints_survive_playground_overrides_and_direct_commands(): void
    {
        $this->loginAs('alice');
        session()->put('workbench.playground.crm.deals', ['features' => ['create_card' => true, 'create_stage' => true], 'interaction' => ['transition_policy' => 'allow']]);
        $definition = app(CrmBoardFactory::class)->make($this->crm());
        self::assertFalse($definition->settings->features->createCard);
        self::assertFalse($definition->settings->features->createStage);
        self::assertSame('deny', $definition->settings->interaction->transitionPolicy);
        Livewire::test(Board::class, ['boardKey' => 'crm.deals'])->call('openCardForm')->assertSet('cardFormOpen', false)->assertDontSeeHtml('data-kb-part="toolbar-create-card"');
        foreach (['createCard', 'createStage'] as $type) {
            $result = app(CrmHandler::class)->execute(ActionCommand::allowlisted('deny-'.$type, $type, ['title' => 'Forbidden', 'stageId' => 'new']), $this->crm());
            self::assertSame(ActionStatus::VALIDATION_ERROR, $result->status);
        }
    }

    public function test_crm_requires_note_for_every_transition_and_persists_it_as_comment(): void
    {
        $payload = ['cardId' => '101', 'fromStageId' => 'new', 'toStageId' => 'progress'];
        $handler = app(CrmHandler::class);
        $result = $handler->execute(ActionCommand::allowlisted('missing-note', 'moveCard', $payload), $this->crm());
        self::assertSame(ActionStatus::VALIDATION_ERROR, $result->status);
        foreach ([123, '   ', str_repeat('x', 5001)] as $invalid) {
            $invalidPayload = $payload;
            $invalidPayload['formData'] = ['note' => $invalid];
            self::assertSame(ActionStatus::VALIDATION_ERROR, $handler->execute(ActionCommand::allowlisted('invalid-'.bin2hex(random_bytes(3)), 'moveCard', $invalidPayload), $this->crm())->status);
        }
        $payload['formData'] = ['note' => 'Approved after review'];
        $result = $handler->execute(ActionCommand::allowlisted('with-note', 'moveCard', $payload), $this->crm());
        self::assertSame(ActionStatus::SUCCESS, $result->status);
        $card = app(CrmProvider::class)->card($this->crm(), '101');
        self::assertSame('progress', $card->stageId);
        self::assertSame('Approved after review', array_last($card->comments)->body);
        $handler->execute(ActionCommand::allowlisted('with-note', 'moveCard', $payload), $this->crm());
        self::assertCount(3, app(CrmProvider::class)->card($this->crm(), '101')->comments);
    }

    public function test_filters_combine_and_options_are_tenant_scoped(): void
    {
        $context = new BoardContext('tasks.board', 'tenant-a', '1', 'test');
        $provider = app(TaskProvider::class);
        self::assertCount(1, $provider->listCards($context, new BoardQuery(filters: ['priority' => 'high', 'assignee' => 'u-alice', 'badge' => 'Security Audit', 'stage' => 'todo']))->items);
        self::assertCount(0, $provider->listCards($context, new BoardQuery(filters: ['priority' => 'low', 'assignee' => 'u-alice']))->items);
        $card = DemoCard::query()->where('scope_key', 'tenant-b')->where('board_key', 'tasks.board')->firstOrFail();
        $meta = $card->metadata;
        $meta['assignees'][] = ['id' => 'private-user', 'name' => 'Other tenant'];
        $card->update(['metadata' => $meta]);
        self::assertArrayNotHasKey('private-user', $provider->filterOptions($context)['assignee']);
        $this->loginAs('alice');
        Livewire::test(Board::class, ['boardKey' => 'tasks.board'])->call('setBoardFilter', 'assignee', 'u-alice')->call('setBoardFilter', 'priority', 'high')->assertSet('filters', ['assignee' => 'u-alice', 'priority' => 'high']);
    }

    public function test_configured_card_pages_replace_inline_create_and_edit_forms(): void
    {
        $this->loginAs('alice');
        app()->bind(\Workbench\App\Kanban\TaskBoardFactory::class, fn () => new class implements \Karnoweb\Kanban\Contracts\BoardFactory {
            public function make(BoardContext $context): \Karnoweb\Kanban\Data\BoardDefinition
            {
                $base = (new \Workbench\App\Kanban\TaskBoardFactory)->make($context);
                $values = get_object_vars($base);
                $values['cardForm'] = new \Karnoweb\Kanban\Data\CardFormSchema(createUrl: '/tasks/create?stage={stage}', editUrl: '/tasks/{id}/edit');
                return new \Karnoweb\Kanban\Data\BoardDefinition(...$values);
            }
        });
        Livewire::test(Board::class, ['boardKey' => 'tasks.board'])->call('openInlineCreate', 'todo')->assertRedirect('/tasks/create?stage=todo')->assertSet('cardFormOpen', false);
        Livewire::test(Board::class, ['boardKey' => 'tasks.board'])->call('editCard', '101')->assertRedirect('/tasks/101/edit')->assertSet('cardFormOpen', false);
    }

    public function test_demo_details_include_real_comments_and_image_file(): void
    {
        $card = app(CrmProvider::class)->card($this->crm(), '101');
        self::assertCount(2, $card->comments);
        self::assertNotEmpty($card->checklist);
        self::assertSame('image/svg+xml', array_last($card->attachments)->mime);
        $this->get('/workbench/board-preview.svg')->assertOk();
    }
}

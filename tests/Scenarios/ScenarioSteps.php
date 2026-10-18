<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Scenarios;

use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Enums\ActionType;
use Karnoweb\Kanban\Livewire\Board;
use Karnoweb\Kanban\Tests\Fixtures\BroadcastHarness;
use Karnoweb\Kanban\Tests\Fixtures\InMemoryHandler;
use Karnoweb\Kanban\Tests\Fixtures\MemoryStore;
use Karnoweb\Kanban\Tests\Fixtures\SampleDataFactory;
use Livewire\Livewire;

trait ScenarioSteps
{
    private function stageCountText(string $html, string $stage): string
    {
        $dom = new \DOMDocument;
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);
        $node = $xpath->query('//*[@data-stage="'.$stage.'"]//*[@data-kb-part="stage-count"]')->item(0);

        return trim((string) preg_replace('/\s+/', ' ', $node?->textContent ?? ''));
    }

    protected function bootBoard(string $key, array $config): mixed
    {
        MemoryStore::seed($this->scope->scope, $key, $config);
        $this->registerBoard($key);

        return Livewire::test(Board::class, ['boardKey' => $key]);
    }

    protected function callCount(string $type): int
    {
        return count(array_filter(MemoryStore::$calls, static fn (array $call): bool => $call['type'] === $type));
    }

    /** @param list<array<string, mixed>> $cards */
    protected function column(array $stages, string $stageId, array $cards): array
    {
        return [
            'title' => $stageId,
            'stages' => $stages,
            'cards' => $cards,
            'settings' => ['interaction' => ['transition_policy' => 'allow']],
        ];
    }

    public function kb001(): void
    {
        $component = $this->bootBoard('crm.deals', SampleDataFactory::standard(8));
        $component->assertSee('New')->assertSee('Progress')->assertSee('Review')->assertSee('Done')->assertSee('NEW 1');
        $this->assertSame('8', $this->stageCountText($component->html(), 'new'));
        $this->assertCount(4, $component->get('columns'));
    }

    public function kb002(): void
    {
        $component = $this->bootBoard('crm.deals', SampleDataFactory::standard(2));
        $component->call('applyFilters', ['assignee' => 'alice']);
        $component->call('switchView', 'list')->assertSet('view', 'list')->assertSee('NEW 2');
        $component->call('setViewport', 'mobile')->assertSet('view', 'list')->assertSet('viewport', 'mobile');
        $this->assertSame(0, $this->callCount('moveCard'));
    }

    public function kb003(): void
    {
        $config = SampleDataFactory::standard(1);
        $config['stages'] = [['id' => 'new', 'title' => 'New', 'order' => 0]];
        $config['cards'] = [];

        for ($i = 1; $i <= 30; $i++) {
            $config['cards'][] = ['id' => sprintf('c%03d', $i), 'stageId' => 'new', 'title' => sprintf('C-%03d', $i), 'manualOrder' => sprintf('%03d', $i)];
        }

        $config['settings']['views'] = ['default' => 'board', 'allowed' => ['board']];
        $component = $this->bootBoard('crm.deals', $config);
        $this->assertStringNotContainsString("switchView('list')", $component->html());
        $component->assertSee('Load more');
    }

    public function kb004(): void
    {
        $left = ['title' => 'CRM', 'stages' => [['id' => 'new', 'title' => 'New']], 'cards' => [['id' => '101', 'stageId' => 'new', 'title' => 'Alpha deal']], 'settings' => ['interaction' => ['transition_policy' => 'allow']]];
        $right = ['title' => 'Tasks', 'stages' => [['id' => 'new', 'title' => 'New']], 'cards' => [['id' => '101', 'stageId' => 'new', 'title' => 'Beta task']], 'settings' => ['interaction' => ['transition_policy' => 'allow']]];
        $crm = $this->bootBoard('crm.deals', $left);
        $tasks = $this->bootBoard('tasks.board', $right);
        $crm->assertSee('Alpha deal')->assertDontSee('Beta task');
        $tasks->assertSee('Beta task')->assertDontSee('Alpha deal');
        $this->assertNotSame(
            $crm->html(),
            str_replace('Alpha deal', 'Beta task', $crm->html()),
        );
    }

    public function kb005(): void
    {
        $cards = [];

        for ($i = 1; $i <= 120; $i++) {
            $cards[] = ['id' => 'c'.$i, 'stageId' => 'new', 'title' => sprintf('C-%03d', $i), 'manualOrder' => sprintf('%03d', $i)];
        }

        $component = $this->bootBoard('crm.deals', $this->column([['id' => 'new', 'title' => 'New']], 'new', $cards));
        $component->assertSee('C-001')->assertDontSee('C-026');
        $this->assertSame('120', $this->stageCountText($component->html(), 'new'));
        $this->assertSame(25, $component->get('columns')[0]['loadedCount']);
        $this->assertSame(120, $component->get('columns')[0]['totalCount']);
    }

    public function kb006(): void
    {
        $cards = [];

        for ($i = 1; $i <= 50; $i++) {
            $cards[] = ['id' => 'c'.$i, 'stageId' => 'new', 'title' => sprintf('C-%03d', $i), 'manualOrder' => sprintf('%03d', $i)];
        }

        $component = $this->bootBoard('crm.deals', $this->column([['id' => 'new', 'title' => 'New']], 'new', $cards));
        $component->call('loadMore', 'new');
        $cards = $component->get('columns')[0]['cards'];
        $ids = array_column($cards, 'id');
        $titles = array_column($cards, 'title');
        $this->assertCount(50, $ids);
        $this->assertSame($ids, array_values(array_unique($ids)));
        $this->assertContains('C-026', $titles);
        $this->assertContains('C-050', $titles);
        $this->assertSame(50, $component->get('columns')[0]['renderedCount']);
        $component->assertSee('C-050')->assertSee('C-026');
    }

    public function kb007(): void
    {
        $component = $this->bootBoard('crm.deals', SampleDataFactory::standard(4));
        $component->call('applyFilters', ['assignee' => 'alice', 'priority' => 'high']);
        $component->assertSee('NEW 2')->assertDontSee('NEW 1');
        $component->call('switchView', 'list')->assertSee('NEW 2')->assertDontSee('NEW 1');
        $this->assertSame(2, $component->get('columns')[0]['totalCount']);
    }

    public function kb008(): void
    {
        $config = $this->column(
            [['id' => 'new', 'title' => 'New']],
            'new',
            [
                ['id' => 'a', 'stageId' => 'new', 'title' => 'AlphaUnique', 'manualOrder' => '1'],
                ['id' => 'b', 'stageId' => 'new', 'title' => 'BetaUnique', 'manualOrder' => '2'],
            ],
        );
        $component = $this->bootBoard('crm.deals', $config);
        $hash = $component->get('queryHash');
        $component->set('search', 'BetaUnique');
        $component->call('commitStagePage', 'new', $hash, [['id' => 'stale', 'stageId' => 'new', 'title' => 'STALE_RESULT']]);
        $component->assertDontSee('STALE_RESULT')->assertSee('BetaUnique');
    }

    public function kb009(): void
    {
        $component = $this->bootBoard('crm.deals', SampleDataFactory::standard(1));
        $component->call('sortBy', 'dueAt')->assertSet('reorderAllowed', false)->assertSee('Manual reorder is unavailable');
    }

    public function kb010(): void
    {
        MemoryStore::$constructedCards = 0;
        $component = $this->bootBoard('crm.deals', SampleDataFactory::large());
        $loaded = array_sum(array_column($component->get('columns'), 'loadedCount'));
        $this->assertSame(250, $loaded);
        $this->assertSame(250, MemoryStore::$constructedCards);
        $this->assertLessThanOrEqual(250, $loaded);
    }

    public function kb011(): void
    {
        $component = $this->moveBoard();
        $component->call('requestMove', 'c1', 'a', 'b');
        $this->assertSame('b', MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['stageId']);
        $this->assertSame(1, $this->callCount('moveCard'));
        $this->assertSame(0, $component->get('columns')[0]['totalCount']);
        $this->assertSame(1, $component->get('columns')[1]['totalCount']);
    }

    public function kb012(): void
    {
        $config = $this->twoStage();
        $config['settings']['interaction'] = ['transition_policy' => 'deny', 'transitions' => ['a' => ['b']]];
        $component = $this->bootBoard('crm.deals', $config);
        $component->call('requestMove', 'c1', 'a', 'c');
        $this->assertSame('a', MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['stageId']);
        $this->assertSame(0, $this->callCount('moveCard'));
        $this->assertSame('forbidden', $component->get('notice')['status']);
    }

    public function kb013(): void
    {
        $config = $this->twoStage();
        $config['settings']['interaction']['confirmation'] = 'generic';
        $component = $this->bootBoard('crm.deals', $config);
        $component->call('requestMove', 'c1', 'a', 'b')->call('cancelPending');
        $this->assertSame(0, $this->callCount('moveCard'));
        $this->assertNull($component->get('pending'));
        $this->assertSame('a', MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['stageId']);
    }

    public function kb014(): void
    {
        $component = $this->noteBoard();
        $component->call('requestMove', 'c1', 'a', 'b');
        $component->call('confirmPending', ['note' => '']);
        $this->assertSame(0, $this->callCount('moveCard'));
        $this->assertSame('required', $component->get('pending')['errors']['note']);
        $this->assertArrayNotHasKey('note', $component->get('pendingValues'));
    }

    public function kb015(): void
    {
        $component = $this->noteBoard();
        $component->call('requestMove', 'c1', 'a', 'b');
        $component->call('confirmPending', ['note' => 'Negotiation result']);
        $this->assertSame(1, $this->callCount('moveCard'));
        $this->assertSame('b', MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['stageId']);
        $this->assertSame('Negotiation result', MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['metadata']['note']);
    }

    public function kb016(): void
    {
        $config = $this->twoStage();
        $config['denyActors'] = ['user-a'];
        $component = $this->bootBoard('crm.deals', $config);
        $component->call('requestMove', 'c1', 'a', 'b');
        $this->assertSame('a', MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['stageId']);
        $this->assertSame('forbidden', $component->get('notice')['status']);
    }

    public function kb017(): void
    {
        $config = $this->twoStage();
        $config['settings']['features'] = ['reorder_cards' => false];
        $component = $this->bootBoard('crm.deals', $config);
        $component->call('requestMove', 'c1', 'a', 'a');
        $this->assertSame(0, $this->callCount('reorderCard'));
        $this->assertSame('a', MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['stageId']);
    }

    public function kb018(): void
    {
        $config = $this->column(
            [['id' => 'a', 'title' => 'A']],
            'a',
            [
                ['id' => 'c1', 'stageId' => 'a', 'title' => 'First', 'manualOrder' => '2'],
                ['id' => 'c2', 'stageId' => 'a', 'title' => 'Second', 'manualOrder' => '1'],
            ],
        );
        $component = $this->bootBoard('crm.deals', $config);
        $component->call('reorderCard', 'c1', 'a', 'c2');
        $component->call('switchView', 'board');
        $orders = array_column(MemoryStore::board('tenant-a', 'crm.deals')['cards'], 'manualOrder', 'id');
        $this->assertNotSame('2', $orders['c1']);
        $this->assertSame(1, $this->callCount('reorderCard'));
    }

    public function kb019(): void
    {
        $this->bootBoard('crm.deals', $this->twoStage());
        $handler = new InMemoryHandler;
        $context = new BoardContext('crm.deals', 'tenant-a', 'user-a', 'instance-1');
        $command = new ActionCommand('op-same', ActionType::MoveCard, [
            'boardId' => 'crm.deals', 'cardId' => 'c1', 'fromStageId' => 'a', 'toStageId' => 'b',
            'beforeCardId' => null, 'afterCardId' => null, 'placement' => null, 'formData' => [],
        ]);
        $handler->execute($command, $context);
        $handler->execute($command, $context);
        $this->assertSame('b', MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['stageId']);
        $this->assertSame(1, $this->callCount('moveCard'));
    }

    public function kb020(): void
    {
        $component = $this->moveBoard();
        MemoryStore::$returnUnknown = true;
        $component->call('requestMove', 'c1', 'a', 'b');
        $this->assertSame('unknown_outcome', $component->get('notice')['status']);
        $this->assertSame('c1', $component->get('columns')[0]['cards'][0]['id']);
        $this->assertSame('a', $component->get('columns')[0]['id']);
        MemoryStore::$returnUnknown = false;
        $component->call('reconcileOperation', $component->get('unknownOperationId'));
        $this->assertSame('success', $component->get('notice')['status']);
        $this->assertSame('b', MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['stageId']);
    }

    public function kb021(): void
    {
        $component = $this->moveBoard();
        $board = MemoryStore::board('tenant-a', 'crm.deals');
        $board['cards']['c1']['version'] = '4';
        $board['cards']['c1']['title'] = 'Other user edit';
        MemoryStore::put('tenant-a', 'crm.deals', $board);
        $component->call('requestMove', 'c1', 'a', 'b', null, null, null, '1');
        $this->assertSame('conflict', $component->get('notice')['status']);
        $this->assertSame('a', MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['stageId']);
        $this->assertSame('4', MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['version']);
    }

    public function kb022(): void
    {
        $config = $this->wipBoard(5, 5);
        $component = $this->bootBoard('crm.deals', $config);
        $component->call('requestMove', 'extra', 'a', 'b');
        $this->assertSame('wip_limit', $component->get('notice')['status']);
        $this->assertSame('a', MemoryStore::board('tenant-a', 'crm.deals')['cards']['extra']['stageId']);
    }

    public function kb023(): void
    {
        $config = $this->wipBoard(4, 5);
        $config['cards'][] = ['id' => 'extra2', 'stageId' => 'a', 'title' => 'Extra 2', 'manualOrder' => '2'];
        $component = $this->bootBoard('crm.deals', $config);
        $component->call('requestMove', 'extra', 'a', 'b');
        $component->call('requestMove', 'extra2', 'a', 'b');
        $inB = array_filter(MemoryStore::board('tenant-a', 'crm.deals')['cards'], static fn (array $card): bool => $card['stageId'] === 'b' && ! $card['archived']);
        $this->assertCount(5, $inB);
        $this->assertSame('wip_limit', $component->get('notice')['status']);
    }

    public function kb024(): void
    {
        $component = $this->moveBoard();
        $component->call('openInlineCreate', 'a')->set('cardDraft', 'Created by Enter')->call('submitInlineCard');
        $titles = array_column(MemoryStore::board('tenant-a', 'crm.deals')['cards'], 'title');
        $this->assertContains('Created by Enter', $titles);
        $this->assertSame(1, $this->callCount('createCard'));
    }

    public function kb025(): void
    {
        $component = $this->moveBoard();
        $before = count(MemoryStore::board('tenant-a', 'crm.deals')['cards']);
        $component->call('openInlineCreate', 'a')->set('cardDraft', 'Nope')->call('cancelInlineCreate');
        $this->assertSame($before, count(MemoryStore::board('tenant-a', 'crm.deals')['cards']));
        $this->assertSame(0, $this->callCount('createCard'));
    }

    public function kb026(): void
    {
        $component = $this->moveBoard();
        $before = count(MemoryStore::board('tenant-a', 'crm.deals')['cards']);
        $component->call('openInlineCreate', 'a')->set('cardDraft', '   ')->call('submitInlineCard');
        $this->assertSame('required', $component->get('cardErrors')['title']);
        $this->assertSame('a', $component->get('inlineStageId'));
        $this->assertSame($before, count(MemoryStore::board('tenant-a', 'crm.deals')['cards']));
    }

    public function kb027(): void
    {
        $component = $this->moveBoard();
        $component->call('openStageForm')->set('stageForm', ['title' => 'Later', 'color' => '#112233', 'stageId' => null])->call('submitStageForm');
        $component->assertSee('Later');
        $id = $component->get('columns')[2]['id'];
        $component->call('openStageForm', $id)->set('stageForm.title', 'Renamed')->set('stageForm.color', '#445566')->call('submitStageForm');
        $component->assertSee('Renamed');
        $this->assertSame('#445566', MemoryStore::board('tenant-a', 'crm.deals')['stages'][$id]['color']);
    }

    public function kb028(): void
    {
        $component = $this->moveBoard();
        $component->call('reorderStages', ['b', 'a']);
        $orders = array_column(MemoryStore::board('tenant-a', 'crm.deals')['stages'], 'order', 'id');
        $this->assertSame(0, $orders['b']);
        $fresh = Livewire::test(Board::class, ['boardKey' => 'crm.deals']);
        $this->assertSame('b', $fresh->get('columns')[0]['id']);
    }

    public function kb029(): void
    {
        $component = $this->moveBoard();
        $component->call('archiveStage', 'a');
        $this->assertSame('domain_rejected', $component->get('notice')['status']);
        $this->assertFalse(MemoryStore::board('tenant-a', 'crm.deals')['stages']['a']['archived']);
        $component->assertSee('Only');
    }

    public function kb030(): void
    {
        $component = $this->moveBoard();
        $component->call('archiveCard', 'c1')->assertDontSee('Only');
        $component->call('openArchive')->assertSee('Only');
        $component->call('restoreCard', 'c1', 'a')->assertSee('Only');
        $this->assertFalse(MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['archived']);
    }

    public function kb031(): void
    {
        $config = $this->twoStage();
        $config['cards'][0]['actions'] = [['id' => 'ping', 'label' => 'Ping host', 'enabled' => true]];
        $component = $this->bootBoard('crm.deals', $config);
        $component->call('openDetails', 'c1')->assertSee('Only')->call('runCardAction', 'c1', 'ping');
        $this->assertSame('ping', MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['metadata']['lastAction']);
        $this->assertSame(1, $this->callCount('executeCardAction'));
    }

    public function kb032(): void
    {
        $config = $this->bulkBoard();
        $config['bulkDeny'] = ['c5'];
        $component = $this->bootBoard('crm.deals', $config);

        foreach (['c1', 'c2', 'c3', 'c4', 'c5'] as $id) {
            $component->call('toggleSelected', $id);
        }

        $component->call('runBulk', 'archive', 'partial');
        $cards = MemoryStore::board('tenant-a', 'crm.deals')['cards'];
        $this->assertTrue($cards['c1']['archived']);
        $this->assertFalse($cards['c5']['archived']);
        $this->assertSame(4, $component->get('bulkSummary')['successCount']);
        $this->assertSame(1, $component->get('bulkSummary')['failureCount']);
    }

    public function kb033(): void
    {
        $config = $this->bulkBoard();
        $config['bulkDeny'] = ['c5'];
        $component = $this->bootBoard('crm.deals', $config);

        foreach (['c1', 'c2', 'c3', 'c4', 'c5'] as $id) {
            $component->call('toggleSelected', $id);
        }

        $component->call('runBulk', 'archive', 'all-or-nothing');
        $archived = array_filter(MemoryStore::board('tenant-a', 'crm.deals')['cards'], static fn (array $card): bool => $card['archived']);
        $this->assertSame([], $archived);
        $this->assertSame(0, $component->get('bulkSummary')['successCount']);
    }

    public function kb034(): void
    {
        $config = $this->realtimeBoard();
        $first = $this->bootBoard('crm.deals', $config);
        $second = Livewire::test(Board::class, ['boardKey' => 'crm.deals']);
        $first->call('requestMove', 'c1', 'a', 'b');
        $event = BroadcastHarness::event(new BoardContext('crm.deals', 'tenant-a', 'user-a', 'i'), 'CardMoved', 'c1', '2', 'op-1');
        $second->call('applyRealtimeEvent', $event->toArray());
        $this->assertSame('b', $second->get('columns')[1]['id']);
        $this->assertSame('c1', $second->get('columns')[1]['cards'][0]['id']);
        $this->assertSame(1, $second->get('realtimeApplies'));
    }

    public function kb035(): void
    {
        $component = $this->bootBoard('crm.deals', $this->realtimeBoard());
        $event = BroadcastHarness::event(new BoardContext('crm.deals', 'tenant-a', 'user-a', 'i'), 'CardUpdated', 'c1', '2');
        $component->call('applyRealtimeEvent', $event->toArray());
        $component->call('applyRealtimeEvent', $event->toArray());
        $this->assertSame(1, $component->get('realtimeApplies'));
    }

    public function kb036(): void
    {
        $config = $this->realtimeBoard();
        $config['cards'][0]['version'] = '5';
        $component = $this->bootBoard('crm.deals', $config);
        $event = BroadcastHarness::event(new BoardContext('crm.deals', 'tenant-a', 'user-a', 'i'), 'CardUpdated', 'c1', '2');
        $component->call('applyRealtimeEvent', $event->toArray());
        $this->assertSame(0, $component->get('realtimeApplies'));
        $this->assertSame('5', $component->get('columns')[0]['cards'][0]['version']);
    }

    public function kb037(): void
    {
        $component = $this->bootBoard('crm.deals', $this->realtimeBoard());
        $component->call('setOffline', true);
        $component->call('requestMove', 'c1', 'a', 'b');
        $this->assertSame(0, $this->callCount('moveCard'));
        $board = MemoryStore::board('tenant-a', 'crm.deals');
        $board['cards']['late'] = ['id' => 'late', 'stageId' => 'a', 'title' => 'Added offline', 'priority' => null, 'description' => null, 'assignees' => [], 'badges' => [], 'attachments' => [], 'actions' => [], 'metadata' => [], 'dueAt' => null, 'createdAt' => '2026-01-01T00:00:00Z', 'version' => '1', 'manualOrder' => '9', 'archived' => false, 'permissions' => []];
        MemoryStore::put('tenant-a', 'crm.deals', $board);
        $component->call('reconnect')->assertSee('Added offline')->assertSet('connection', 'online');
    }

    public function kb038(): void
    {
        MemoryStore::seed('tenant-a', 'crm.deals', $this->secret('Tenant A secret'));
        MemoryStore::seed('tenant-b', 'crm.deals', $this->secret('Tenant B secret'));
        $this->registerBoard('crm.deals');
        $visible = Livewire::test(Board::class, ['boardKey' => 'crm.deals']);
        $visible->assertSee('Tenant A secret')->assertDontSee('Tenant B secret');
        $handler = new InMemoryHandler;
        $handler->execute(new ActionCommand('op-cross', ActionType::MoveCard, [
            'cardId' => 'c1', 'fromStageId' => 'a', 'toStageId' => 'b', 'boardId' => 'crm.deals',
            'beforeCardId' => null, 'afterCardId' => null, 'placement' => null, 'formData' => [],
        ]), new BoardContext('crm.deals', 'tenant-a', 'user-a', 'i'));
        $this->assertSame('a', MemoryStore::board('tenant-b', 'crm.deals')['cards']['c1']['stageId']);
        $this->assertSame('Tenant B secret', MemoryStore::board('tenant-b', 'crm.deals')['cards']['c1']['title']);
    }

    public function kb039(): void
    {
        $config = $this->twoStage();
        $config['cards'][0]['title'] = '<script>alert(1)</script>';
        $component = $this->bootBoard('crm.deals', $config);
        $html = $component->html();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function kb040(): void
    {
        $config = $this->twoStage();
        $config['settings']['appearance'] = ['theme' => 'dark', 'direction' => 'rtl'];
        $component = $this->bootBoard('crm.deals', $config);
        $component->call('setViewport', 'mobile');
        $html = $component->html();
        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('data-theme="dark"', $html);
        $this->assertStringContainsString('data-viewport="mobile"', $html);
        $this->assertSame('board', $component->get('view'));
        $component->call('setViewport', 'desktop')->assertSet('view', 'board');
    }

    public function kb041(): void
    {
        $component = $this->moveBoard();
        $component->call('moveCardToStage', 'c1', 'b');
        $this->assertSame('b', MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['stageId']);
        $component->call('reorderCard', 'c1', 'b');
        $this->assertSame(1, $this->callCount('reorderCard'));
    }

    public function kb042(): void
    {
        $component = $this->moveBoard();
        $component->call('setReducedMotion', true);
        $this->assertStringContainsString('data-reduced-motion="true"', $component->html());
        $component->call('requestMove', 'c1', 'a', 'b');
        $this->assertSame('b', MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['stageId']);
    }

    public function kb043(): void
    {
        $this->bootBoard('crm.deals', $this->realtimeBoard());

        for ($i = 0; $i < 50; $i++) {
            Livewire::test(Board::class, ['boardKey' => 'crm.deals'])->call('$refresh');
        }

        $js = file_get_contents(dirname(__DIR__, 2).'/resources/js/realtime-bridge.js');
        $this->assertIsString($js);
        $this->assertStringContainsString('detach', (string) $js);
        $this->assertStringNotContainsString('Alpine.start', (string) file_get_contents(dirname(__DIR__, 2).'/resources/js/kanban.js'));
        $node = $this->nodeListenerLeak();
        $this->assertSame('0', trim($node));
    }

    public function kb044(): void
    {
        $crm = $this->bootBoard('crm.deals', $this->realtimeBoard());
        $tasks = $this->bootBoard('tasks.board', array_replace($this->realtimeBoard(), ['title' => 'Tasks', 'cards' => [[
            'id' => 't1', 'stageId' => 'a', 'title' => 'Task card', 'manualOrder' => '1',
        ]]]));
        $event = BroadcastHarness::event(new BoardContext('crm.deals', 'tenant-a', 'user-a', 'i'), 'CardMoved', 'c1', '2');
        $tasks->call('applyRealtimeEvent', $event->toArray());
        $this->assertSame(0, $tasks->get('realtimeApplies'));
        $this->assertSame('Task card', $tasks->get('columns')[0]['cards'][0]['title']);
        $crm->call('applyRealtimeEvent', $event->toArray());
        $this->assertSame(1, $crm->get('realtimeApplies'));
    }

    /** @return array<string, mixed> */
    private function twoStage(): array
    {
        return [
            'title' => 'CRM',
            'stages' => [
                ['id' => 'a', 'title' => 'A', 'order' => 0],
                ['id' => 'b', 'title' => 'B', 'order' => 1],
                ['id' => 'c', 'title' => 'C', 'order' => 2],
            ],
            'cards' => [['id' => 'c1', 'stageId' => 'a', 'title' => 'Only', 'manualOrder' => '1']],
            'settings' => ['interaction' => ['transition_policy' => 'allow']],
        ];
    }

    private function moveBoard(): mixed
    {
        return $this->bootBoard('crm.deals', $this->twoStage());
    }

    private function noteBoard(): mixed
    {
        $config = $this->twoStage();
        $config['settings']['interaction']['confirmation_rules'] = [
            'a>b' => ['mode' => 'form', 'fields' => ['note' => ['required' => true]]],
        ];

        return $this->bootBoard('crm.deals', $config);
    }

    /** @return array<string, mixed> */
    private function wipBoard(int $existing, int $limit): array
    {
        $cards = [['id' => 'extra', 'stageId' => 'a', 'title' => 'Extra', 'manualOrder' => '1']];

        for ($i = 1; $i <= $existing; $i++) {
            $cards[] = ['id' => 'b'.$i, 'stageId' => 'b', 'title' => 'B'.$i, 'manualOrder' => (string) $i];
        }

        return [
            'title' => 'WIP',
            'stages' => [
                ['id' => 'a', 'title' => 'A', 'order' => 0],
                ['id' => 'b', 'title' => 'B', 'order' => 1, 'wipLimit' => $limit, 'wipMode' => 'strict'],
            ],
            'cards' => $cards,
            'settings' => ['interaction' => ['transition_policy' => 'allow']],
        ];
    }

    /** @return array<string, mixed> */
    private function bulkBoard(): array
    {
        $cards = [];

        for ($i = 1; $i <= 5; $i++) {
            $cards[] = ['id' => 'c'.$i, 'stageId' => 'a', 'title' => 'Card '.$i, 'manualOrder' => (string) $i];
        }

        return [
            'title' => 'Bulk',
            'stages' => [['id' => 'a', 'title' => 'A']],
            'cards' => $cards,
            'settings' => ['interaction' => ['transition_policy' => 'allow']],
        ];
    }

    /** @return array<string, mixed> */
    private function realtimeBoard(): array
    {
        $config = $this->twoStage();
        $config['settings']['realtime'] = ['enabled' => true];
        $config['settings']['features'] = ['realtime' => true];

        return $config;
    }

    /** @return array<string, mixed> */
    private function secret(string $title): array
    {
        return [
            'title' => 'Scoped',
            'stages' => [['id' => 'a', 'title' => 'A'], ['id' => 'b', 'title' => 'B']],
            'cards' => [['id' => 'c1', 'stageId' => 'a', 'title' => $title, 'manualOrder' => '1']],
            'settings' => ['interaction' => ['transition_policy' => 'allow']],
        ];
    }

    private function nodeListenerLeak(): string
    {
        $script = dirname(__DIR__, 2).'/resources/js/realtime-bridge.js';
        $runner = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kb-listener-leak.mjs';
        $path = json_encode($script);
        file_put_contents($runner, <<<JS
import fs from 'fs';
import vm from 'vm';
const context = { window: {}, Map, document: { addEventListener() {} } };
context.window = context;
vm.createContext(context);
vm.runInContext(fs.readFileSync({$path}, 'utf8'), context);
const echo = { private() { return { stopListening() {} }; } };
for (let i = 0; i < 50; i++) {
  context.window.KbRealtime.attach('id' + i, echo, 'private-kanban');
  context.window.KbRealtime.detach('id' + i);
}
process.stdout.write(String(context.window.KbRealtime.size()));
JS);
        $result = shell_exec('node '.escapeshellarg($runner));

        return is_string($result) ? $result : 'missing-node';
    }
}

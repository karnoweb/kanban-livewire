<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Security;

use Karnoweb\Kanban\Tests\Fixtures\MemoryStore;
use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class DisabledFeatureBypassTest extends TestCase
{
    use ScenarioSteps;

    /** @return array<string, array{0: string, 1: list<mixed>, 2: string}> */
    public static function direct_calls(): array
    {
        return [
            'reorder cards' => ['reorder_cards', ['reorderCard', 'c1', 'a', null, null], 'reorderCard'],
            'create card' => ['create_card', ['submitInlineCard'], 'createCard'],
            'archive card' => ['archive_card', ['archiveCard', 'c1'], 'archiveCard'],
            'restore card' => ['restore_card', ['restoreCard', 'c1', 'a'], 'restoreCard'],
            'reorder stages' => ['reorder_stages', ['reorderStages', ['c', 'b', 'a']], 'reorderStage'],
            'archive stage' => ['archive_stage', ['archiveStage', 'c'], 'archiveStage'],
            'bulk' => ['bulk', ['runBulk', 'archive', 'partial'], 'executeBulkAction'],
            'custom actions' => ['custom_actions', ['runCardAction', 'c1', 'ping'], 'executeCardAction'],
        ];
    }

    /**
     * @param  list<mixed>  $call
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('direct_calls')]
    public function test_disabled_feature_cannot_be_invoked_directly(string $feature, array $call, string $type): void
    {
        $config = $this->twoStage();
        $config['cards'][0]['actions'] = [['id' => 'ping', 'label' => 'Ping', 'enabled' => true]];
        $config['settings']['features'] = [$feature => false];
        $component = $this->bootBoard('crm.deals', $config);

        if ($type === 'createCard') {
            $component->set('cardDraft', 'sneaky');
        }

        if ($type === 'executeBulkAction') {
            $component->call('toggleSelected', 'c1');
        }

        $method = array_shift($call);
        $component->call($method, ...$call);

        $this->assertSame(0, $this->callCount($type), 'Handler reached for disabled feature '.$feature);
        $this->assertSame('a', MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['stageId']);
        $this->assertFalse(MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1']['archived'] ?? false);
    }

    public function test_pending_cannot_be_tampered_from_the_client(): void
    {
        $component = $this->noteBoard();
        $component->call('requestMove', 'c1', 'a', 'b');
        $this->assertSame('form', $component->get('pending')['mode']);

        // pending is locked: the client cannot rewrite the target stage or required fields
        try {
            $component->set('pending', ['operationId' => 'x', 'mode' => 'none', 'fields' => [], 'request' => ['boardId' => 'x', 'cardId' => 'c1', 'fromStageId' => 'a', 'toStageId' => 'c', 'beforeCardId' => null, 'afterCardId' => null, 'placement' => null, 'expectedVersion' => null]]);
            $this->fail('Locked property was writable.');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('pending', strtolower($e->getMessage()));
        }

        $component->call('confirmPending', ['note' => '', 'toStageId' => 'c']);
        $this->assertSame(0, $this->callCount('moveCard'));

        $component->call('confirmPending', ['note' => 'ok', 'toStageId' => 'c', 'extra' => 'x']);
        $card = MemoryStore::board('tenant-a', 'crm.deals')['cards']['c1'];
        $this->assertSame('b', $card['stageId']);
        $this->assertSame(['note' => 'ok'], array_intersect_key($card['metadata'], ['note' => 1, 'extra' => 1]));
    }

    public function test_pending_is_revalidated_when_card_changed_meanwhile(): void
    {
        $component = $this->noteBoard();
        $component->call('requestMove', 'c1', 'a', 'b');

        $store = MemoryStore::board('tenant-a', 'crm.deals');
        $store['cards']['c1']['stageId'] = 'c';
        MemoryStore::put('tenant-a', 'crm.deals', $store);

        $component->call('confirmPending', ['note' => 'late']);
        $this->assertSame(0, $this->callCount('moveCard'));
        $this->assertNull($component->get('pending'));
    }
}

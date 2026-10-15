<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Livewire\Concerns;

use Karnoweb\Kanban\Realtime\ConnectionState;
use Karnoweb\Kanban\Realtime\RealtimeBridge;
use Karnoweb\Kanban\Support\ScopeHasher;

trait HandlesRealtime
{
    public function setOffline(bool $offline): void
    {
        $this->connection = $offline ? ConnectionState::OFFLINE : ConnectionState::ONLINE;
        $this->dispatch('kanban:realtime-status-changed', status: $this->connection);
    }

    public function reconnect(): void
    {
        $this->connection = ConnectionState::RECONNECTING;
        $this->listenerSerial++;
        $this->reload();
        $this->connection = ConnectionState::ONLINE;

        if ($this->unknownOperationId !== null) {
            $this->reconcileOperation($this->unknownOperationId);
        }

        $this->dispatch('kanban:realtime-status-changed', status: $this->connection);
    }

    /** @param array<string, mixed> $payload */
    public function applyRealtimeEvent(array $payload): void
    {
        if (! $this->definition()->settings->realtime->enabled && ! $this->definition()->settings->features->realtime) {
            return;
        }

        $known = null;
        $entity = $payload['entityId'] ?? null;

        if (is_string($entity)) {
            foreach ($this->columns as $column) {
                foreach ($column['cards'] as $card) {
                    if (($card['id'] ?? null) === $entity) {
                        $known = isset($card['version']) ? (string) $card['version'] : null;
                    }
                }
            }
        }

        $decision = app(RealtimeBridge::class)->accept($payload, $this->context(), $this->definition()->board->id, $known);

        if (in_array($decision['decision'], ['apply', 'gap'], true)) {
            $this->realtimeApplies++;
            $this->reload();
            $this->dispatch('kanban:realtime-status-changed', status: $decision['decision']);
        }
    }

    public function reconcileOperation(string $operationId): void
    {
        $status = $this->provider()->operationStatus($this->context(), $operationId);

        if ($status === null) {
            $this->notice = ['status' => 'unknown_outcome', 'messageCode' => 'unknown_outcome', 'fieldErrors' => [], 'safeMessage' => null, 'successCount' => 0, 'failureCount' => 0, 'noop' => false, 'items' => []];

            return;
        }

        $this->notice = app(\Karnoweb\Kanban\Actions\ResultMapper::class)->notice($status);
        $this->unknownOperationId = null;
        $this->reload();
    }

    public function channelName(): string
    {
        $context = $this->context();

        return app(\Karnoweb\Kanban\Realtime\ChannelNameFactory::class)->name(
            app(ScopeHasher::class)->hash($context->scopeKey),
            $context->boardKey,
        );
    }
}

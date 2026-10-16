<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Realtime;

use Illuminate\Contracts\Events\Dispatcher;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\RealtimeEventData;
use Karnoweb\Kanban\Support\ScopeHasher;

/**
 * Host handlers call this after a successful write. The event is dispatched
 * after the surrounding database transaction commits, so clients never see
 * a change that was rolled back.
 */
final class RealtimePublisher
{
    public function __construct(
        private readonly BroadcastEventFactory $factory,
        private readonly ChannelNameFactory $channels,
        private readonly ScopeHasher $hasher,
        private readonly Dispatcher $events,
    ) {}

    public function publish(
        BoardContext $context,
        string $eventType,
        ?string $entityId = null,
        ?string $entityVersion = null,
        ?string $operationId = null,
    ): RealtimeEventData {
        $registry = app(\Karnoweb\Kanban\Support\BoardRegistry::class);
        if ($registry->has($context->boardKey)) {
            $board = $registry->resolve($context->boardKey, $context);
            if ($board->sources !== null) {
                if ($entityId !== null) {
                    $identity = \Karnoweb\Kanban\Models\CardIdentity::parse($entityId);
                    if (! in_array($identity->source, $board->sources, true)) {
                        throw new \InvalidArgumentException('Cannot broadcast a source outside the board allowlist.');
                    }
                }
                // Scope-wide subscribers may have different row visibility. Refetch privately.
                $eventType = 'BoardInvalidated';
                $entityId = null;
                $entityVersion = null;
            }
        }
        $hash = $this->hasher->hash($context->scopeKey);
        $event = $this->factory->make($eventType, $hash, $context->boardKey, $entityId, $entityVersion, $operationId);
        $this->events->dispatch(new KanbanBroadcastEvent(
            $this->channels->name($hash, $context->boardKey),
            $event->toArray(),
        ));

        return $event;
    }
}

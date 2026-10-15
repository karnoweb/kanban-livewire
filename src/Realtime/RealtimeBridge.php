<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Realtime;

use InvalidArgumentException;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\RealtimeEventData;
use Karnoweb\Kanban\Support\ScopeHasher;

final class RealtimeBridge
{
    public function __construct(
        private readonly EventDeduplicator $deduplicator,
        private readonly EventVersionGuard $versions,
        private readonly ScopeHasher $hasher,
        private readonly RefreshCoalescer $coalescer,
    ) {}

    /** @param array<string, mixed> $payload
     * @return array{decision: string, event: ?RealtimeEventData}
     */
    public function accept(array $payload, BoardContext $context, string $boardId, ?string $knownVersion): array
    {
        try {
            $event = RealtimeEventData::fromArray($payload);
        } catch (InvalidArgumentException) {
            return ['decision' => 'rejected', 'event' => null];
        }

        if (! hash_equals($this->hasher->hash($context->scopeKey), $event->scopeHash)) {
            return ['decision' => 'foreign-scope', 'event' => $event];
        }

        if ($event->boardId !== $boardId && $event->boardId !== $context->boardKey) {
            return ['decision' => 'foreign-board', 'event' => $event];
        }

        if ($this->deduplicator->isDuplicate($event->boardId, $event->eventId)) {
            return ['decision' => 'duplicate', 'event' => $event];
        }

        $version = $this->versions->decide($knownVersion, $event->entityVersion);
        $this->coalescer->push($version === 'gap' ? 'board' : 'card:'.($event->entityId ?? ''));

        return ['decision' => $version, 'event' => $event];
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Realtime;

use Karnoweb\Kanban\Data\RealtimeEventData;

final class BroadcastEventFactory
{
    /** @var list<string> */
    public const TYPES = [
        'CardCreated', 'CardUpdated', 'CardMoved', 'CardReordered', 'CardArchived', 'CardRestored',
        'StageCreated', 'StageUpdated', 'StageReordered', 'StageArchived', 'BoardInvalidated',
    ];

    public function make(
        string $eventType,
        string $scopeHash,
        string $boardId,
        ?string $entityId = null,
        ?string $entityVersion = null,
        ?string $operationId = null,
    ): RealtimeEventData {
        if (! in_array($eventType, self::TYPES, true)) {
            throw new \InvalidArgumentException('Realtime event type is not allowlisted.');
        }

        return new RealtimeEventData(
            schemaVersion: 1,
            eventId: 'evt-'.bin2hex(random_bytes(8)),
            operationId: $operationId,
            eventType: $eventType,
            scopeHash: $scopeHash,
            boardId: $boardId,
            entityId: $entityId,
            entityVersion: $entityVersion,
            occurredAt: gmdate('c'),
        );
    }
}

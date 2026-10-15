<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;
use Karnoweb\Kanban\Support\ValueGuard;

final readonly class RealtimeEventData
{
    public function __construct(
        public int $schemaVersion,
        public string $eventId,
        public ?string $operationId,
        public string $eventType,
        public string $scopeHash,
        public string $boardId,
        public ?string $entityId,
        public ?string $entityVersion,
        public string $occurredAt,
    ) {
        if ($schemaVersion !== 1) {
            throw new InvalidArgumentException('Unsupported realtime schema version.');
        }

        ValueGuard::id($eventId, 'eventId');
        ValueGuard::id($boardId, 'boardId');
        ValueGuard::requiredText($eventType, 'eventType', 80);
        ValueGuard::requiredText($scopeHash, 'scopeHash', 128);

        if ($operationId !== null) {
            ValueGuard::id($operationId, 'operationId');
        }
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $allowed = [
            'schemaVersion', 'eventId', 'operationId', 'eventType', 'scopeHash',
            'boardId', 'entityId', 'entityVersion', 'occurredAt',
        ];

        foreach (array_keys($payload) as $key) {
            if (! in_array($key, $allowed, true)) {
                throw new InvalidArgumentException('Realtime payload contains a forbidden field.');
            }
        }

        return new self(
            schemaVersion: (int) ($payload['schemaVersion'] ?? 0),
            eventId: (string) ($payload['eventId'] ?? ''),
            operationId: isset($payload['operationId']) ? (string) $payload['operationId'] : null,
            eventType: (string) ($payload['eventType'] ?? ''),
            scopeHash: (string) ($payload['scopeHash'] ?? ''),
            boardId: (string) ($payload['boardId'] ?? ''),
            entityId: isset($payload['entityId']) ? (string) $payload['entityId'] : null,
            entityVersion: isset($payload['entityVersion']) ? (string) $payload['entityVersion'] : null,
            occurredAt: (string) ($payload['occurredAt'] ?? ''),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schemaVersion' => $this->schemaVersion,
            'eventId' => $this->eventId,
            'operationId' => $this->operationId,
            'eventType' => $this->eventType,
            'scopeHash' => $this->scopeHash,
            'boardId' => $this->boardId,
            'entityId' => $this->entityId,
            'entityVersion' => $this->entityVersion,
            'occurredAt' => $this->occurredAt,
        ];
    }
}

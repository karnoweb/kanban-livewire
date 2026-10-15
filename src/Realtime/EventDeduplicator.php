<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Realtime;

final class EventDeduplicator
{
    /** @var array<string, true> */
    private array $seen = [];

    public function __construct(private readonly int $limit = 200) {}

    public function isDuplicate(string $boardId, string $eventId): bool
    {
        $key = $boardId.'|'.$eventId;

        if (isset($this->seen[$key])) {
            return true;
        }

        $this->seen[$key] = true;

        while (count($this->seen) > $this->limit) {
            array_shift($this->seen);
        }

        return false;
    }
}

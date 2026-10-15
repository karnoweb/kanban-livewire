<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;

final readonly class PageResult
{
    /** @param list<CardData> $items */
    public function __construct(
        public array $items,
        public ?string $nextCursor,
        public bool $hasMore,
        public ?int $totalCount = null,
        public bool $countExact = true,
        public ?string $queryHash = null,
    ) {
        foreach ($items as $item) {
            if (! $item instanceof CardData) {
                throw new InvalidArgumentException('Page items must be CardData.');
            }
        }

        if ($totalCount !== null && $totalCount < 0) {
            throw new InvalidArgumentException('totalCount cannot be negative.');
        }

        if ($hasMore && ($nextCursor === null || $nextCursor === '')) {
            throw new InvalidArgumentException('A further page requires a cursor.');
        }

        if ($countExact && $totalCount !== null && count($items) > $totalCount) {
            throw new InvalidArgumentException('Page size cannot exceed an exact totalCount.');
        }
    }

    public function isExact(): bool
    {
        return $this->countExact && $this->totalCount !== null;
    }

    public function loadedCount(): int
    {
        return count($this->items);
    }
}

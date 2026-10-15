<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;

final readonly class PaginationSettings
{
    public function __construct(
        public int $pageSize = 25,
        public int $maxPageSize = 100,
        public bool $virtualization = true,
        public int $virtualizationThreshold = 40,
        public int $overscan = 4,
    ) {
        if ($pageSize < 1 || $pageSize > $maxPageSize || $maxPageSize < 1) {
            throw new InvalidArgumentException('Pagination limits are invalid.');
        }
    }
}

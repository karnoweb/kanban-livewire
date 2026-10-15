<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;

final readonly class StageCount
{
    public function __construct(
        public string $stageId,
        public ?int $totalCount,
        public bool $exact,
    ) {
        if ($totalCount !== null && $totalCount < 0) {
            throw new InvalidArgumentException('Stage count cannot be negative.');
        }
    }
}

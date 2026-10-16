<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Contracts;

use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardQuery;

interface ColumnMetricsProvider
{
    /** @return array<string, int|float|null> */
    public function metrics(BoardContext $context, string $stageId, BoardQuery $query): array;
}

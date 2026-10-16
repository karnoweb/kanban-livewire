<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Contracts;

use Karnoweb\Kanban\Data\{BoardContext, BoardQuery};

interface SwimlaneProvider
{
    /** Bounded groups; each group remains a SQL filter applied before pagination. */
    public function groups(BoardContext $context, BoardQuery $query): array;
}

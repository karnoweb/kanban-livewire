<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Contracts;

use Karnoweb\Kanban\Data\BoardContext;

interface FilterOptionsProvider
{
    /** @return array<string, array<string, string>> Scoped, stable options independent of pagination. */
    public function filterOptions(BoardContext $context): array;
}

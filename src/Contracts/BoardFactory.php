<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Contracts;

use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardDefinition;

interface BoardFactory
{
    public function make(BoardContext $context): BoardDefinition;
}

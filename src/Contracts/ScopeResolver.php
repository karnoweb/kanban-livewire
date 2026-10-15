<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Contracts;

use Karnoweb\Kanban\Data\BoardContext;

interface ScopeResolver
{
    public function resolve(string $boardKey, ?string $instanceId = null): BoardContext;
}

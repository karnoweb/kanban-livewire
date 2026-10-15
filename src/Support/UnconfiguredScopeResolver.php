<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Karnoweb\Kanban\Contracts\ScopeResolver;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Exceptions\BoardAccessDenied;

final class UnconfiguredScopeResolver implements ScopeResolver
{
    public function resolve(string $boardKey, ?string $instanceId = null): BoardContext
    {
        throw new BoardAccessDenied('ScopeResolver is not configured.');
    }
}

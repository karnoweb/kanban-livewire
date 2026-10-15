<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Karnoweb\Kanban\Contracts\ScopeResolver;
use Karnoweb\Kanban\Data\BoardContext;

final class ContextFactory
{
    public function __construct(private readonly ScopeResolver $resolver) {}

    public function make(string $boardKey, ?string $instanceId = null, ?string $clientScopeKey = null): BoardContext
    {
        unset($clientScopeKey);

        return $this->resolver->resolve($boardKey, $instanceId);
    }
}

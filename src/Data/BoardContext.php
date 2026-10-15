<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use Karnoweb\Kanban\Support\ValueGuard;

final readonly class BoardContext
{
    public function __construct(
        public string $boardKey,
        public string $scopeKey,
        public string $actorId,
        public string $instanceId,
    ) {
        ValueGuard::id($boardKey, 'boardKey');
        ValueGuard::id($scopeKey, 'scopeKey');
        ValueGuard::id($actorId, 'actorId');
        ValueGuard::id($instanceId, 'instanceId');
    }
}

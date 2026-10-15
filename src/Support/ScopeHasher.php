<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

final class ScopeHasher
{
    public function hash(string $scopeKey): string
    {
        return hash('sha256', 'kanban-scope:'.$scopeKey);
    }
}

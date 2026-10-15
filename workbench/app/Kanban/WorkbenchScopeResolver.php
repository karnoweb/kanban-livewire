<?php

declare(strict_types=1);

namespace Workbench\App\Kanban;

use Karnoweb\Kanban\Contracts\ScopeResolver;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Exceptions\BoardAccessDenied;

final class WorkbenchScopeResolver implements ScopeResolver
{
    public function resolve(string $boardKey, ?string $instanceId = null): BoardContext
    {
        $user = auth()->user();

        if ($user === null) {
            throw new BoardAccessDenied('Authentication is required.');
        }

        return new BoardContext(
            $boardKey,
            (string) $user->tenant_id,
            (string) $user->getAuthIdentifier(),
            $instanceId ?: 'workbench',
        );
    }
}

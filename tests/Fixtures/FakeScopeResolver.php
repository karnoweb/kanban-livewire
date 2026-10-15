<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Fixtures;

use Karnoweb\Kanban\Contracts\ScopeResolver;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Exceptions\BoardAccessDenied;

final class FakeScopeResolver implements ScopeResolver
{
    public string $scope = 'tenant-a';

    public string $actor = 'user-a';

    /** @var list<string> */
    public array $deny = [];

    public function resolve(string $boardKey, ?string $instanceId = null): BoardContext
    {
        if (in_array($boardKey, $this->deny, true)) {
            throw new BoardAccessDenied;
        }

        return new BoardContext($boardKey, $this->scope, $this->actor, $instanceId ?: 'instance-1');
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Security;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class TenantTamperingTest extends TestCase
{
    use ScenarioSteps;

    public function test_tenant(): void
    {
        $this->kb038();
    }
}

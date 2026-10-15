<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class ScopeIsolationTest extends TestCase
{
    use ScenarioSteps;

    public function test_tenant(): void
    {
        $this->kb038();
    }

    public function test_same_card(): void
    {
        $this->kb004();
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class RealtimeContractTest extends TestCase
{
    use ScenarioSteps;

    public function test_schema(): void
    {
        $this->kb035();
    }

    public function test_order(): void
    {
        $this->kb036();
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Performance;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class RealtimeBurstTest extends TestCase
{
    use ScenarioSteps;

    public function test_burst(): void
    {
        $this->kb035();
    }
}

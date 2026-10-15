<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Performance;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class LargeBoardTest extends TestCase
{
    use ScenarioSteps;

    public function test_ten_thousand(): void
    {
        $this->kb010();
    }
}

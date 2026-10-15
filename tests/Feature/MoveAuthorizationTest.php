<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class MoveAuthorizationTest extends TestCase
{
    use ScenarioSteps;

    public function test_allowed(): void
    {
        $this->kb011();
    }

    public function test_denied(): void
    {
        $this->kb012();
    }
}

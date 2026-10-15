<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Security;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class XssAndUrlTest extends TestCase
{
    use ScenarioSteps;

    public function test_xss(): void
    {
        $this->kb039();
    }
}

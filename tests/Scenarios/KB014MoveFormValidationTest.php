<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Scenarios;

final class KB014MoveFormValidationTest extends \Karnoweb\Kanban\Tests\TestCase
{
    use ScenarioSteps;

    public function test_scenario(): void
    {
        $this->kb014();
    }
}

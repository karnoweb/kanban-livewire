<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Browser;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class AccessibilityTest extends TestCase
{
    use ScenarioSteps;

    public function test_keyboard(): void
    {
        $this->kb041();
    }
}

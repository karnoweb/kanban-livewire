<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Browser;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class AllThemesAndDirectionsTest extends TestCase
{
    use ScenarioSteps;

    public function test_themes(): void
    {
        $this->kb040();
    }
}

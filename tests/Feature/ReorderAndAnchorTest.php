<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class ReorderAndAnchorTest extends TestCase
{
    use ScenarioSteps;

    public function test_disabled(): void
    {
        $this->kb017();
    }

    public function test_anchor(): void
    {
        $this->kb018();
    }
}

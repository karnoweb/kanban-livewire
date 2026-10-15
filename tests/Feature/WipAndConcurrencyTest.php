<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class WipAndConcurrencyTest extends TestCase
{
    use ScenarioSteps;

    public function test_strict(): void
    {
        $this->kb022();
    }

    public function test_last_slot(): void
    {
        $this->kb023();
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class BulkActionsTest extends TestCase
{
    use ScenarioSteps;

    public function test_partial(): void
    {
        $this->kb032();
    }

    public function test_atomic(): void
    {
        $this->kb033();
    }
}

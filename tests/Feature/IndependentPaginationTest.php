<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class IndependentPaginationTest extends TestCase
{
    use ScenarioSteps;

    public function test_page_size(): void
    {
        $this->kb005();
    }

    public function test_next_page(): void
    {
        $this->kb006();
    }
}

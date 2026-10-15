<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class FiltersAndSearchTest extends TestCase
{
    use ScenarioSteps;

    public function test_filters(): void
    {
        $this->kb007();
    }

    public function test_stale_search(): void
    {
        $this->kb008();
    }

    public function test_sort_guard(): void
    {
        $this->kb009();
    }
}

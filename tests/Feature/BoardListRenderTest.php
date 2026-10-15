<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class BoardListRenderTest extends TestCase
{
    use ScenarioSteps;

    public function test_board_and_list(): void
    {
        $this->kb001();
    }

    public function test_switch_keeps_filters(): void
    {
        $this->kb002();
    }

    public function test_disabled_list(): void
    {
        $this->kb003();
    }
}

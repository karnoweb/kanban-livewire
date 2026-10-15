<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Browser;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class FormsAndFocusTest extends TestCase
{
    use ScenarioSteps;

    public function test_form(): void
    {
        $this->kb026();
    }

    public function test_motion(): void
    {
        $this->kb042();
    }
}

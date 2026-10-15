<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class MoveConfirmationTest extends TestCase
{
    use ScenarioSteps;

    public function test_cancel(): void
    {
        $this->kb013();
    }

    public function test_invalid_form(): void
    {
        $this->kb014();
    }

    public function test_note(): void
    {
        $this->kb015();
    }
}

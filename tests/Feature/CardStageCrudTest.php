<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class CardStageCrudTest extends TestCase
{
    use ScenarioSteps;

    public function test_create(): void
    {
        $this->kb024();
    }

    public function test_escape(): void
    {
        $this->kb025();
    }

    public function test_validation(): void
    {
        $this->kb026();
    }

    public function test_stage(): void
    {
        $this->kb027();
    }

    public function test_archive(): void
    {
        $this->kb030();
    }
}

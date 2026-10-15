<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class IdempotencyAndUnknownOutcomeTest extends TestCase
{
    use ScenarioSteps;

    public function test_idempotent(): void
    {
        $this->kb019();
    }

    public function test_unknown(): void
    {
        $this->kb020();
    }

    public function test_conflict(): void
    {
        $this->kb021();
    }
}

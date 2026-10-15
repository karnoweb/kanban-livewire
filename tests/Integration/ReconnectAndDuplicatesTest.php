<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Integration;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class ReconnectAndDuplicatesTest extends TestCase
{
    use ScenarioSteps;

    public function test_duplicates(): void
    {
        $this->kb035();
    }

    public function test_order(): void
    {
        $this->kb036();
    }

    public function test_reconnect(): void
    {
        $this->kb037();
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Integration;

use Karnoweb\Kanban\Tests\Scenarios\ScenarioSteps;
use Karnoweb\Kanban\Tests\TestCase;

final class ReverbTwoUsersTest extends TestCase
{
    use ScenarioSteps;

    public function test_two_users(): void
    {
        $this->kb034();
    }
}

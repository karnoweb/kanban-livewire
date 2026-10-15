<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Support\ContextFactory;
use Karnoweb\Kanban\Tests\TestCase;

final class HostScopeContextTest extends TestCase
{
    public function test_client_scope_is_ignored(): void
    {
        $context = $this->app->make(ContextFactory::class)->make('crm.deals', 'instance-9', 'tenant-b');

        $this->assertSame('tenant-a', $context->scopeKey);
        $this->assertSame('user-a', $context->actorId);
        $this->assertSame('crm.deals', $context->boardKey);
    }
}

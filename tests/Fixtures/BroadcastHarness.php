<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Fixtures;

use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\RealtimeEventData;
use Karnoweb\Kanban\Realtime\BroadcastEventFactory;
use Karnoweb\Kanban\Support\ScopeHasher;

final class BroadcastHarness
{
    public static function event(BoardContext $context, string $type, ?string $entityId = null, ?string $version = null, ?string $operationId = null): RealtimeEventData
    {
        return app(BroadcastEventFactory::class)->make(
            $type,
            app(ScopeHasher::class)->hash($context->scopeKey),
            $context->boardKey,
            $entityId,
            $version,
            $operationId,
        );
    }
}

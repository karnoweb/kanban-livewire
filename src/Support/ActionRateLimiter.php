<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Illuminate\Cache\RateLimiter;
use Karnoweb\Kanban\Data\BoardContext;

final class ActionRateLimiter
{
    public function __construct(private readonly ?RateLimiter $limiter = null) {}

    public function tooMany(BoardContext $context, ?int $perMinute): bool
    {
        if ($perMinute === null || $perMinute < 1 || $this->limiter === null) {
            return false;
        }

        $key = 'kanban:'.$context->scopeKey.':'.$context->actorId.':'.$context->boardKey;

        if ($this->limiter->tooManyAttempts($key, $perMinute)) {
            return true;
        }

        $this->limiter->hit($key, 60);

        return false;
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Realtime;

final class EventVersionGuard
{
    public function decide(?string $current, ?string $incoming): string
    {
        if ($incoming === null || $incoming === '') {
            return 'gap';
        }

        if ($current === null || $current === '') {
            return 'apply';
        }

        if ($incoming === $current) {
            return 'stale';
        }

        if (ctype_digit($incoming) && ctype_digit($current)) {
            $next = (int) $incoming;
            $known = (int) $current;

            if ($next <= $known) {
                return 'stale';
            }

            if ($next > $known + 1) {
                return 'gap';
            }

            return 'apply';
        }

        return strcmp($incoming, $current) > 0 ? 'apply' : 'stale';
    }
}

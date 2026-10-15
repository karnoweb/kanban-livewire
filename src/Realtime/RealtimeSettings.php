<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Realtime;

final readonly class RealtimeSettings
{
    public function __construct(
        public bool $enabled = false,
        public string $refresh = 'invalidate',
        public bool $reconnect = true,
        public int $dedupeLimit = 200,
        public int $coalesceMs = 50,
    ) {}
}

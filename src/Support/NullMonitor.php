<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Karnoweb\Kanban\Contracts\Monitor;

final class NullMonitor implements Monitor
{
    public function record(string $operation, float $durationMs, string $status, array $context = []): void {}
}

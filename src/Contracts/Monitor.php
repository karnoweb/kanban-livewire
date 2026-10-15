<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Contracts;

interface Monitor
{
    /** @param array<string, scalar|null> $context */
    public function record(string $operation, float $durationMs, string $status, array $context = []): void;
}

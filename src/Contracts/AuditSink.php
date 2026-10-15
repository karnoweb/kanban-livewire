<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Contracts;

interface AuditSink
{
    /** @param array<string, scalar|null> $redacted */
    public function record(string $operationId, string $action, string $status, array $redacted = []): void;
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Karnoweb\Kanban\Contracts\AuditSink;

final class NullAuditSink implements AuditSink
{
    public function record(string $operationId, string $action, string $status, array $redacted = []): void {}
}

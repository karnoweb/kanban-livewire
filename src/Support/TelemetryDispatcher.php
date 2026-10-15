<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Karnoweb\Kanban\Contracts\AuditSink;
use Karnoweb\Kanban\Contracts\Monitor;

final class TelemetryDispatcher
{
    /** @var list<string> */
    private const SENSITIVE = ['title', 'description', 'metadata', 'formData', 'note', 'attachment', 'url', 'email', 'token', 'password'];

    public function __construct(
        private readonly Monitor $monitor,
        private readonly AuditSink $audit,
    ) {}

    /** @param array<string, mixed> $context */
    public function timing(string $operation, float $startedAt, string $status, array $context = []): void
    {
        $this->monitor->record($operation, (microtime(true) - $startedAt) * 1000, $status, $this->redact($context));
    }

    /** @param array<string, mixed> $context */
    public function audit(string $operationId, string $action, string $status, array $context = []): void
    {
        $this->audit->record($operationId, $action, $status, $this->redact($context));
    }

    /** @param array<string, mixed> $context
     * @return array<string, scalar|null>
     */
    public function redact(array $context): array
    {
        $clean = [];

        foreach ($context as $key => $value) {
            if (! is_string($key) || in_array(strtolower($key), self::SENSITIVE, true)) {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }
}

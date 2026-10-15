<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Karnoweb\Kanban\Data\ActionResult;

final class IdempotencyGuard
{
    /** @var array<string, array<string, true>> */
    private array $inflight = [];

    /** @var array<string, ActionResult> */
    private array $results = [];

    public function begin(string $instanceId, string $operationId): bool
    {
        if (isset($this->results[$operationId]) || isset($this->inflight[$instanceId][$operationId])) {
            return false;
        }

        $this->inflight[$instanceId][$operationId] = true;

        return true;
    }

    public function finish(string $instanceId, string $operationId, ActionResult $result): void
    {
        unset($this->inflight[$instanceId][$operationId]);
        $this->results[$operationId] = $result;
    }

    public function remembered(string $operationId): ?ActionResult
    {
        return $this->results[$operationId] ?? null;
    }

    public function inflight(string $instanceId, string $operationId): bool
    {
        return isset($this->inflight[$instanceId][$operationId]);
    }
}

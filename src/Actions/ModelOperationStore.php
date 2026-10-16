<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Actions;

use Illuminate\Contracts\Cache\Repository;
use Karnoweb\Kanban\Data\{ActionResult, BoardContext};

/** Shared cache is required across workers. Pending entries prevent unsafe crash retries. */
final class ModelOperationStore implements \Karnoweb\Kanban\Contracts\ModelOperationStore
{
    public function __construct(private readonly Repository $cache) {}

    public function key(BoardContext $context, string $operationId): string
    {
        return 'kanban:operation:'.hash('sha256', json_encode([$context->boardKey, $context->scopeKey, $context->actorId, $operationId], JSON_THROW_ON_ERROR));
    }

    public function result(BoardContext $context, string $operationId): ?ActionResult
    {
        $value = $this->cache->get($this->key($context, $operationId));

        return is_array($value) ? ($value['result'] ?? ActionResult::unknown($operationId)) : null;
    }

    public function remembered(BoardContext $context, string $operationId): ?array
    {
        return $this->cache->get($this->key($context, $operationId));
    }

    public function pending(BoardContext $context, string $operationId, string $fingerprint): void
    {
        $this->cache->forever($this->key($context, $operationId), ['fingerprint' => $fingerprint]);
    }

    public function finish(BoardContext $context, string $operationId, string $fingerprint, ActionResult $result): void
    {
        // Results intentionally contain no model/title or executable definition.
        $this->cache->forever($this->key($context, $operationId), ['fingerprint' => $fingerprint, 'result' => $result]);
    }
}

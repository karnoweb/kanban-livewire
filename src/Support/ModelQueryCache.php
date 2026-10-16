<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Illuminate\Contracts\Cache\Repository;
use Karnoweb\Kanban\Data\{BoardContext, BoardDefinition, BoardQuery};

final class ModelQueryCache
{
    public function __construct(private readonly Repository $cache) {}

    public function key(BoardContext $context, BoardDefinition $board, BoardQuery $query, string $kind): string
    {
        $revisionKey = $this->revisionKey($context);
        $this->cache->add($revisionKey, bin2hex(random_bytes(16)), 86400);
        $revision = $this->cache->get($revisionKey);
        $definitions = app(\Karnoweb\Kanban\Models\BoardSources::class)->definitions($board);
        $signatures = [];
        foreach ($definitions as $source => $definition) {
            $visibility = $definition->query($context);
            $signatures[$source] = [$definition->model, $definition->fieldMap, $definition->metricDefinitions,
                $visibility->toSql(), $visibility->getBindings()];
        }

        return 'kanban:query:'.hash('sha256', json_encode([$context->scopeKey, $context->actorId, $context->boardKey,
            $board->sources, $board->stageMap, $signatures, $query->view, $query->filters, $query->search, $query->sort, $query->direction,
            $query->limit, $query->hash(), $kind, $revision], JSON_THROW_ON_ERROR));
    }

    public function invalidate(BoardContext $context): void
    {
        $this->cache->forever($this->revisionKey($context), bin2hex(random_bytes(16)));
    }

    public function get(string $key): mixed
    {
        return $this->cache->get($key);
    }

    public function put(string $key, mixed $result, int $ttl): void
    {
        $this->cache->put($key, $result, max(1, $ttl));
    }

    private function revisionKey(BoardContext $context): string
    {
        return 'kanban:revision:'.hash('sha256', $context->scopeKey);
    }
}

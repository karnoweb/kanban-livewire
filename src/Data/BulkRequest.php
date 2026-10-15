<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;
use Karnoweb\Kanban\Enums\BulkMode;
use Karnoweb\Kanban\Support\ValueGuard;

final readonly class BulkRequest
{
    /**
     * @param list<string> $ids
     * @param array<string, string> $expectedVersions
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $operationId,
        public array $ids,
        public BulkMode $mode,
        public string $action,
        public array $expectedVersions = [],
        public array $payload = [],
    ) {
        ValueGuard::id($operationId, 'operationId');
        $cap = (int) config('kanban.limits.bulk_ids', 100);

        if ($ids === [] || count($ids) > $cap) {
            throw new InvalidArgumentException('Bulk selection is empty or above the cap.');
        }

        foreach ($ids as $id) {
            ValueGuard::id($id, 'cardId');
        }

        ValueGuard::id($action, 'bulk.action');
        ValueGuard::formData($payload);
    }
}

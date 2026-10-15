<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use InvalidArgumentException;

final class QueryNormalizer
{
    public function search(?string $search): ?string
    {
        if ($search === null) {
            return null;
        }

        $search = trim($search);
        $max = (int) config('kanban.limits.search_length', 200);

        if (strlen($search) > $max) {
            throw new InvalidArgumentException('Search exceeds the length limit.');
        }

        return $search === '' ? null : $search;
    }

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function filters(array $filters): array
    {
        $allowed = config('kanban.filter_fields', []);
        $clean = [];

        foreach ($filters as $field => $value) {
            if (! is_string($field) || ! in_array($field, $allowed, true)) {
                throw new InvalidArgumentException('Filter field is not allowlisted.');
            }

            if (is_object($value)) {
                throw new InvalidArgumentException('Filter value must be scalar.');
            }

            $clean[$field] = $value;
        }

        return $clean;
    }

    public function sort(string $sort): string
    {
        $allowed = config('kanban.sort_fields', ['manualOrder']);

        if (! in_array($sort, $allowed, true)) {
            throw new InvalidArgumentException('Sort field is not allowlisted.');
        }

        return $sort;
    }
}

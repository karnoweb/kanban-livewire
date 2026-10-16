<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;

final readonly class EnterpriseSettings
{
    public function __construct(
        public array $models = ['auto_discover' => false, 'allow_unlisted_sources' => false, 'default_board_sources' => []],
        public array $eloquent = ['enabled' => true, 'query_mode' => 'db_first', 'default_page_size' => 25, 'cursor' => ['signed' => true, 'bind_to_scope' => true, 'ttl' => 3600], 'count_mode' => 'exact', 'eager_load' => 'declared_only', 'allow_raw_sort' => false, 'allow_raw_filter' => false],
        public array $ordering = ['strategy' => 'rank', 'rank_column' => null, 'rebalance' => 'local', 'mixed_sources_require_adapter' => true],
        public array $metrics = ['enabled' => true, 'cache_ttl' => 0],
        public array $swimlanes = ['enabled' => true, 'max_groups' => 30],
        public array $cache = ['enabled' => false, 'ttl' => 30],
    ) {
        if (($models['auto_discover'] ?? false) || ($models['allow_unlisted_sources'] ?? false) || ($models['default_board_sources'] ?? []) !== []
            || ! ($eloquent['cursor']['signed'] ?? true) || ! ($eloquent['cursor']['bind_to_scope'] ?? true)
            || ($eloquent['allow_raw_sort'] ?? false) || ($eloquent['allow_raw_filter'] ?? false)
            || ($eloquent['query_mode'] ?? 'db_first') !== 'db_first' || ($eloquent['count_mode'] ?? 'exact') !== 'exact'
            || ($eloquent['eager_load'] ?? 'declared_only') !== 'declared_only'
            || ($ordering['rebalance'] ?? 'local') !== 'local' || ! ($ordering['mixed_sources_require_adapter'] ?? true)) {
            throw new InvalidArgumentException('Unsafe or unsupported model settings.');
        }
        if (($ordering['rank_column'] ?? null) !== null || (int) ($eloquent['default_page_size'] ?? 25) < 1) {
            throw new InvalidArgumentException('Rank columns must be declared on each model; page size must be positive.');
        }
        if ((int) ($cache['ttl'] ?? 30) < 1 || (int) ($swimlanes['max_groups'] ?? 30) < 1 || (int) ($eloquent['cursor']['ttl'] ?? 3600) < 1) {
            throw new InvalidArgumentException('Model setting limits must be positive.');
        }
    }

    public static function fromArray(array $settings): self
    {
        $defaults = new self;
        $values = [];
        foreach (get_object_vars($defaults) as $name => $base) {
            $global = config('kanban.'.$name, []);
            $local = $settings[$name] ?? [];
            if (! is_array($global) || ! is_array($local)) {
                throw new InvalidArgumentException('Model settings groups must be arrays.');
            }
            $values[$name] = array_replace_recursive($base, $global, $local);
        }
        $values['swimlanes']['max_groups'] = min((int) $values['swimlanes']['max_groups'], (int) config('kanban.swimlanes.max_groups', 30));

        return new self(...$values);
    }
}

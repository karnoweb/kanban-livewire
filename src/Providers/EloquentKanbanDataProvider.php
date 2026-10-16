<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Providers;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Karnoweb\Kanban\Contracts\ColumnMetricsProvider;
use Karnoweb\Kanban\Contracts\DataProvider;
use Karnoweb\Kanban\Data\{ActionResult, BoardContext, BoardData, BoardDefinition, BoardQuery, CardData, CountResult, PageResult, StageCount};
use Karnoweb\Kanban\Models\{BoardSources, CardIdentity, KanbanModelDefinition};
use Karnoweb\Kanban\Support\ModelCursor;

final class EloquentKanbanDataProvider implements DataProvider, ColumnMetricsProvider, \Karnoweb\Kanban\Contracts\SwimlaneProvider
{
    public function __construct(private readonly BoardSources $sources, private readonly ModelCursor $cursors) {}

    public function board(BoardContext $context): BoardData
    {
        return $this->sources->board($context)->board;
    }

    public function stages(BoardContext $context, BoardQuery $query): array
    {
        $board = $this->sources->board($context);
        $stages = [];
        foreach ($this->definitions($board, $query) as $source => $definition) {
            foreach ($definition->stageDefinitions as $value => $_) {
                $stage = $definition->stage((string) $value, $board->board->id, $this->sources->mappedStage($board, $source, (string) $value));
                if ($stage->visible) {
                    if (! isset($stages[$stage->id])) {
                        $stages[$stage->id] = $stage;
                    } else {
                        $previous = $stages[$stage->id];
                        $strict = [];
                        foreach ([$previous, $stage] as $candidate) {
                            if ($candidate->wipMode === \Karnoweb\Kanban\Enums\WipMode::Strict && $candidate->wipLimit !== null) {
                                $strict[] = $candidate->wipLimit;
                            }
                        }
                        if ($strict !== []) {
                            $values = get_object_vars($previous);
                            $values['wipLimit'] = min($strict);
                            $values['wipMode'] = \Karnoweb\Kanban\Enums\WipMode::Strict;
                            $stages[$stage->id] = new \Karnoweb\Kanban\Data\StageData(...$values);
                        }
                    }
                }
            }
        }
        uasort($stages, fn ($a, $b) => $a->order <=> $b->order ?: strcmp($a->id, $b->id));

        return array_values($stages);
    }

    public function stageCards(BoardContext $context, string $stageId, BoardQuery $query): PageResult
    {
        return $this->page($context, $query, $stageId, false);
    }

    public function listCards(BoardContext $context, BoardQuery $query): PageResult
    {
        return $this->page($context, $query, null, false);
    }

    public function archives(BoardContext $context, BoardQuery $query): PageResult
    {
        return $this->page($context, $query, null, true);
    }

    public function definitions(BoardDefinition $board, BoardQuery $query): array
    {
        if (! $board->settings->model->eloquent['enabled']) {
            return [];
        }
        $selected = $query->filters['source'] ?? null;
        if ($selected !== null) {
            $selected = is_string($selected) ? [$selected] : $selected;
            if (! is_array($selected) || array_filter($selected, fn ($s) => ! is_string($s)) !== []) {
                throw new InvalidArgumentException('Invalid source selection.');
            }
            $selected = array_values(array_unique($selected));
        }

        return $this->sources->definitions($board, $selected);
    }

    /** All read paths, including counts and individual cards, use this scope. */
    public function scoped(KanbanModelDefinition $definition, BoardDefinition $board, BoardContext $context, bool $archived = false): Builder
    {
        $builder = $definition->query($context);
        $stage = $definition->fieldMap['stage'];
        $visible = [];
        foreach ($definition->stageDefinitions as $value => $_) {
            if ($definition->stage((string) $value, $board->board->id)->visible) {
                $visible[] = (string) $value;
            }
        }
        $builder->whereIn($stage, $visible);
        if (isset($definition->fieldMap['archivedAt'])) {
            $archived ? $builder->whereNotNull($definition->fieldMap['archivedAt']) : $builder->whereNull($definition->fieldMap['archivedAt']);
        } elseif ($archived) {
            $builder->whereRaw('1 = 0');
        }

        return $builder;
    }

    public function filtered(KanbanModelDefinition $definition, BoardDefinition $board, BoardContext $context, BoardQuery $query, ?string $stageId, bool $archived): Builder
    {
        if ($query->search !== null && strlen($query->search) > $board->settings->security->searchLength) {
            throw new InvalidArgumentException('Search exceeds this board length limit.');
        }
        $builder = $this->scoped($definition, $board, $context, $archived);
        if ($stageId !== null) {
            $builder->whereIn($definition->fieldMap['stage'], $this->sources->nativeStages($board, $definition, $stageId));
        }
        if ($query->search !== null && $query->search !== '') {
            if ($definition->searchFields === []) {
                $builder->whereRaw('1 = 0');
            } else {
                $builder->where(function (Builder $search) use ($definition, $query) {
                    foreach ($definition->searchFields as $column) {
                        $search->orWhere($column, 'like', '%'.$query->search.'%');
                    }
                });
            }
        }
        foreach ($query->filters as $name => $value) {
            if ($name === 'source' || $value === null || $value === '') {
                continue;
            }
            if ($name === 'group') {
                $field = $board->swimlane?->field;
                $column = $field === null ? null : ($definition->fieldMap[$field] ?? null);
                if ($column === null || ! is_scalar($value)) {
                    throw new InvalidArgumentException('Swimlane filter is not declared.');
                }
                $value === '__ungrouped__' ? $builder->whereNull($column) : $builder->where($column, $value);
                continue;
            }
            if ($name === 'stage' || $name === 'status') {
                $values = is_array($value) ? $value : [$value];
                $native = [];
                foreach ($values as $mapped) {
                    if (! is_string($mapped)) {
                        throw new InvalidArgumentException('Invalid stage filter.');
                    }
                    $native = array_merge($native, $this->sources->nativeStages($board, $definition, $mapped));
                }
                $builder->whereIn($definition->fieldMap['stage'], $native);
                continue;
            }
            $column = $definition->filterMap[$name] ?? null;
            if ($column === null) {
                throw new InvalidArgumentException('Filter is not declared by this model.');
            }
            if (is_array($value)) {
                if (count($value) > 100 || array_filter($value, fn ($v) => ! is_scalar($v)) !== []) {
                    throw new InvalidArgumentException('Invalid filter values.');
                }
                $builder->whereIn($column, $value);
            } elseif (is_scalar($value)) {
                $operator = str_ends_with($name, 'From') ? '>=' : (str_ends_with($name, 'To') ? '<=' : '=');
                $builder->where($column, $operator, $value);
            } else {
                throw new InvalidArgumentException('Invalid filter value.');
            }
        }

        return $builder;
    }

    public function counts(BoardContext $context, BoardQuery $query, bool $useCache = true): CountResult
    {
        $board = $this->sources->board($context);
        $cache = app(\Karnoweb\Kanban\Support\ModelQueryCache::class);
        $cacheKey = $useCache && $board->settings->model->cache['enabled'] ? $cache->key($context, $board, $query, 'counts') : null;
        if ($cacheKey !== null && ($cached = $cache->get($cacheKey)) instanceof CountResult) {
            return $cached;
        }
        $counts = [];
        foreach ($this->definitions($board, $query) as $source => $definition) {
            $column = $definition->fieldMap['stage'];
            $rows = $this->filtered($definition, $board, $context, $query, null, false)
                ->reorder()->select($column)->selectRaw('COUNT(*) AS aggregate')->groupBy($column)->toBase()->get();
            foreach ($rows as $row) {
                $id = $this->sources->mappedStage($board, $source, (string) $row->$column);
                $counts[$id] = ($counts[$id] ?? 0) + (int) $row->aggregate;
            }
        }
        $items = [];
        foreach ($this->stages($context, $query) as $stage) {
            $items[] = new StageCount($stage->id, $counts[$stage->id] ?? 0, true);
        }

        $result = new CountResult($items, array_sum($counts), true);
        if ($cacheKey !== null) {
            $cache->put($cacheKey, $result, (int) $board->settings->model->cache['ttl']);
        }

        return $result;
    }

    public function card(BoardContext $context, string $cardId): ?CardData
    {
        try {
            $id = CardIdentity::parse($cardId);
        } catch (InvalidArgumentException) {
            return null;
        }
        $board = $this->sources->board($context);
        if (! $board->settings->model->eloquent['enabled']) {
            return null;
        }
        if (! in_array($id->source, $board->sources ?? [], true)) {
            return null;
        }
        $definition = $this->sources->definitions($board, [$id->source])[$id->source];
        $record = $this->scoped($definition, $board, $context)->with($definition->relations)
            ->where($definition->fieldMap['id'], $id->recordId)->first($this->columns($definition));
        if ($record === null || ! $definition->allows('view', $record, $context)) {
            return null;
        }

        return $this->toCard($record, $definition, $board, $context);
    }

    public function capabilities(BoardContext $context): array
    {
        $board = $this->sources->board($context);
        $definitions = $this->sources->definitions($board);
        $manual = $this->sources->rankStrategy($board)->supports($board, $definitions);

        return ['read' => true, 'manualOrder' => $manual, 'reorderReason' => $manual ? null : 'rank_storage_required', 'sources' => array_keys($definitions)];
    }

    public function operationStatus(BoardContext $context, string $operationId): ?ActionResult
    {
        if (! $this->sources->board($context)->settings->model->eloquent['enabled']) {
            return null;
        }
        $result = app(\Karnoweb\Kanban\Contracts\ModelOperationStore::class)->result($context, $operationId);
        if ($result === null || $result->status === \Karnoweb\Kanban\Enums\ActionStatus::UNKNOWN_OUTCOME) {
            return $result;
        }
        $allowed = $this->sources->board($context)->sources ?? [];
        foreach ($result->affectedIds as $id) {
            try {
                if (! in_array(CardIdentity::parse($id)->source, $allowed, true)) {
                    return null;
                }
            } catch (InvalidArgumentException) {
                return null;
            }
        }

        return $result;
    }

    public function metrics(BoardContext $context, string $stageId, BoardQuery $query): array
    {
        $board = $this->sources->board($context);
        if (! $board->settings->model->metrics['enabled']) {
            return [];
        }
        $cache = app(\Karnoweb\Kanban\Support\ModelQueryCache::class);
        $ttl = (int) $board->settings->model->metrics['cache_ttl'];
        $key = $ttl > 0 ? $cache->key($context, $board, $query, 'metrics:'.$stageId) : null;
        if ($ttl > 0 && is_array($cached = $cache->get($key))) {
            return $cached;
        }
        $results = [];
        foreach ($this->definitions($board, $query) as $source => $definition) {
            foreach ($definition->metricDefinitions as $name => $metric) {
                $value = $this->filtered($definition, $board, $context, $query, $stageId, false)->toBase()
                    ->{$metric['aggregate']}($metric['column']);
                $results[$source.'.'.$name] = $value === null ? null : (float) $value;
            }
        }

        if ($ttl > 0) {
            $cache->put($key, $results, $ttl);
        }

        return $results;
    }

    public function groups(BoardContext $context, BoardQuery $query): array
    {
        $board = $this->sources->board($context);
        if ($board->swimlane === null || ! $board->settings->model->swimlanes['enabled']) {
            return [];
        }
        $groups = [];
        foreach ($this->definitions($board, $query) as $definition) {
            $column = $definition->fieldMap[$board->swimlane->field] ?? null;
            if ($column === null) {
                throw new InvalidArgumentException('Swimlane field is not mapped by every source.');
            }
            $values = $this->filtered($definition, $board, $context, new BoardQuery(search: $query->search,
                filters: array_diff_key($query->filters, ['group' => true])), null, false)
                ->reorder()->select($column)->distinct()->orderBy($column)->limit($board->swimlane->maxGroups + 1)->toBase()->pluck($column);
            foreach ($values as $value) {
                $key = $value === null ? '__ungrouped__' : (string) $value;
                $groups[$key] = ['id' => $key, 'title' => $value === null ? __('kanban::kanban.ungrouped') : (string) $value];
            }
        }
        ksort($groups, SORT_STRING);
        $maxGroups = min($board->swimlane->maxGroups, (int) $board->settings->model->swimlanes['max_groups']);

        return ['items' => array_values(array_slice($groups, 0, $maxGroups, true)),
            'hasMore' => count($groups) > $maxGroups];
    }

    public function toCard(Model $record, KanbanModelDefinition $definition, BoardDefinition $board, BoardContext $context): CardData
    {
        $record->preventsLazyLoading = true;
        $value = function (string $field) use ($record, $definition): ?string {
            $value = isset($definition->fieldMap[$field]) ? $record->getAttribute($definition->fieldMap[$field]) : null;

            return $value instanceof BackedEnum ? (string) $value->value : ($value === null ? null : (string) $value);
        };
        $extras = $definition->presentation === null ? [] : ($definition->presentation)($record, $context);
        $metadata = array_replace($extras['metadata'] ?? [], ['source' => $definition->source]);
        if (strlen(json_encode($metadata, JSON_THROW_ON_ERROR)) > $board->settings->security->metadataBytes) {
            throw new InvalidArgumentException('Metadata exceeds this board size limit.');
        }
        foreach ($extras['attachments'] ?? [] as $attachment) {
            foreach ([$attachment->url, $attachment->thumbnailUrl] as $url) {
                $scheme = $url === null ? null : parse_url($url, PHP_URL_SCHEME);
                if ($scheme !== null && $scheme !== false && ! in_array(strtolower($scheme), $board->settings->security->allowedUrlSchemes, true)) {
                    throw new InvalidArgumentException('Attachment scheme is not allowed by this board.');
                }
            }
        }
        $permissions = [];
        foreach (['view', 'update', 'move', 'reorder', 'archive', 'restore'] as $ability) {
            $permissions[$ability] = $definition->allows($ability, $record, $context);
        }

        return new CardData(
            id: (new CardIdentity($definition->source, (string) $value('id')))->encode(), boardId: $board->board->id,
            stageId: $this->sources->mappedStage($board, $definition->source, (string) $value('stage')),
            title: (string) $value('title'), description: $value('description'), priority: $value('priority'),
            badges: $extras['badges'] ?? [], assignees: $extras['assignees'] ?? [], attachments: $extras['attachments'] ?? [],
            createdAt: $value('createdAt'), dueAt: $value('dueAt'),
            metadata: $metadata,
            actions: $extras['actions'] ?? [], permissions: $permissions,
            version: $value('version'), manualOrder: $value('rank'), archived: $value('archivedAt') !== null,
        );
    }

    private function columns(KanbanModelDefinition $definition): array
    {
        return $definition->selected === ['*'] ? ['*'] : array_values(array_unique(array_merge($definition->selected, array_values($definition->fieldMap))));
    }

    private function sortColumn(KanbanModelDefinition $definition, BoardQuery $query): string
    {
        if (! in_array($query->sort, $definition->sortFields, true) && $query->sort !== 'id') {
            throw new InvalidArgumentException('Sort is not declared by this model.');
        }
        $field = $query->sort === 'manualOrder' ? 'rank' : $query->sort;

        return $definition->fieldMap[$field] ?? ($query->sort === 'manualOrder' ? $definition->fieldMap['id'] : throw new InvalidArgumentException('Sort column is not mapped.'));
    }

    /** One binary text order for SQL windows, cursor predicates and the merge comparator. */
    private function orderExpression(Builder $builder, string $column, bool $text): string
    {
        $wrapped = $builder->getQuery()->getGrammar()->wrap($column);
        if (! $text) {
            return $wrapped;
        }

        return match ($builder->getConnection()->getDriverName()) {
            'sqlite' => $wrapped.' COLLATE BINARY',
            'mysql', 'mariadb' => 'CAST('.$wrapped.' AS BINARY)',
            'pgsql' => $wrapped.' COLLATE "C"',
            default => throw new InvalidArgumentException('This driver needs a host provider for consistent text ordering.'),
        };
    }

    private function page(BoardContext $context, BoardQuery $query, ?string $stageId, bool $archived): PageResult
    {
        $board = $this->sources->board($context);
        $definitions = $this->definitions($board, $query);
        $binding = hash('sha256', json_encode([$context->boardKey, $board->board->id, $context->scopeKey, $context->actorId,
            $board->sources, $board->stageMap, array_keys($definitions), $stageId, $archived, $query->view, $query->filters,
            $query->search, $query->sort, $query->direction, $query->limit, $query->hash()], JSON_THROW_ON_ERROR));
        $positions = $this->cursors->decode($query->cursor, $binding);
        $candidates = [];
        $total = 0;
        $limit = min($query->limit, $board->settings->pagination->maxPageSize);
        foreach ($definitions as $source => $definition) {
            $builder = $this->filtered($definition, $board, $context, $query, $stageId, $archived);
            $total += (clone $builder)->toBase()->count();
            $sort = $this->sortColumn($definition, $query);
            $id = $definition->fieldMap['id'];
            $model = new $definition->model;
            $idText = $model->getKeyType() === 'string' || $id !== $model->getKeyName();
            $sortExpr = $this->orderExpression($builder, $sort, in_array($query->sort, ['title', 'priority'], true) || ($sort === $id && $idText));
            $idExpr = $this->orderExpression($builder, $id, $idText);
            $operator = $query->direction === 'asc' ? '>' : '<';
            if (isset($positions[$source])) {
                [$last, $lastId] = $positions[$source];
                $builder->where(function (Builder $q) use ($sort, $sortExpr, $idExpr, $operator, $last, $lastId, $query) {
                    // SQL NULL ordering is made explicit: nulls first for asc, last for desc.
                    if ($last === null) {
                        $q->where(fn (Builder $same) => $same->whereNull($sort)->whereRaw($idExpr.' '.$operator.' ?', [$lastId]));
                        if ($query->direction === 'asc') {
                            $q->orWhereNotNull($sort);
                        }
                    } else {
                        $q->whereRaw($sortExpr.' '.$operator.' ?', [$last])->orWhere(fn (Builder $same) => $same->whereRaw($sortExpr.' = ?', [$last])->whereRaw($idExpr.' '.$operator.' ?', [$lastId]));
                        if ($query->direction === 'desc') {
                            $q->orWhereNull($sort);
                        }
                    }
                });
            }
            $grammar = $builder->getQuery()->getGrammar();
            $rows = $builder->reorder()->orderByRaw('CASE WHEN '.$grammar->wrap($sort).' IS NULL THEN 0 ELSE 1 END '.$query->direction)
                ->orderByRaw($sortExpr.' '.$query->direction)->orderByRaw($idExpr.' '.$query->direction)
                ->limit($limit + 1)->with($definition->relations)->get($this->columns($definition));
            foreach ($rows as $record) {
                $candidates[] = ['source' => $source, 'record' => $record, 'definition' => $definition,
                    'value' => $record->getRawOriginal($sort), 'id' => $record->getRawOriginal($id), 'idText' => $idText];
            }
        }
        usort($candidates, function ($a, $b) use ($query) {
            $comparison = $a['value'] === null || $b['value'] === null
                ? (($a['value'] !== null) <=> ($b['value'] !== null))
                : ($query->sort === 'priority' || $query->sort === 'title'
                    ? strcmp((string) $a['value'], (string) $b['value']) : $a['value'] <=> $b['value']);
            $comparison = $comparison ?: strcmp($a['source'], $b['source']) ?: ($a['idText'] || $b['idText'] ? strcmp((string) $a['id'], (string) $b['id']) : $a['id'] <=> $b['id']);

            return $query->direction === 'desc' ? -$comparison : $comparison;
        });
        $items = [];
        foreach (array_slice($candidates, 0, $limit) as $candidate) {
            $items[] = $this->toCard($candidate['record'], $candidate['definition'], $board, $context);
            $positions[$candidate['source']] = [$candidate['value'], $candidate['id']];
        }
        $hasMore = count($candidates) > $limit;

        return new PageResult($items, $hasMore ? $this->cursors->encode($positions, $binding, (int) $board->settings->model->eloquent['cursor']['ttl']) : null, $hasMore, $total, true, $query->hash());
    }
}

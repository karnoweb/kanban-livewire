<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;
use Karnoweb\Kanban\Enums\ViewMode;
use Karnoweb\Kanban\Support\QueryNormalizer;

final readonly class BoardQuery
{
    /** @param array<string, mixed> $filters */
    public function __construct(
        public string $view = 'board',
        public ?string $stageId = null,
        public ?string $cursor = null,
        public int $limit = 25,
        public ?string $search = null,
        public array $filters = [],
        public string $sort = 'manualOrder',
        public string $direction = 'asc',
        public ?string $queryHash = null,
    ) {
        ViewMode::fromSetting($view);

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('Sort direction must be asc or desc.');
        }

        $max = (int) config('kanban.max_page_size', 100);

        if ($limit < 1 || $limit > $max) {
            throw new InvalidArgumentException('Page limit is outside the allowed range.');
        }

        if ($search !== null && strlen($search) > (int) config('kanban.limits.search_length', 200)) {
            throw new InvalidArgumentException('Search exceeds the length limit.');
        }
    }

    public function hash(): string
    {
        return $this->queryHash ?? hash('sha256', json_encode([
            'view' => $this->view,
            'search' => $this->search,
            'filters' => $this->filters,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'limit' => $this->limit,
        ], JSON_THROW_ON_ERROR));
    }

    public function withCursor(?string $cursor, ?string $stageId = null): self
    {
        return new self(
            $this->view,
            $stageId ?? $this->stageId,
            $cursor,
            $this->limit,
            $this->search,
            $this->filters,
            $this->sort,
            $this->direction,
            $this->hash(),
        );
    }

    public static function make(
        string $view,
        ?string $search,
        array $filters,
        string $sort,
        string $direction,
        ?string $cursor,
        int $limit,
        ?string $stageId = null,
    ): self {
        $normalizer = new QueryNormalizer;

        return new self(
            view: $view,
            stageId: $stageId,
            cursor: $cursor,
            limit: $limit,
            search: $normalizer->search($search),
            filters: $normalizer->filters($filters),
            sort: $normalizer->sort($sort),
            direction: $direction,
        );
    }
}

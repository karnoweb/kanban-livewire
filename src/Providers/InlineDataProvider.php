<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Providers;

use Karnoweb\Kanban\Contracts\DataProvider;
use Karnoweb\Kanban\Data\ActionResult;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardData;
use Karnoweb\Kanban\Data\BoardQuery;
use Karnoweb\Kanban\Data\CardData;
use Karnoweb\Kanban\Data\CountResult;
use Karnoweb\Kanban\Data\PageResult;
use Karnoweb\Kanban\Data\StageCount;
use Karnoweb\Kanban\Data\StageData;
use Karnoweb\Kanban\Support\CursorGuard;

final class InlineDataProvider implements DataProvider, \Karnoweb\Kanban\Contracts\FilterOptionsProvider
{
    /** @param list<StageData> $stages
     * @param list<CardData> $cards
     */
    public function __construct(
        private readonly ?BoardData $board = null,
        private readonly array $stages = [],
        private readonly array $cards = [],
    ) {}

    public function filterOptions(BoardContext $context): array
    {
        $options = ['assignee' => [], 'badge' => []];
        foreach ($this->cards as $card) {
            if ($card->archived || $card->boardId !== $this->board?->id) {
                continue;
            }
            foreach ($card->assignees as $person) {
                $options['assignee'][$person->id] = $person->name;
            }
            foreach ($card->badges as $badge) {
                $options['badge'][$badge->text] = $badge->text;
            }
        }
        return $options;
    }

    public function board(BoardContext $context): BoardData
    {
        return $this->board ?? throw new \RuntimeException('Inline board is not configured.');
    }

    public function stages(BoardContext $context, BoardQuery $query): array
    {
        return array_values(array_filter(
            $this->stages,
            static fn (StageData $stage): bool => $stage->visible && ! $stage->archived,
        ));
    }

    public function stageCards(BoardContext $context, string $stageId, BoardQuery $query): PageResult
    {
        $cards = $this->matching($query, $stageId, false);

        return $this->page($cards, $query);
    }

    public function listCards(BoardContext $context, BoardQuery $query): PageResult
    {
        $stage = $query->filters['stage'] ?? null;

        return $this->page($this->matching($query, is_string($stage) ? $stage : null, false), $query);
    }

    public function counts(BoardContext $context, BoardQuery $query): CountResult
    {
        $stages = [];
        $total = 0;

        foreach ($this->stages as $stage) {
            $count = count($this->matching($query, $stage->id, false));
            $stages[] = new StageCount($stage->id, $count, true);
            $total += $count;
        }

        return new CountResult($stages, $total, true);
    }

    public function card(BoardContext $context, string $cardId): ?CardData
    {
        foreach ($this->cards as $card) {
            if ($card->id === $cardId && ($this->board === null || $card->boardId === $this->board->id)) {
                return $card;
            }
        }

        return null;
    }

    public function archives(BoardContext $context, BoardQuery $query): PageResult
    {
        return $this->page($this->matching($query, null, true), $query);
    }

    public function capabilities(BoardContext $context): array
    {
        return ['read' => true];
    }

    public function operationStatus(BoardContext $context, string $operationId): ?ActionResult
    {
        return null;
    }

    /** @return list<CardData> */
    private function matching(BoardQuery $query, ?string $stageId, bool $archived): array
    {
        $cards = array_values(array_filter($this->cards, function (CardData $card) use ($query, $stageId, $archived): bool {
            if ($card->archived !== $archived) {
                return false;
            }

            if ($stageId !== null && $card->stageId !== $stageId) {
                return false;
            }

            if ($query->search !== null) {
                $haystack = mb_strtolower($card->title.' '.($card->description ?? ''));

                if (! str_contains($haystack, mb_strtolower($query->search))) {
                    return false;
                }
            }

            if (is_string($query->filters['stage'] ?? null) && $query->filters['stage'] !== '' && $card->stageId !== $query->filters['stage']) {
                return false;
            }

            $assignee = $query->filters['assignee'] ?? null;

            if (is_string($assignee) && $assignee !== '') {
                $ids = array_map(static fn ($item) => $item->id, $card->assignees);

                if (! in_array($assignee, $ids, true)) {
                    return false;
                }
            }

            $priority = $query->filters['priority'] ?? null;

            if (is_string($priority) && $priority !== '' && $card->priority !== $priority) {
                return false;
            }

            $badge = $query->filters['badge'] ?? null;

            if (is_string($badge) && $badge !== '') {
                $texts = array_map(static fn ($item) => $item->text, $card->badges);

                if (! in_array($badge, $texts, true)) {
                    return false;
                }
            }

            return true;
        }));

        usort($cards, function (CardData $left, CardData $right) use ($query): int {
            $result = match ($query->sort) {
                'title' => strcmp($left->title, $right->title),
                'priority' => strcmp((string) $left->priority, (string) $right->priority),
                'dueAt' => strcmp((string) $left->dueAt, (string) $right->dueAt),
                'createdAt' => strcmp((string) $left->createdAt, (string) $right->createdAt),
                default => strcmp((string) $left->manualOrder, (string) $right->manualOrder),
            };

            if ($result === 0) {
                $result = strcmp($left->id, $right->id);
            }

            return $query->direction === 'desc' ? -$result : $result;
        });

        return $cards;
    }

    /** @param list<CardData> $cards */
    private function page(array $cards, BoardQuery $query): PageResult
    {
        $guard = new CursorGuard;
        $offset = $guard->offset($query->cursor, $query->hash());
        $slice = array_slice($cards, $offset, $query->limit);
        $next = $offset + count($slice);
        $hasMore = $next < count($cards);

        return new PageResult(
            items: $slice,
            nextCursor: $hasMore ? $guard->encode($next, $query->hash()) : null,
            hasMore: $hasMore,
            totalCount: count($cards),
            countExact: true,
            queryHash: $query->hash(),
        );
    }
}

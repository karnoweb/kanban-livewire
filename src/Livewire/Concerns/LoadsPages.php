<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Livewire\Concerns;

use Karnoweb\Kanban\Data\CardData;

trait LoadsPages
{
    public function shiftStageWindow(string $stageId, int $direction): void
    {
        $index = $this->columnIndex($stageId);
        if ($index === null || ! in_array($direction, [-1, 1], true)) {
            return;
        }
        $column = $this->columns[$index];
        $size = $this->renderedCount(count($column['cards']), $this->definition());
        $max = max(0, count($column['cards']) - $size);
        $this->columns[$index]['windowStart'] = max(0, min($max, ($column['windowStart'] ?? 0) + $direction * max(1, intdiv($size, 2))));
    }

    public function loadMore(string $stageId): void
    {
        $index = $this->columnIndex($stageId);

        if ($index === null || ! ($this->columns[$index]['hasMore'] ?? false)) {
            return;
        }

        $hash = $this->currentHash();
        $page = $this->provider()->stageCards(
            $this->context(),
            $stageId,
            $this->query($this->columns[$index]['nextCursor'], $stageId),
        );

        if ($page->queryHash !== $hash) {
            return;
        }

        $existing = array_column($this->columns[$index]['cards'], 'id');
        $appended = $this->columns[$index]['cards'];

        foreach ($page->items as $card) {
            if ($card instanceof CardData && ! in_array($card->id, $existing, true)) {
                $appended[] = $card->toArray();
                $existing[] = $card->id;
            }
        }

        $this->columns[$index]['cards'] = $appended;
        $this->columns[$index]['loadedCount'] = count($appended);
        $this->columns[$index]['renderedCount'] = $this->renderedCount(count($appended), $this->definition());
        $this->columns[$index]['hasMore'] = $page->hasMore;
        $this->columns[$index]['nextCursor'] = $page->nextCursor;
        $this->columns[$index]['error'] = null;
    }

    public function loadMoreList(): void
    {
        if (! $this->listHasMore) {
            return;
        }

        $page = $this->provider()->listCards($this->context(), $this->query($this->listCursor));

        if ($page->queryHash !== $this->currentHash()) {
            return;
        }

        $existing = array_column($this->listItems, 'id');

        foreach ($page->items as $card) {
            if (! in_array($card->id, $existing, true)) {
                $this->listItems[] = $card->toArray();
            }
        }

        $this->listCursor = $page->nextCursor;
        $this->listHasMore = $page->hasMore;
    }

    public function retryStage(string $stageId): void
    {
        $index = $this->columnIndex($stageId);

        if ($index === null) {
            return;
        }

        try {
            $page = $this->provider()->stageCards($this->context(), $stageId, $this->query(null, $stageId));
            $this->columns[$index]['cards'] = array_map(static fn (CardData $card): array => $card->toArray(), $page->items);
            $this->columns[$index]['loadedCount'] = count($page->items);
            $this->columns[$index]['renderedCount'] = $this->renderedCount(count($page->items), $this->definition());
            $this->columns[$index]['windowStart'] = 0;
            $this->columns[$index]['hasMore'] = $page->hasMore;
            $this->columns[$index]['nextCursor'] = $page->nextCursor;
            $this->columns[$index]['error'] = null;
            $this->columns[$index]['totalCount'] = $page->totalCount;
        } catch (\Throwable) {
            $this->columns[$index]['error'] = 'stage_error';
        }
    }

    /** @param list<array<string, mixed>> $items */
    public function commitStagePage(string $stageId, string $queryHash, array $items): void
    {
        if ($queryHash !== $this->currentHash()) {
            return;
        }

        $index = $this->columnIndex($stageId);

        if ($index === null) {
            return;
        }

        $this->columns[$index]['cards'] = $items;
        $this->columns[$index]['loadedCount'] = count($items);
    }
}

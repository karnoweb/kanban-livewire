<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Fixtures;

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
use Karnoweb\Kanban\Enums\ActionStatus;
use Karnoweb\Kanban\Support\CursorGuard;

final class InMemoryProvider implements DataProvider
{
    public function board(BoardContext $context): BoardData
    {
        $row = MemoryStore::board($context->scopeKey, $context->boardKey);

        return new BoardData($context->boardKey, $row['title'], $context->scopeKey);
    }

    public function stages(BoardContext $context, BoardQuery $query): array
    {
        $row = MemoryStore::board($context->scopeKey, $context->boardKey);
        $stages = [];

        foreach ($row['stages'] as $stage) {
            if ($stage['archived'] || ! $stage['visible']) {
                continue;
            }

            $stages[] = new StageData(
                id: $stage['id'],
                boardId: $context->boardKey,
                title: $stage['title'],
                order: (int) $stage['order'],
                visible: true,
                readOnly: (bool) $stage['readOnly'],
                disabled: (bool) $stage['disabled'],
                wipLimit: $stage['wipLimit'],
                wipMode: MemoryStore::wipMode($stage),
                color: $stage['color'],
            );
        }

        usort($stages, static fn (StageData $a, StageData $b): int => $a->order <=> $b->order);

        return $stages;
    }

    public function stageCards(BoardContext $context, string $stageId, BoardQuery $query): PageResult
    {
        return $this->page($context, $this->filtered($context, $query, $stageId, false), $query);
    }

    public function listCards(BoardContext $context, BoardQuery $query): PageResult
    {
        $stage = $query->filters['stage'] ?? null;

        return $this->page($context, $this->filtered($context, $query, is_string($stage) ? $stage : null, false), $query);
    }

    public function counts(BoardContext $context, BoardQuery $query): CountResult
    {
        $row = MemoryStore::board($context->scopeKey, $context->boardKey);
        $stages = [];
        $total = 0;

        foreach ($row['stages'] as $stage) {
            $count = count($this->filtered($context, $query, $stage['id'], false));
            $stages[] = new StageCount($stage['id'], $count, true);
            $total += $count;
        }

        return new CountResult($stages, $total, true);
    }

    public function card(BoardContext $context, string $cardId): ?CardData
    {
        $row = MemoryStore::board($context->scopeKey, $context->boardKey);
        $card = $row['cards'][$cardId] ?? null;

        return is_array($card) ? MemoryStore::card($card, $context->boardKey) : null;
    }

    public function archives(BoardContext $context, BoardQuery $query): PageResult
    {
        return $this->page($context, $this->filtered($context, $query, null, true), $query);
    }

    public function capabilities(BoardContext $context): array
    {
        return ['read' => true];
    }

    public function operationStatus(BoardContext $context, string $operationId): ?ActionResult
    {
        $stored = MemoryStore::$operations[$context->scopeKey.'|'.$operationId] ?? null;

        if (! is_array($stored)) {
            return null;
        }

        return new ActionResult(ActionStatus::from($stored['status']), affectedIds: $stored['affectedIds'] ?? [], newVersion: $stored['newVersion'] ?? null);
    }

    /** @return list<array<string, mixed>> */
    private function filtered(BoardContext $context, BoardQuery $query, ?string $stageId, bool $archived): array
    {
        $row = MemoryStore::board($context->scopeKey, $context->boardKey);
        $cards = array_values(array_filter($row['cards'], function (array $card) use ($query, $stageId, $archived): bool {
            if ((bool) $card['archived'] !== $archived) {
                return false;
            }

            if ($stageId !== null && $card['stageId'] !== $stageId) {
                return false;
            }

            if ($query->search !== null && ! str_contains(mb_strtolower($card['title']), mb_strtolower($query->search))) {
                return false;
            }

            $assignee = $query->filters['assignee'] ?? null;

            if (is_string($assignee) && $assignee !== '') {
                $ids = array_column($card['assignees'], 'id');

                if (! in_array($assignee, $ids, true)) {
                    return false;
                }
            }

            $priority = $query->filters['priority'] ?? null;

            if (is_string($priority) && $priority !== '' && $card['priority'] !== $priority) {
                return false;
            }

            $badge = $query->filters['badge'] ?? null;

            if (is_string($badge) && $badge !== '') {
                $texts = array_column($card['badges'], 'text');

                if (! in_array($badge, $texts, true)) {
                    return false;
                }
            }

            return true;
        }));

        usort($cards, function (array $left, array $right) use ($query): int {
            $result = match ($query->sort) {
                'title' => strcmp($left['title'], $right['title']),
                'priority' => strcmp((string) $left['priority'], (string) $right['priority']),
                'dueAt' => strcmp((string) $left['dueAt'], (string) $right['dueAt']),
                'createdAt' => strcmp((string) $left['createdAt'], (string) $right['createdAt']),
                default => strcmp((string) $left['manualOrder'], (string) $right['manualOrder']),
            };

            if ($result === 0) {
                $result = strcmp($left['id'], $right['id']);
            }

            return $query->direction === 'desc' ? -$result : $result;
        });

        return $cards;
    }

    /** @param list<array<string, mixed>> $cards */
    private function page(BoardContext $context, array $cards, BoardQuery $query): PageResult
    {
        $guard = new CursorGuard;
        $offset = $guard->offset($query->cursor, $query->hash());
        $slice = array_slice($cards, $offset, $query->limit);
        $items = array_map(fn (array $card): CardData => MemoryStore::card($card, $context->boardKey), $slice);
        $next = $offset + count($slice);
        $hasMore = $next < count($cards);

        return new PageResult($items, $hasMore ? $guard->encode($next, $query->hash()) : null, $hasMore, count($cards), true, $query->hash());
    }
}

<?php

declare(strict_types=1);

namespace Workbench\App\Kanban;

use Illuminate\Database\Eloquent\Builder;
use Karnoweb\Kanban\Contracts\DataProvider;
use Karnoweb\Kanban\Data\ActionResult;
use Karnoweb\Kanban\Data\AssigneeData;
use Karnoweb\Kanban\Data\AttachmentData;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardData;
use Karnoweb\Kanban\Data\BoardQuery;
use Karnoweb\Kanban\Data\CardActionData;
use Karnoweb\Kanban\Data\CardBadgeData;
use Karnoweb\Kanban\Data\CardData;
use Karnoweb\Kanban\Data\CountResult;
use Karnoweb\Kanban\Data\PageResult;
use Karnoweb\Kanban\Data\StageCount;
use Karnoweb\Kanban\Data\StageData;
use Karnoweb\Kanban\Enums\ActionStatus;
use Karnoweb\Kanban\Enums\BadgeTone;
use Karnoweb\Kanban\Enums\ConfirmationMode;
use Karnoweb\Kanban\Enums\WipMode;
use Karnoweb\Kanban\Support\CursorGuard;
use Workbench\App\Models\DemoCard;
use Workbench\App\Models\DemoOperation;
use Workbench\App\Models\DemoStage;

class WorkbenchReader implements DataProvider
{
    public function board(BoardContext $context): BoardData
    {
        return new BoardData($context->boardKey, $context->boardKey, $context->scopeKey);
    }

    public function stages(BoardContext $context, BoardQuery $query): array
    {
        return DemoStage::query()
            ->where('scope_key', $context->scopeKey)
            ->where('board_key', $context->boardKey)
            ->where('archived', false)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (DemoStage $stage): StageData => new StageData(
                (string) $stage->public_id,
                $context->boardKey,
                (string) $stage->title,
                (int) $stage->sort_order,
                wipLimit: $stage->wip_limit === null ? null : (int) $stage->wip_limit,
                wipMode: WipMode::tryFrom((string) $stage->wip_mode) ?? WipMode::Off,
                color: $stage->color,
            ))
            ->all();
    }

    public function stageCards(BoardContext $context, string $stageId, BoardQuery $query): PageResult
    {
        return $this->page($context, $query, $stageId, false);
    }

    public function listCards(BoardContext $context, BoardQuery $query): PageResult
    {
        $stage = $query->filters['stage'] ?? null;

        return $this->page($context, $query, is_string($stage) && $stage !== '' ? $stage : null, false);
    }

    public function counts(BoardContext $context, BoardQuery $query): CountResult
    {
        $stages = [];
        $total = 0;

        foreach ($this->stages($context, $query) as $stage) {
            $count = $this->base($context, $stage->id, $query, false)->count();
            $stages[] = new StageCount($stage->id, $count, true);
            $total += $count;
        }

        return new CountResult($stages, $total, true);
    }

    public function card(BoardContext $context, string $cardId): ?CardData
    {
        $row = DemoCard::query()
            ->where('scope_key', $context->scopeKey)
            ->where('board_key', $context->boardKey)
            ->where('public_id', $cardId)
            ->first();

        return $row instanceof DemoCard ? $this->dto($row, $context) : null;
    }

    public function archives(BoardContext $context, BoardQuery $query): PageResult
    {
        return $this->page($context, $query, null, true);
    }

    public function capabilities(BoardContext $context): array
    {
        return ['read' => true];
    }

    public function operationStatus(BoardContext $context, string $operationId): ?ActionResult
    {
        $row = DemoOperation::query()
            ->where('scope_key', $context->scopeKey)
            ->where('operation_id', $operationId)
            ->first();

        if (! $row instanceof DemoOperation) {
            return null;
        }

        $stored = json_decode((string) $row->result, true) ?: [];

        return new ActionResult(
            ActionStatus::from((string) $row->status),
            affectedIds: $stored['affectedIds'] ?? [],
            newVersion: $stored['newVersion'] ?? null,
        );
    }

    private function page(BoardContext $context, BoardQuery $query, ?string $stageId, bool $archived): PageResult
    {
        $guard = new CursorGuard;
        $offset = $guard->offset($query->cursor, $query->hash());
        $builder = $this->base($context, $stageId, $query, $archived);
        $total = (clone $builder)->count();
        $rows = $builder->skip($offset)->take($query->limit)->get();
        $items = $rows->map(fn (DemoCard $card): CardData => $this->dto($card, $context))->all();
        $next = $offset + count($items);
        $hasMore = $next < $total;

        return new PageResult(
            $items,
            $hasMore ? $guard->encode($next, $query->hash()) : null,
            $hasMore,
            $total,
            true,
            $query->hash(),
        );
    }

    /** @return Builder<DemoCard> */
    private function base(BoardContext $context, ?string $stageId, BoardQuery $query, bool $archived): Builder
    {
        $builder = DemoCard::query()
            ->where('scope_key', $context->scopeKey)
            ->where('board_key', $context->boardKey)
            ->where('archived', $archived);

        if ($stageId !== null) {
            $builder->where('stage_id', $stageId);
        }

        if ($query->search !== null) {
            $needle = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query->search);
            $builder->where('title', 'like', '%'.$needle.'%');
        }

        $priority = $query->filters['priority'] ?? null;

        if (is_string($priority) && $priority !== '') {
            $builder->where('priority', $priority);
        }

        $column = match ($query->sort) {
            'title' => 'title',
            'priority' => 'priority',
            'dueAt' => 'due_at',
            'createdAt' => 'created_at',
            default => 'manual_order',
        };

        return $builder->orderBy($column, $query->direction)->orderBy('id', $query->direction);
    }

    private function dto(DemoCard $card, BoardContext $context): CardData
    {
        $meta = is_array($card->metadata) ? $card->metadata : [];
        $badges = [];

        foreach (is_array($meta['badges'] ?? null) ? $meta['badges'] : [] as $item) {
            if (is_array($item) && ! empty($item['text'])) {
                $badges[] = new CardBadgeData(
                    (string) $item['text'],
                    BadgeTone::tryFrom((string) ($item['tone'] ?? 'neutral')) ?? BadgeTone::Neutral,
                    isset($item['description']) && is_string($item['description']) ? $item['description'] : null,
                );
            }
        }

        $assignees = [];

        foreach (is_array($meta['assignees'] ?? null) ? $meta['assignees'] : [] as $item) {
            if (is_array($item) && ! empty($item['id']) && ! empty($item['name'])) {
                $assignees[] = new AssigneeData(
                    (string) $item['id'],
                    (string) $item['name'],
                    isset($item['avatarUrl']) && is_string($item['avatarUrl']) ? $item['avatarUrl'] : null,
                    isset($item['label']) && is_string($item['label']) ? $item['label'] : null,
                );
            }
        }

        $attachments = [];

        foreach (is_array($meta['attachments'] ?? null) ? $meta['attachments'] : [] as $item) {
            if (is_array($item) && ! empty($item['id']) && ! empty($item['name']) && ! empty($item['url'])) {
                $attachments[] = new AttachmentData(
                    (string) $item['id'],
                    (string) $item['name'],
                    (string) ($item['mime'] ?? 'application/octet-stream'),
                    isset($item['size']) && is_numeric($item['size']) ? (int) $item['size'] : null,
                    (string) $item['url'],
                    isset($item['thumbnailUrl']) && is_string($item['thumbnailUrl']) ? $item['thumbnailUrl'] : null,
                );
            }
        }

        $actions = [];

        foreach (is_array($meta['actions'] ?? null) ? $meta['actions'] : [] as $item) {
            if (is_array($item) && ! empty($item['id']) && ! empty($item['label'])) {
                $actions[] = new CardActionData(
                    (string) $item['id'],
                    (string) $item['label'],
                    (bool) ($item['enabled'] ?? true),
                    ConfirmationMode::tryFrom((string) ($item['confirmation'] ?? 'none')) ?? ConfirmationMode::None,
                    isset($item['icon']) && is_string($item['icon']) ? $item['icon'] : null,
                );
            }
        }

        unset($meta['badges'], $meta['assignees'], $meta['attachments'], $meta['actions']);

        return new CardData(
            (string) $card->public_id,
            $context->boardKey,
            (string) $card->stage_id,
            (string) $card->title,
            description: $card->description,
            priority: $card->priority,
            badges: $badges,
            assignees: $assignees,
            attachments: $attachments,
            createdAt: $card->created_at?->toIso8601String(),
            dueAt: $card->due_at,
            metadata: $meta,
            actions: $actions,
            version: (string) $card->version,
            manualOrder: (string) $card->manual_order,
            archived: (bool) $card->archived,
        );
    }
}

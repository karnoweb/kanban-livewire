<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Actions;

use Illuminate\Database\Eloquent\Model;
use Karnoweb\Kanban\Contracts\RankStrategy;
use Karnoweb\Kanban\Data\{ActionCommand, ActionResult, BoardContext, BoardDefinition};
use Karnoweb\Kanban\Enums\ActionStatus;
use Karnoweb\Kanban\Models\{CardIdentity, KanbanModelDefinition};
use Karnoweb\Kanban\Providers\EloquentKanbanDataProvider;

final class SparseNumericRank implements RankStrategy
{
    public function __construct(private readonly EloquentKanbanDataProvider $provider) {}

    public function supports(BoardDefinition $board, array $definitions): bool
    {
        return count($definitions) === 1 && isset(reset($definitions)->fieldMap['rank']);
    }

    public function assign(Model $record, KanbanModelDefinition $definition, BoardDefinition $board, BoardContext $context, ActionCommand $command, string $stage): array
    {
        if (count($board->sources ?? []) > 1 || ! isset($definition->fieldMap['rank'])) {
            $this->reject(ActionStatus::UNSUPPORTED, 'rank_storage_required');
        }
        $rank = $definition->fieldMap['rank'];
        $id = $definition->fieldMap['id'];
        $query = fn () => $this->provider->scoped($definition, $board, $context)->where($definition->fieldMap['stage'], $stage)
            ->where($id, '!=', $record->getAttribute($id) ?? '');
        $neighbors = [];
        foreach (['afterCardId', 'beforeCardId'] as $field) {
            $value = $command->payload[$field] ?? null;
            if ($value === null) {
                $neighbors[$field] = null;
                continue;
            }
            $identity = CardIdentity::parse((string) $value);
            if ($identity->source !== $definition->source || $identity->recordId === (string) $record->getAttribute($id)) {
                $this->reject(ActionStatus::VALIDATION_ERROR, 'invalid_anchor');
            }
            $neighbor = $query()->where($id, $identity->recordId)->lockForUpdate()->first();
            if ($neighbor === null || $neighbor->getAttribute($rank) === null) {
                $this->reject(ActionStatus::NOT_FOUND, 'not_found');
            }
            $neighbors[$field] = (int) $neighbor->getAttribute($rank);
        }
        $low = $neighbors['afterCardId']; $high = $neighbors['beforeCardId'];
        if ($low === null && $high === null) {
            if (($command->payload['placement'] ?? 'end') === 'start') {
                $high = $query()->min($rank);
            } else {
                $low = $query()->max($rank);
            }
        } elseif ($low !== null && $high === null) {
            $high = $query()->where($rank, '>', $low)->min($rank);
        } elseif ($high !== null && $low === null) {
            $low = $query()->where($rank, '<', $high)->max($rank);
        }
        $low = $low === null ? ($high === null ? 0 : (int) $high - 2048) : (int) $low;
        $high = $high === null ? $low + 2048 : (int) $high;
        if ($low > $high) {
            $this->reject(ActionStatus::VALIDATION_ERROR, 'invalid_anchor_order');
        }
        // Both supplied anchors must really be adjacent in storage, not just in a loaded window.
        if ($neighbors['afterCardId'] !== null && $neighbors['beforeCardId'] !== null && $query()->where($rank, '>', $low)->where($rank, '<', $high)->exists()) {
            $this->reject(ActionStatus::CONFLICT, 'anchors_not_adjacent');
        }
        $changed = [];
        $next = $low + intdiv($high - $low, 2);
        if ($high - $low < 2 || $query()->where($rank, $next)->exists()) {
            // Rebalance only a bounded neighborhood, never the whole column.
            $windowQuery = $query()->where($rank, '>=', $high);
            if (($command->payload['afterCardId'] ?? null) !== null) {
                $windowQuery->where($id, '!=', CardIdentity::parse($command->payload['afterCardId'])->recordId);
            }
            $window = $windowQuery->orderBy($rank)->orderBy($id)->limit(65)->lockForUpdate()->get();
            $boundary = count($window) > 64 ? (int) $window[64]->getAttribute($rank) : $low + (count($window) + 2) * 1024;
            $rows = $window->take(64);
            $step = intdiv($boundary - $low, count($rows) + 2);
            if ($step < 1) {
                $this->reject(ActionStatus::CONFLICT, 'rank_window_exhausted');
            }
            $next = $low + $step;
            foreach ($rows as $index => $neighbor) {
                $neighbor->setAttribute($rank, $low + ($index + 2) * $step);
                if (isset($definition->fieldMap['version'])) {
                    $version = $definition->fieldMap['version'];
                    $neighbor->setAttribute($version, (int) $neighbor->getAttribute($version) + 1);
                }
                $neighbor->save();
                $changed[] = (new CardIdentity($definition->source, (string) $neighbor->getAttribute($id)))->encode();
            }
        }
        $record->setAttribute($rank, $next);

        return $changed;
    }

    private function reject(ActionStatus $status, string $code): never
    {
        throw new ModelActionRejected(new ActionResult($status, messageCode: $code));
    }
}

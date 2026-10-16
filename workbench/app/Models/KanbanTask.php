<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\{Builder, Model};
use Karnoweb\Kanban\Concerns\HasKanbanCards;
use Karnoweb\Kanban\Data\{BoardContext, CardActionData};
use Karnoweb\Kanban\Models\KanbanModelDefinition;

class KanbanTask extends Model
{
    use HasKanbanCards;

    protected $table = 'demo_model_cards';
    protected $guarded = [];
    protected const SOURCE = 'tasks';
    protected const TITLE = 'title';
    protected const STAGE = 'status';
    protected const STAGES = ['todo' => 'To do', 'doing' => 'Doing', 'done' => 'Done'];

    public static function kanbanDefinition(): KanbanModelDefinition
    {
        return KanbanModelDefinition::make(static::SOURCE, static::class)
            ->fields(['title' => static::TITLE, 'stage' => static::STAGE, 'rank' => 'sort_rank', 'version' => 'version',
                'priority' => 'priority', 'description' => 'description', 'dueAt' => 'due_at', 'createdAt' => 'created_at', 'archivedAt' => 'archived_at', 'group' => 'team'])
            ->stages(static::STAGES)
            ->visibleQuery(fn (Builder $query, BoardContext $context) => $query->where('scope_key', $context->scopeKey)->where('source', static::SOURCE))
            ->authorize(fn (string $ability, ?Model $record, BoardContext $context): bool => $record === null
                ? $ability === 'create' : (string) $record->scope_key === $context->scopeKey)
            ->filters(['priority', 'dueFrom' => 'due_at', 'dueTo' => 'due_at'])
            ->sorts(['manualOrder', 'title', 'priority', 'dueAt', 'createdAt'])
            ->searchable([static::TITLE, 'description'])
            ->transitions(array_fill_keys(array_keys(static::STAGES), array_keys(static::STAGES)))
            ->metrics(['amount' => ['aggregate' => 'sum', 'column' => 'amount']])
            ->moveGroups(fn ($record, string $group): bool => in_array($group, ['Engineering', 'Support', '__ungrouped__'], true))
            ->action('complete', function (Model $record): void {
                $record->setAttribute(static::STAGE, array_key_last(static::STAGES));
            })
            ->present(fn (Model $record) => ['actions' => [new CardActionData('complete', 'Complete')],
                'metadata' => ['team' => $record->team, 'amount' => $record->amount]])
            ->hook('before', function (Model $record, $command, BoardContext $context): void {
                if (! $record->exists) {
                    $record->scope_key = $context->scopeKey;
                    $record->source = static::SOURCE;
                    $record->team = 'Engineering';
                }
            });
    }
}

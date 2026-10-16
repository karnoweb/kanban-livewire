<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Karnoweb\Kanban\Concerns\HasKanbanCards;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Models\KanbanModelDefinition;

class EnterpriseTask extends Model
{
    use HasKanbanCards;

    protected $table = 'enterprise_tasks';
    public $timestamps = false;
    protected $guarded = [];

    public static function kanbanDefinition(): KanbanModelDefinition
    {
        $definition = KanbanModelDefinition::make('tasks', static::class)
            ->fields(['stage' => 'status', 'description' => 'description', 'rank' => 'rank', 'version' => 'version', 'priority' => 'priority', 'archivedAt' => 'archived_at', 'group' => 'team', 'assignee' => 'owner_id'])
            ->stages(['todo' => 'To do', 'doing' => ['title' => 'Doing', 'wipLimit' => 2], 'done' => 'Done'])
            ->visibleQuery(fn (Builder $q, BoardContext $c) => $q->where('owner_id', $c->actorId))
            ->authorize(fn (string $ability, ?Model $record, BoardContext $context): bool => $record === null
                ? $ability === 'create' : (string) $record->owner_id === $context->actorId)
            ->filters(['priority', 'team', 'assignee'])
            ->sorts(['manualOrder', 'title', 'priority'])
            ->searchable(['title', 'description'])
            ->transitions(['todo' => ['doing'], 'doing' => ['todo', 'done']])
            ->metrics(['amount' => ['aggregate' => 'sum', 'column' => 'amount']])
            ->hook('before', function (Model $record, $command, BoardContext $context): void {
                if (! $record->exists) {
                    $record->owner_id = $context->actorId;
                }
            });
        if (config('kanban_test.custom', false)) {
            $definition->action('approve', function (Model $record, $command): void {
                $record->title = 'Approved: '.($command->payload['formData']['note'] ?? '');
            }, ['note' => ['required', 'string']]);
        }

        return $definition;
    }
}

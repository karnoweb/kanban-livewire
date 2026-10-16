<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Models\KanbanModelDefinition;

final class EnterpriseClass extends EnterpriseTask
{
    protected $table = 'enterprise_classes';

    public static function kanbanDefinition(): KanbanModelDefinition
    {
        return KanbanModelDefinition::make('classes', static::class)->fields(['title' => 'name', 'stage' => 'approval_state'])
            ->stages(['pending' => 'Pending', 'approved' => 'Approved'])
            ->visibleQuery(fn (Builder $query, BoardContext $context) => $query->where('owner_id', $context->actorId))
            ->authorize(fn () => true);
    }
}

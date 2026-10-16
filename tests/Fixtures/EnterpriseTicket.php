<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Models\KanbanModelDefinition;

final class EnterpriseTicket extends EnterpriseTask
{
    protected $table = 'enterprise_tickets';

    public static function kanbanDefinition(): KanbanModelDefinition
    {
        return KanbanModelDefinition::make('tickets', static::class)->fields(['title' => 'subject', 'stage' => 'state'])
            ->stages(['open' => 'Open', 'closed' => 'Closed'])
            ->visibleQuery(fn (Builder $q, BoardContext $c) => $q->where('owner_id', $c->actorId))
            ->authorize(fn () => true);
    }
}

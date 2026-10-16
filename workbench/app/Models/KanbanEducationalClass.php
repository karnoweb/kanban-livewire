<?php

declare(strict_types=1);

namespace Workbench\App\Models;

final class KanbanEducationalClass extends KanbanTask
{
    protected const SOURCE = 'classes';
    protected const TITLE = 'name';
    protected const STAGE = 'approval_state';
    protected const STAGES = ['pending' => 'Pending', 'review' => 'Review', 'approved' => 'Approved'];
}

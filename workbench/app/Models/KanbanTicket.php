<?php

declare(strict_types=1);

namespace Workbench\App\Models;

final class KanbanTicket extends KanbanTask
{
    protected const SOURCE = 'tickets';
    protected const TITLE = 'subject';
    protected const STAGE = 'state';
    protected const STAGES = ['open' => 'Open', 'answering' => 'Answering', 'closed' => 'Closed'];
}

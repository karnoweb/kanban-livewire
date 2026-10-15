<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Enums;

enum BulkMode: string
{
    case Partial = 'partial';
    case AllOrNothing = 'all-or-nothing';
}

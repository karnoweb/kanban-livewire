<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Enums;

enum BadgeTone: string
{
    case Neutral = 'neutral';
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';
}

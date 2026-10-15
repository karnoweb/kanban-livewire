<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Enums;

enum WipMode: string
{
    case Off = 'off';
    case Warning = 'warning';
    case Strict = 'strict';
}

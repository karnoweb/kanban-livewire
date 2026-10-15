<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Enums;

enum ConfirmationMode: string
{
    case None = 'none';
    case Generic = 'generic';
    case Custom = 'custom';
    case Form = 'form';
}

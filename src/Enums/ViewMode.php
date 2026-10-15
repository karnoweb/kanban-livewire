<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Enums;

enum ViewMode: string
{
    case Board = 'board';
    case List = 'list';

    public static function fromSetting(string $value): self
    {
        return self::tryFrom($value)
            ?? throw new \InvalidArgumentException('View mode must be board or list.');
    }
}

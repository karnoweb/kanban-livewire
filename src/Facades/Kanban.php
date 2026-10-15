<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Karnoweb\Kanban\Support\BoardBuilder init()
 * @method static \Karnoweb\Kanban\Data\BoardDefinition resolveBoard(string $key, \Karnoweb\Kanban\Data\BoardContext $context)
 */
final class Kanban extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'kanban';
    }
}

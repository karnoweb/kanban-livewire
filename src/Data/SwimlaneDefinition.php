<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;

/** Group values are discovered through each model's visibility query, never globally. */
final readonly class SwimlaneDefinition
{
    public function __construct(public string $field = 'group', public int $maxGroups = 30, public bool $allowMove = false)
    {
        if (! in_array($field, ['group', 'priority', 'assignee'], true) || $maxGroups < 1 || $maxGroups > 100) {
            throw new InvalidArgumentException('Invalid swimlane field or group limit.');
        }
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Exceptions;

use InvalidArgumentException;

final class UnknownBoard extends InvalidArgumentException
{
    public function __construct(public readonly string $boardKey)
    {
        parent::__construct('Unknown board key.');
    }
}

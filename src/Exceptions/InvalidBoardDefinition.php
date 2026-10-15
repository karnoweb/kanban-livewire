<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Exceptions;

use InvalidArgumentException;

final class InvalidBoardDefinition extends InvalidArgumentException
{
    public function __construct(
        string $message,
        public readonly ?string $boardKey = null,
    ) {
        parent::__construct($boardKey === null ? $message : $boardKey.': '.$message);
    }
}

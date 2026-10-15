<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Exceptions;

use RuntimeException;

final class BoardAccessDenied extends RuntimeException
{
    public function __construct(string $message = 'Board access denied.')
    {
        parent::__construct($message);
    }
}

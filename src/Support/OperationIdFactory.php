<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

final class OperationIdFactory
{
    public function make(): string
    {
        return 'op-'.bin2hex(random_bytes(16));
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Actions;

use Karnoweb\Kanban\Data\ActionResult;
use RuntimeException;

final class ModelActionRejected extends RuntimeException
{
    public function __construct(public readonly ActionResult $result)
    {
        parent::__construct($result->messageCode ?? $result->status->value);
    }
}

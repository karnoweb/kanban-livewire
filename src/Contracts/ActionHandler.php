<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Contracts;

use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\ActionResult;
use Karnoweb\Kanban\Data\BoardContext;

interface ActionHandler
{
    public function execute(ActionCommand $command, BoardContext $context): ActionResult;
}

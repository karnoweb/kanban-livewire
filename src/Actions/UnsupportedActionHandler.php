<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Actions;

use Karnoweb\Kanban\Contracts\ActionHandler;
use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\ActionResult;
use Karnoweb\Kanban\Data\BoardContext;

final class UnsupportedActionHandler implements ActionHandler
{
    public function execute(ActionCommand $command, BoardContext $context): ActionResult
    {
        return ActionResult::unsupported();
    }
}

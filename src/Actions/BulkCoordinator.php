<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Actions;

use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\BulkRequest;
use Karnoweb\Kanban\Enums\ActionType;

final class BulkCoordinator
{
    public function command(BulkRequest $request): ActionCommand
    {
        return new ActionCommand($request->operationId, ActionType::ExecuteBulkAction, [
            'ids' => implode(',', $request->ids),
            'mode' => $request->mode->value,
            'action' => $request->action,
            'expectedVersions' => implode(',', array_map(
                static fn (string $id, string $version): string => $id.':'.$version,
                array_keys($request->expectedVersions),
                $request->expectedVersions,
            )),
        ]);
    }
}

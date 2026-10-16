<?php

declare(strict_types=1);

namespace Workbench\App\Kanban;

use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardQuery;
use Karnoweb\Kanban\Data\StageData;
use Karnoweb\Kanban\Enums\WipMode;
use Workbench\App\Models\DemoStage;

final class TicketProvider extends WorkbenchReader
{
    public function stages(BoardContext $context, BoardQuery $query): array
    {
        return DemoStage::query()
            ->where('scope_key', $context->scopeKey)
            ->where('board_key', $context->boardKey)
            ->where('archived', false)
            ->orderBy('sort_order')
            ->get()
            ->map(function (DemoStage $stage): StageData {
                $id = (string) $stage->public_id;

                return new StageData(
                    $id,
                    (string) $stage->board_key,
                    (string) $stage->title,
                    (int) $stage->sort_order,
                    readOnly: $id === 'closed',
                    wipLimit: $stage->wip_limit === null ? null : (int) $stage->wip_limit,
                    wipMode: WipMode::tryFrom((string) $stage->wip_mode) ?? WipMode::Off,
                    color: $stage->color,
                );
            })
            ->all();
    }
}

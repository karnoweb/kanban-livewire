<?php

declare(strict_types=1);

namespace Workbench\App\Kanban;

use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\ActionResult;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Enums\ActionStatus;
use Karnoweb\Kanban\Enums\ActionType;
use Workbench\App\Models\DemoCard;

/**
 * Orders sample: finance stage only accepts cards reviewed in the handler (domain gate).
 */
final class OrdersHandler extends WorkbenchWriter
{
    public function execute(ActionCommand $command, BoardContext $context): ActionResult
    {
        if ($command->type === ActionType::MoveCard) {
            $to = (string) ($command->payload['toStageId'] ?? '');
            $cardId = (string) ($command->payload['cardId'] ?? '');

            if ($to === 'finance') {
                $card = DemoCard::query()
                    ->where('scope_key', $context->scopeKey)
                    ->where('board_key', $context->boardKey)
                    ->where('public_id', $cardId)
                    ->first();

                if (! $card instanceof DemoCard) {
                    return new ActionResult(ActionStatus::NOT_FOUND, messageCode: 'not_found');
                }

                $meta = is_array($card->metadata) ? $card->metadata : [];

                if (! ($meta['reviewApproved'] ?? false)) {
                    return new ActionResult(ActionStatus::DOMAIN_REJECTED, messageCode: 'orders_review_required');
                }
            }
        }

        return parent::execute($command, $context);
    }
}

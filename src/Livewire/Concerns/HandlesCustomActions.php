<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Livewire\Concerns;

use Karnoweb\Kanban\Actions\CardCoordinator;
use Karnoweb\Kanban\Support\OperationIdFactory;

trait HandlesCustomActions
{
    /** @param array<string, mixed> $fields */
    public function runCardAction(string $cardId, string $actionId, array $fields = []): void
    {
        $allowed = false;
        $card = $this->provider()->card($this->context(), $cardId);

        foreach ($card?->actions ?? [] as $action) {
            if ($action->id === $actionId && $action->enabled) {
                $allowed = true;
            }
        }

        if (! $allowed) {
            $this->notice = ['status' => 'unsupported', 'messageCode' => 'unsupported', 'fieldErrors' => [], 'safeMessage' => null, 'successCount' => 0, 'failureCount' => 0, 'noop' => false, 'items' => []];

            return;
        }

        $this->execute(app(CardCoordinator::class)->custom(
            app(OperationIdFactory::class)->make(),
            $cardId,
            $actionId,
            $fields,
            $this->loadedVersion($cardId),
        ));
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Livewire\Concerns;

trait HandlesCardDetails
{
    public function openDetails(string $cardId, ?string $surface = null): void
    {
        $card = $this->provider()->card($this->context(), $cardId);

        if ($card === null) {
            $this->notice = ['status' => 'not_found', 'messageCode' => 'not_found', 'fieldErrors' => [], 'safeMessage' => null, 'successCount' => 0, 'failureCount' => 0, 'noop' => false, 'items' => []];

            return;
        }

        $this->detail = $card->toArray();
        $this->detailSurface = in_array($surface, ['modal', 'drawer'], true) ? $surface : $this->detailSurface;
    }

    public function closeDetails(): void
    {
        $this->detail = null;
    }

    public function openArchive(): void
    {
        $page = $this->provider()->archives($this->context(), $this->query());
        $this->archiveItems = array_map(static fn ($card) => $card->toArray(), $page->items);
        $this->archiveOpen = true;
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Livewire\Concerns;

trait ManagesSelection
{
    public function toggleSelected(string $cardId): void
    {
        if (in_array($cardId, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_filter(
                $this->selectedIds,
                static fn (string $id): bool => $id !== $cardId,
            ));
        } else {
            $this->selectedIds[] = $cardId;
        }

        $this->dispatch('kanban:card-selected', cardId: $cardId);
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Livewire\Concerns;

trait ManagesBoardQuery
{
    public function updatedSearch(): void
    {
        $this->resetQueryState();
    }

    /** @param array<string, mixed> $filters */
    public function applyFilters(array $filters): void
    {
        $this->filters = $filters;
        $this->resetQueryState();
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->search = '';
        $this->resetQueryState();
    }

    public function sortBy(string $field): void
    {
        if ($this->sort === $field) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->direction = 'asc';
        }

        $this->resetQueryState();
    }

    public function setViewport(string $viewport): void
    {
        if (! in_array($viewport, ['mobile', 'tablet', 'desktop'], true)) {
            return;
        }

        $this->viewport = $viewport;
    }

    public function setReducedMotion(bool $enabled): void
    {
        $this->reducedMotion = $enabled;
    }

    protected function resetQueryState(): void
    {
        $this->selectedIds = [];
        $this->listCursor = null;
        $this->syncReorderFlag();
        $this->reload();
    }
}

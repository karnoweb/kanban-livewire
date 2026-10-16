<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Livewire\Concerns;

use Karnoweb\Kanban\Actions\BulkCoordinator;
use Karnoweb\Kanban\Data\BulkRequest;
use Karnoweb\Kanban\Enums\BulkMode;
use Karnoweb\Kanban\Support\OperationIdFactory;

trait HandlesBulkActions
{
    public function runBulk(string $action, ?string $mode = null): void
    {
        $bulkMode = BulkMode::tryFrom($mode ?? $this->bulkMode) ?? BulkMode::Partial;
        $versions = [];
        foreach ($this->selectedIds as $id) {
            $version = $this->loadedVersion($id);
            if ($version !== null) {
                $versions[$id] = $version;
            }
        }
        $request = new BulkRequest(
            app(OperationIdFactory::class)->make(),
            array_values($this->selectedIds),
            $bulkMode,
            $action,
            $versions,
        );
        $result = $this->execute(app(BulkCoordinator::class)->command($request));
        $this->bulkSummary = [
            'successCount' => $result->successCount,
            'failureCount' => $result->failureCount,
            'items' => array_map(static fn ($item) => $item->toArray(), $result->itemResults),
        ];
        $this->selectedIds = [];
    }
}

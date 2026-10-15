<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Livewire\Concerns;

use Karnoweb\Kanban\Actions\StageCoordinator;
use Karnoweb\Kanban\Enums\ActionStatus;
use Karnoweb\Kanban\Support\OperationIdFactory;

trait HandlesStageCrud
{
    public function openStageForm(?string $stageId = null): void
    {
        $current = ['title' => '', 'color' => '#336699', 'stageId' => $stageId];

        if ($stageId !== null) {
            $index = $this->columnIndex($stageId);

            if ($index !== null) {
                $current['title'] = $this->columns[$index]['title'];
                $current['color'] = $this->columns[$index]['color'] ?? '#336699';
            }
        }

        $this->stageForm = $current;
        $this->stageFormOpen = true;
        $this->stageErrors = [];
    }

    public function submitStageForm(): void
    {
        $coordinator = app(StageCoordinator::class);
        $operationId = app(OperationIdFactory::class)->make();
        $stageId = $this->stageForm['stageId'] ?? null;
        $command = is_string($stageId) && $stageId !== ''
            ? $coordinator->update($operationId, $stageId, $this->stageForm, $this->definition())
            : $coordinator->create($operationId, [
                'title' => (string) ($this->stageForm['title'] ?? ''),
                'color' => (string) ($this->stageForm['color'] ?? ''),
            ]);
        $result = $this->execute($command);

        if ($result->status === ActionStatus::SUCCESS) {
            $this->stageFormOpen = false;
        }
    }

    /** @param list<string> $stageIds */
    public function reorderStages(array $stageIds): void
    {
        $this->execute(app(StageCoordinator::class)->reorder(app(OperationIdFactory::class)->make(), $stageIds));
    }

    public function archiveStage(string $stageId): void
    {
        $this->execute(app(StageCoordinator::class)->archive(app(OperationIdFactory::class)->make(), $stageId));
    }
}

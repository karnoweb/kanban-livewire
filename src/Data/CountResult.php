<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

final readonly class CountResult
{
    /** @param list<StageCount> $stages */
    public function __construct(
        public array $stages,
        public ?int $boardTotal = null,
        public bool $exact = true,
    ) {}

    public function forStage(string $stageId): ?StageCount
    {
        foreach ($this->stages as $stage) {
            if ($stage->stageId === $stageId) {
                return $stage;
            }
        }

        return null;
    }
}

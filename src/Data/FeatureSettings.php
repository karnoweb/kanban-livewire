<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

final readonly class FeatureSettings
{
    public function __construct(
        public bool $dragDrop = true,
        public bool $reorderCards = true,
        public bool $reorderStages = true,
        public bool $createCard = true,
        public bool $updateCard = true,
        public bool $createStage = true,
        public bool $updateStage = true,
        public bool $archiveCard = true,
        public bool $archiveStage = true,
        public bool $restoreCard = true,
        public bool $search = true,
        public bool $filters = true,
        public bool $bulk = true,
        public bool $wip = true,
        public bool $details = true,
        public bool $virtualization = true,
        public bool $realtime = false,
        public bool $customActions = true,
        public bool $toolbar = true,
        public bool $viewSwitch = true,
        public bool $themeSwitch = true,
        public bool $directionSwitch = true,
        public bool $densitySwitch = true,
        public bool $deleteCard = false,
    ) {}

    public function writesEnabled(): bool
    {
        return $this->dragDrop
            || $this->reorderCards
            || $this->reorderStages
            || $this->createCard
            || $this->updateCard
            || $this->createStage
            || $this->updateStage
            || $this->archiveCard
            || $this->archiveStage
            || $this->restoreCard
            || $this->deleteCard
            || $this->bulk
            || $this->customActions;
    }
}

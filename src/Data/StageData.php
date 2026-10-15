<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;
use Karnoweb\Kanban\Enums\WipMode;
use Karnoweb\Kanban\Support\ValueGuard;

final readonly class StageData
{
    public function __construct(
        public string $id,
        public string $boardId,
        public string $title,
        public int $order,
        public bool $visible = true,
        public bool $readOnly = false,
        public bool $disabled = false,
        public ?int $wipLimit = null,
        public WipMode $wipMode = WipMode::Off,
        public ?int $totalCount = null,
        public ?int $loadedCount = null,
        public bool $countExact = true,
        public ?string $color = null,
        public ?string $description = null,
        public bool $archived = false,
    ) {
        ValueGuard::id($id, 'stage.id');
        ValueGuard::id($boardId, 'stage.boardId');
        ValueGuard::requiredText($title, 'stage.title');

        if ($wipLimit !== null && $wipLimit < 0) {
            throw new InvalidArgumentException('WIP limit cannot be negative.');
        }

        if ($totalCount !== null && $totalCount < 0) {
            throw new InvalidArgumentException('Stage totalCount cannot be negative.');
        }

        if ($loadedCount !== null && $loadedCount < 0) {
            throw new InvalidArgumentException('Stage loadedCount cannot be negative.');
        }

        if ($countExact && $totalCount !== null && $loadedCount !== null && $loadedCount > $totalCount) {
            throw new InvalidArgumentException('loadedCount cannot exceed an exact totalCount.');
        }

        if ($color !== null && preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color) !== 1) {
            throw new InvalidArgumentException('Stage color must be a hex accent.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'boardId' => $this->boardId,
            'title' => $this->title,
            'order' => $this->order,
            'visible' => $this->visible,
            'readOnly' => $this->readOnly,
            'disabled' => $this->disabled,
            'wipLimit' => $this->wipLimit,
            'wipMode' => $this->wipMode->value,
            'totalCount' => $this->totalCount,
            'loadedCount' => $this->loadedCount,
            'countExact' => $this->countExact,
            'color' => $this->color,
            'description' => $this->description,
            'archived' => $this->archived,
        ];
    }
}

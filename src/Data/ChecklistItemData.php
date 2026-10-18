<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use Karnoweb\Kanban\Support\ValueGuard;

final readonly class ChecklistItemData
{
    public function __construct(public string $id, public string $title, public bool $done = false)
    {
        ValueGuard::id($id, 'checklist.id');
        ValueGuard::requiredText($title, 'checklist.title', 500);
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'done' => $this->done];
    }
}

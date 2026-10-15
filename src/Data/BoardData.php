<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use Karnoweb\Kanban\Support\ValueGuard;

final readonly class BoardData
{
    public function __construct(
        public string $id,
        public string $title,
        public string $scopeKey,
        public ?string $description = null,
        public ?string $version = null,
        public array $permissions = [],
    ) {
        ValueGuard::id($id, 'board.id');
        ValueGuard::requiredText($title, 'board.title');
        ValueGuard::id($scopeKey, 'board.scopeKey');
        ValueGuard::optionalText($description);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'scopeKey' => $this->scopeKey,
            'description' => $this->description,
            'version' => $this->version,
            'permissions' => $this->permissions,
        ];
    }
}

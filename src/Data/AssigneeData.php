<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use Karnoweb\Kanban\Support\UrlSanitizer;
use Karnoweb\Kanban\Support\ValueGuard;

final readonly class AssigneeData
{
    public string $id;

    public string $name;

    public ?string $avatarUrl;

    public ?string $label;

    public function __construct(
        string $id,
        string $name,
        ?string $avatarUrl = null,
        ?string $label = null,
    ) {
        $this->id = ValueGuard::id($id, 'assignee.id');
        $this->name = ValueGuard::requiredText($name, 'assignee.name');
        $this->avatarUrl = $avatarUrl === null ? null : (new UrlSanitizer)->sanitize($avatarUrl);
        $this->label = ValueGuard::optionalText($label, 200);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatarUrl' => $this->avatarUrl,
            'label' => $this->label,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use Karnoweb\Kanban\Enums\ConfirmationMode;
use Karnoweb\Kanban\Support\ValueGuard;

final readonly class CardActionData
{
    public function __construct(
        public string $id,
        public string $label,
        public bool $enabled = true,
        public ConfirmationMode $confirmation = ConfirmationMode::None,
        public ?string $icon = null,
    ) {
        ValueGuard::id($id, 'action.id');
        ValueGuard::requiredText($label, 'action.label', 120);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'enabled' => $this->enabled,
            'confirmation' => $this->confirmation->value,
            'icon' => $this->icon,
        ];
    }
}

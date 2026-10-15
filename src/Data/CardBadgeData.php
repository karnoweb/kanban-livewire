<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use Karnoweb\Kanban\Enums\BadgeTone;
use Karnoweb\Kanban\Support\ValueGuard;

final readonly class CardBadgeData
{
    public function __construct(
        public string $text,
        public BadgeTone $tone = BadgeTone::Neutral,
        public ?string $description = null,
    ) {
        ValueGuard::requiredText($text, 'badge.text', 80);
        ValueGuard::optionalText($description, 200);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'tone' => $this->tone->value,
            'description' => $this->description,
        ];
    }
}

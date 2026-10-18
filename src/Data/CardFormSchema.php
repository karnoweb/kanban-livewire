<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

final readonly class CardFormSchema
{
    /** @param list<string> $fields */
    public function __construct(
        public array $fields = ['title', 'description', 'priority', 'dueAt', 'stageId'],
        public ?string $createUrl = null,
        public ?string $editUrl = null,
    ) {}

    public function allows(string $field): bool
    {
        return in_array($field, $this->fields, true);
    }
}

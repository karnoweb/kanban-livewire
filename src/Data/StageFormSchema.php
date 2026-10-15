<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

final readonly class StageFormSchema
{
    /** @param list<string> $fields */
    public function __construct(
        public array $fields = ['title', 'color', 'readOnly', 'wipLimit'],
    ) {}

    public function allows(string $field): bool
    {
        return in_array($field, $this->fields, true);
    }
}

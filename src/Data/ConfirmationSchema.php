<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

final readonly class ConfirmationSchema
{
    /** @param list<array{name: string, rules: string, label: string, required: bool}> $fields */
    public function __construct(
        public string $key,
        public array $fields,
    ) {}
}

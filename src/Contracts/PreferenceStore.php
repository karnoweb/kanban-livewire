<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Contracts;

interface PreferenceStore
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value): void;

    public function forget(string $key): void;
}

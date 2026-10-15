<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Preferences;

use Karnoweb\Kanban\Contracts\PreferenceStore;

final class NullPreferenceStore implements PreferenceStore
{
    public function get(string $key): mixed
    {
        return null;
    }

    public function set(string $key, mixed $value): void {}

    public function forget(string $key): void {}
}

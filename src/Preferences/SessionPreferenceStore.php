<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Preferences;

use Karnoweb\Kanban\Contracts\PreferenceStore;

final class SessionPreferenceStore implements PreferenceStore
{
    public function get(string $key): mixed
    {
        try {
            return session()->get($key);
        } catch (\Throwable) {
            return null;
        }
    }

    public function set(string $key, mixed $value): void
    {
        try {
            session()->put($key, $value);
        } catch (\Throwable) {
        }
    }

    public function forget(string $key): void
    {
        try {
            session()->forget($key);
        } catch (\Throwable) {
        }
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Realtime;

/**
 * Collision-free, reversible channel naming: the board key is base64url encoded
 * (alphabet A-Za-z0-9_- only), so "a.b", "a-b" and "a_b" never map to the same channel.
 */
final class ChannelNameFactory
{
    public function name(string $scopeHash, string $boardKey): string
    {
        return 'private-kanban.'.$scopeHash.'.'.$this->encode($boardKey);
    }

    public function encode(string $boardKey): string
    {
        return rtrim(strtr(base64_encode($boardKey), '+/', '-_'), '=');
    }

    public function decode(string $encoded): ?string
    {
        if ($encoded === '' || preg_match('/^[A-Za-z0-9_-]+$/', $encoded) !== 1) {
            return null;
        }

        $raw = base64_decode(strtr($encoded, '-_', '+/'), true);

        if ($raw === false || $raw === '' || $this->encode($raw) !== $encoded) {
            return null;
        }

        return $raw;
    }
}

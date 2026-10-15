<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use InvalidArgumentException;

final class CursorGuard
{
    public function encode(int $offset, string $queryHash): string
    {
        return rtrim(strtr(base64_encode(json_encode([
            'o' => $offset,
            'q' => $queryHash,
        ], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }

    public function offset(?string $cursor, string $queryHash): int
    {
        if ($cursor === null || $cursor === '') {
            return 0;
        }

        if (preg_match('/tenant|scope|secret|password/i', $cursor) === 1) {
            throw new InvalidArgumentException('Cursor is not opaque.');
        }

        $normalized = strtr($cursor, '-_', '+/');
        $pad = strlen($normalized) % 4;

        if ($pad !== 0) {
            $normalized .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($normalized, true);

        if ($decoded === false) {
            throw new InvalidArgumentException('Cursor is invalid.');
        }

        $data = json_decode($decoded, true);

        if (! is_array($data) || array_keys($data) !== ['o', 'q'] || ($data['q'] ?? null) !== $queryHash) {
            throw new InvalidArgumentException('Cursor does not match the active query.');
        }

        return max(0, (int) $data['o']);
    }
}

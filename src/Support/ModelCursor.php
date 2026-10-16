<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use Throwable;

/** Authenticated encryption keeps database keys private and prevents cursor forgery. */
final class ModelCursor
{
    public function encode(array $positions, string $binding, ?int $ttl = null): string
    {
        return Crypt::encryptString(json_encode(['positions' => $positions, 'binding' => $binding,
            'expires' => time() + min($ttl ?? PHP_INT_MAX, (int) config('kanban.eloquent.cursor.ttl', 3600))], JSON_THROW_ON_ERROR));
    }

    public function decode(?string $cursor, string $binding): array
    {
        if ($cursor === null) {
            return [];
        }
        try {
            $decoded = base64_decode($cursor, true);
            if (strlen($cursor) > 32768 || $decoded === false || base64_encode($decoded) !== $cursor) {
                throw new InvalidArgumentException;
            }
            $data = json_decode(Crypt::decryptString($cursor), true, 32, JSON_THROW_ON_ERROR);
            if (! is_array($data) || ! hash_equals($binding, $data['binding'] ?? '') || ($data['expires'] ?? 0) < time() || ! is_array($data['positions'] ?? null)) {
                throw new InvalidArgumentException;
            }

            return $data['positions'];
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Cursor is invalid, expired or belongs to a different query.', previous: $exception);
        }
    }
}

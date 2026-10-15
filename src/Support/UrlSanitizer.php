<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use InvalidArgumentException;

final class UrlSanitizer
{
    public function sanitize(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (preg_match('#^(javascript|data|vbscript|file)\s*:#i', $url) === 1) {
            throw new InvalidArgumentException('URL scheme is not allowed.');
        }

        if (str_starts_with($url, '//')) {
            throw new InvalidArgumentException('Protocol-relative URLs are not allowed.');
        }

        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return $url;
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $allowed = config('kanban.security.allowed_url_schemes', ['http', 'https']);

        if (! in_array($scheme, $allowed, true)) {
            throw new InvalidArgumentException('URL scheme is not allowed.');
        }

        return $url;
    }
}

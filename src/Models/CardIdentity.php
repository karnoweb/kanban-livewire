<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Models;

use InvalidArgumentException;
use Karnoweb\Kanban\Support\ValueGuard;

final readonly class CardIdentity
{
    public function __construct(public string $source, public string $recordId)
    {
        self::assertSource($source);
        if (trim($recordId) === '' || preg_match('/[\x00-\x1F\x7F]/', $recordId)) {
            throw new InvalidArgumentException('A record id must be nonempty and cannot contain control characters.');
        }
        ValueGuard::id($this->encode(), 'card.id');
    }

    public static function assertSource(string $source): void
    {
        if (preg_match('/^[a-z][a-z0-9_-]{0,47}$/D', $source) !== 1) {
            throw new InvalidArgumentException('Source must be a lowercase slug of at most 48 characters.');
        }
    }

    public static function parse(string $id): self
    {
        $parts = explode(':', $id);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException('Card id must be source:recordId.');
        }

        $decoded = rawurldecode($parts[1]);
        if (rawurlencode($decoded) !== $parts[1]) {
            throw new InvalidArgumentException('Card id must use canonical URL encoding for the record key.');
        }

        return new self($parts[0], $decoded);
    }

    public function encode(): string
    {
        return $this->source.':'.rawurlencode($this->recordId);
    }
}

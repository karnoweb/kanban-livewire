<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Realtime;

use Karnoweb\Kanban\Contracts\ScopeResolver;
use Karnoweb\Kanban\Support\BoardRegistry;
use Karnoweb\Kanban\Support\ScopeHasher;
use Throwable;

/**
 * Host registers one line in routes/channels.php:
 *
 *   Broadcast::channel('kanban.{scopeHash}.{board}', fn ($user, $scopeHash, $board) =>
 *       app(\Karnoweb\Kanban\Realtime\ChannelAuthorizer::class)->allows($scopeHash, $board));
 *
 * Access requires: decodable board key, board registered, server-resolved scope hash equal
 * to the requested one, and the board factory accepting the current actor (it throws when denied).
 */
final class ChannelAuthorizer
{
    public function __construct(
        private readonly ScopeResolver $scopes,
        private readonly ScopeHasher $hasher,
        private readonly ChannelNameFactory $channels,
        private readonly BoardRegistry $registry,
    ) {}

    public function allows(string $scopeHash, string $board): bool
    {
        $boardKey = $this->channels->decode($board);

        if ($boardKey === null || ! $this->registry->has($boardKey)) {
            return false;
        }

        try {
            $context = $this->scopes->resolve($boardKey);

            if (! hash_equals($this->hasher->hash($context->scopeKey), $scopeHash) || $context->boardKey !== $boardKey) {
                return false;
            }

            $this->registry->resolve($boardKey, $context);
        } catch (Throwable) {
            return false;
        }

        return true;
    }
}

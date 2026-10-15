<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Realtime;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final class KanbanBroadcastEvent implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    /** @param array<string, mixed> $payload minimal RealtimeEventData payload only */
    public function __construct(
        public readonly string $channel,
        public readonly array $payload,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        $name = str_starts_with($this->channel, 'private-') ? substr($this->channel, 8) : $this->channel;

        return new PrivateChannel($name);
    }

    public function broadcastAs(): string
    {
        return 'kanban.event';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}

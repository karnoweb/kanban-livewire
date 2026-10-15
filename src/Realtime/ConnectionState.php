<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Realtime;

final class ConnectionState
{
    public const ONLINE = 'online';

    public const OFFLINE = 'offline';

    public const RECONNECTING = 'reconnecting';

    public const STALE = 'stale';
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

final class DomIdFactory
{
    public function make(string $instanceId, string $boardKey, string $entity, string $id): string
    {
        $raw = $instanceId.'-'.$boardKey.'-'.$entity.'-'.$id;

        return 'kb-'.preg_replace('/[^A-Za-z0-9_-]/', '-', $raw);
    }
}

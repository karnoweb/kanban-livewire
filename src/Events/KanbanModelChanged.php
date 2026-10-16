<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Events;

use Karnoweb\Kanban\Data\BoardContext;

/** Server event after commit. Broadcasting/audit persistence belongs to the host. */
final readonly class KanbanModelChanged
{
    public function __construct(
        public BoardContext $context,
        public string $source,
        public string $cardId,
        public string $operationId,
        public string $action,
        public ?string $version,
    ) {}
}

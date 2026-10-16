<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Contracts;

use Karnoweb\Kanban\Data\{ActionResult, BoardContext};

interface ModelOperationStore
{
    public function result(BoardContext $context, string $operationId): ?ActionResult;
    public function remembered(BoardContext $context, string $operationId): ?array;
    public function pending(BoardContext $context, string $operationId, string $fingerprint): void;
    public function finish(BoardContext $context, string $operationId, string $fingerprint, ActionResult $result): void;
}

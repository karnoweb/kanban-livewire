<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;
use Karnoweb\Kanban\Enums\ActionType;
use Karnoweb\Kanban\Support\ValueGuard;

final readonly class ActionCommand
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $operationId,
        public ActionType $type,
        public array $payload,
        public ?string $expectedVersion = null,
    ) {
        ValueGuard::id($operationId, 'operationId');
        ValueGuard::formData($payload);
    }

    /** @param array<string, mixed> $payload */
    public static function allowlisted(
        string $operationId,
        string $type,
        array $payload,
        ?string $expectedVersion = null,
    ): self {
        $resolved = ActionType::tryFrom($type);

        if ($resolved === null) {
            throw new InvalidArgumentException('Action type is not allowlisted.');
        }

        return new self($operationId, $resolved, $payload, $expectedVersion);
    }
}

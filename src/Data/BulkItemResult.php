<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use Karnoweb\Kanban\Enums\ActionStatus;

final readonly class BulkItemResult
{
    public function __construct(
        public string $id,
        public ActionStatus $status,
        public ?string $messageCode = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'messageCode' => $this->messageCode,
        ];
    }
}

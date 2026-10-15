<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use Karnoweb\Kanban\Support\ValueGuard;

final readonly class MoveRequest
{
    /** @param array<string, mixed> $formData */
    public function __construct(
        public string $operationId,
        public string $boardId,
        public string $cardId,
        public string $fromStageId,
        public string $toStageId,
        public ?string $beforeCardId = null,
        public ?string $afterCardId = null,
        public ?string $placement = null,
        public ?string $expectedVersion = null,
        public array $formData = [],
    ) {
        ValueGuard::id($operationId, 'operationId');
        ValueGuard::id($boardId, 'boardId');
        ValueGuard::id($cardId, 'cardId');
        ValueGuard::id($fromStageId, 'fromStageId');
        ValueGuard::id($toStageId, 'toStageId');

        if ($beforeCardId !== null) {
            ValueGuard::id($beforeCardId, 'beforeCardId');
        }

        if ($afterCardId !== null) {
            ValueGuard::id($afterCardId, 'afterCardId');
        }

        if ($placement !== null && ! in_array($placement, ['start', 'end'], true)) {
            throw new \InvalidArgumentException('Placement must be start or end.');
        }

        ValueGuard::formData($formData);
    }

    public function toCommand(): ActionCommand
    {
        $type = $this->fromStageId === $this->toStageId
            ? \Karnoweb\Kanban\Enums\ActionType::ReorderCard
            : \Karnoweb\Kanban\Enums\ActionType::MoveCard;

        return new ActionCommand($this->operationId, $type, [
            'boardId' => $this->boardId,
            'cardId' => $this->cardId,
            'fromStageId' => $this->fromStageId,
            'toStageId' => $this->toStageId,
            'beforeCardId' => $this->beforeCardId,
            'afterCardId' => $this->afterCardId,
            'placement' => $this->placement,
            'formData' => $this->formData,
        ], $this->expectedVersion);
    }
}

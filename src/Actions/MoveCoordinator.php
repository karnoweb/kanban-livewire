<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Actions;

use Karnoweb\Kanban\Contracts\DataProvider;
use Karnoweb\Kanban\Data\ActionResult;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardDefinition;
use Karnoweb\Kanban\Data\MoveRequest;
use Karnoweb\Kanban\Enums\ActionStatus;
use Karnoweb\Kanban\Enums\ConfirmationMode;

final class MoveCoordinator
{
    public function __construct(
        private readonly TransitionPolicy $transitions,
        private readonly ConfirmationResolver $confirmations,
    ) {}

    /** @return array{noop: bool, pending: ?array<string, mixed>, command: ?\Karnoweb\Kanban\Data\ActionCommand, result: ?ActionResult} */
    public function prepare(MoveRequest $request, BoardDefinition $definition, DataProvider $provider, BoardContext $context): array
    {
        $card = $provider->card($context, $request->cardId);

        if ($card === null || $card->stageId !== $request->fromStageId) {
            return $this->done(new ActionResult(ActionStatus::NOT_FOUND, messageCode: 'not_found'));
        }

        if ($request->fromStageId === $request->toStageId && ! $definition->settings->features->reorderCards) {
            return [
                'noop' => true,
                'pending' => null,
                'command' => null,
                'result' => new ActionResult(ActionStatus::SUCCESS, messageCode: 'reorder_disabled', noop: true),
            ];
        }

        if ($definition->settings->features->reorderCards === false && $request->fromStageId === $request->toStageId) {
            return $this->done(new ActionResult(ActionStatus::SUCCESS, messageCode: 'reorder_disabled', noop: true));
        }

        if (! $this->transitions->allows($definition->settings->interaction, $request->fromStageId, $request->toStageId)) {
            return $this->done(new ActionResult(ActionStatus::FORBIDDEN, messageCode: 'transition_denied'));
        }

        if ($request->fromStageId !== $request->toStageId && $definition->board->id !== $request->boardId) {
            return $this->done(new ActionResult(ActionStatus::FORBIDDEN, messageCode: 'forbidden'));
        }

        $confirmation = $this->confirmations->forTransition(
            $definition->settings->interaction,
            $request->fromStageId,
            $request->toStageId,
        );

        if ($confirmation['mode'] !== ConfirmationMode::None && $request->formData === []) {
            return [
                'noop' => false,
                'pending' => [
                    'operationId' => $request->operationId,
                    'mode' => $confirmation['mode']->value,
                    'fields' => $confirmation['fields'],
                    'values' => [],
                    'errors' => [],
                    'request' => [
                        'boardId' => $request->boardId,
                        'cardId' => $request->cardId,
                        'fromStageId' => $request->fromStageId,
                        'toStageId' => $request->toStageId,
                        'beforeCardId' => $request->beforeCardId,
                        'afterCardId' => $request->afterCardId,
                        'placement' => $request->placement,
                        'expectedVersion' => $request->expectedVersion,
                    ],
                ],
                'command' => null,
                'result' => null,
            ];
        }

        return [
            'noop' => false,
            'pending' => null,
            'command' => $request->toCommand(),
            'result' => null,
        ];
    }

    /** @return array{noop: bool, pending: null, command: null, result: ActionResult} */
    private function done(ActionResult $result): array
    {
        return ['noop' => $result->noop, 'pending' => null, 'command' => null, 'result' => $result];
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Livewire\Concerns;

use Karnoweb\Kanban\Actions\MoveCoordinator;
use Karnoweb\Kanban\Data\MoveRequest;
use Karnoweb\Kanban\Enums\ViewMode;
use Karnoweb\Kanban\Support\OperationIdFactory;
use Karnoweb\Kanban\Support\PreferenceManager;

trait HandlesMoves
{
    public function switchView(string $view): void
    {
        $mode = ViewMode::tryFrom($view);

        if ($mode === null || ! $this->definition()->settings->views->allows($mode)) {
            return;
        }

        $this->view = $mode->value;
        app(PreferenceManager::class)->rememberView($this->context(), $mode);
        $this->reload();
    }

    public function requestMove(
        string $cardId,
        string $fromStageId,
        string $toStageId,
        ?string $beforeCardId = null,
        ?string $afterCardId = null,
        ?string $placement = null,
        ?string $expectedVersion = null,
    ): void {
        if ($this->connection === 'offline') {
            $this->unknownOperationId = $this->unknownOperationId ?: app(OperationIdFactory::class)->make();
            $this->notice = ['status' => 'unknown_outcome', 'messageCode' => 'unknown_outcome', 'fieldErrors' => [], 'safeMessage' => null, 'successCount' => 0, 'failureCount' => 0, 'noop' => false, 'items' => []];

            return;
        }

        $operationId = app(OperationIdFactory::class)->make();
        $request = new MoveRequest(
            $operationId,
            $this->definition()->board->id,
            $cardId,
            $fromStageId,
            $toStageId,
            $beforeCardId,
            $afterCardId,
            $placement,
            $expectedVersion,
        );
        $plan = app(MoveCoordinator::class)->prepare($request, $this->definition(), $this->provider(), $this->context());

        if ($plan['pending'] !== null) {
            $this->pending = $plan['pending'];
            $this->dispatch('kanban:move-requested', operationId: $operationId);

            return;
        }

        if ($plan['result'] !== null) {
            $result = $plan['result'];
            $this->notice = app(\Karnoweb\Kanban\Actions\ResultMapper::class)->notice($result);
            $this->emitFor($result, $request->toCommand());

            if (! $plan['noop']) {
                $this->reload();
            }

            return;
        }

        if ($plan['command'] !== null) {
            $this->execute($plan['command']);
        }
    }

    public function moveCardToStage(string $cardId, string $toStageId): void
    {
        $from = null;
        $version = null;

        foreach (array_merge($this->listItems, ...array_map(static fn (array $column): array => $column['cards'], $this->columns)) as $card) {
            if (($card['id'] ?? null) === $cardId) {
                $from = $card['stageId'] ?? null;
                $version = $card['version'] ?? null;
                break;
            }
        }

        if (! is_string($from)) {
            return;
        }

        $this->requestMove($cardId, $from, $toStageId, expectedVersion: $version);
    }

    public function reorderCard(string $cardId, string $stageId, ?string $beforeCardId = null, ?string $afterCardId = null): void
    {
        if (! $this->reorderAllowed) {
            $this->notice = ['status' => 'domain_rejected', 'messageCode' => 'reorder_requires_manual_order', 'fieldErrors' => [], 'safeMessage' => null, 'successCount' => 0, 'failureCount' => 0, 'noop' => true, 'items' => []];

            return;
        }

        $this->requestMove($cardId, $stageId, $stageId, $beforeCardId, $afterCardId);
    }

    /** @param array<string, mixed> $values optional override; defaults to the editable pendingValues */
    public function confirmPending(array $values = []): void
    {
        if (! is_array($this->pending)) {
            return;
        }

        $input = $values !== [] ? $values : $this->pendingValues;
        $clean = [];
        $errors = [];

        foreach ($this->pending['fields'] as $name => $rules) {
            $value = $input[$name] ?? '';
            $value = is_scalar($value) ? trim((string) $value) : '';

            if ((bool) ($rules['required'] ?? false) && $value === '') {
                $errors[$name] = 'required';
            }

            if ($value !== '') {
                $clean[(string) $name] = $value;
            }
        }

        $this->pendingValues = $clean;

        if ($errors !== []) {
            $this->pending['errors'] = $errors;
            $this->cardErrors = $errors;

            return;
        }

        $r = $this->pending['request'];
        $move = new MoveRequest(
            (string) $this->pending['operationId'],
            (string) $r['boardId'],
            (string) $r['cardId'],
            (string) $r['fromStageId'],
            (string) $r['toStageId'],
            $r['beforeCardId'],
            $r['afterCardId'],
            $r['placement'],
            $r['expectedVersion'],
            $clean,
        );

        // Re-evaluate against current server state: card, transition and confirmation rules.
        $plan = app(MoveCoordinator::class)->prepare($move, $this->definition(), $this->provider(), $this->context());

        if ($plan['result'] !== null) {
            $result = $plan['result'];
            $this->notice = app(\Karnoweb\Kanban\Actions\ResultMapper::class)->notice($result);
            $this->emitFor($result, $move->toCommand());
            $this->pending = null;
            $this->pendingValues = [];
            $this->reload();

            return;
        }

        $this->execute($plan['command'] ?? $move->toCommand());
    }
    public function cancelPending(): void
    {
        $this->pending = null;
        $this->pendingValues = [];
        $this->cardErrors = [];
    }
}

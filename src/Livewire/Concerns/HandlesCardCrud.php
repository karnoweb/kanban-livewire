<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Livewire\Concerns;

use Karnoweb\Kanban\Actions\CardCoordinator;
use Karnoweb\Kanban\Enums\ActionStatus;
use Karnoweb\Kanban\Support\OperationIdFactory;

trait HandlesCardCrud
{
    public function openInlineCreate(string $stageId): void
    {
        if (count($this->definition()->sources ?? []) > 1 && ! is_string($this->filters['source'] ?? null)) {
            $this->openCardForm($stageId);

            return;
        }
        $this->inlineStageId = $stageId;
        $this->cardDraft = '';
        $this->cardErrors = [];
    }

    public function cancelInlineCreate(): void
    {
        $this->inlineStageId = null;
        $this->cardDraft = '';
        $this->cardErrors = [];
    }

    public function submitInlineCard(): void
    {
        if ($this->inlineStageId === null) {
            return;
        }

        if (trim($this->cardDraft) === '') {
            $this->cardErrors = ['title' => 'required'];

            return;
        }

        $fields = ['title' => $this->cardDraft, 'stageId' => $this->inlineStageId];
        if ($this->definition()->sources !== null && is_string($this->filters['source'] ?? null)) {
            $fields['source'] = $this->filters['source'];
        }
        $result = $this->execute(app(CardCoordinator::class)->create(
            app(OperationIdFactory::class)->make(),
            $fields,
            $this->definition(),
        ));

        if ($result->status === ActionStatus::SUCCESS) {
            $this->cancelInlineCreate();
        }
    }

    public function openCardForm(?string $stageId = null): void
    {
        $this->editingCardId = null;
        $this->editingVersion = null;
        $this->cardFormOpen = true;
        $this->cardForm = ['title' => '', 'stageId' => $stageId ?? ($this->columns[0]['id'] ?? ''), 'description' => ''];
        $sources = $this->definition()->sources;
        if ($sources !== null) {
            $this->cardForm['source'] = is_string($this->filters['source'] ?? null) ? $this->filters['source'] : (count($sources) === 1 ? $sources[0] : '');
        }
        $this->cardErrors = [];
    }

    public function editCard(string $cardId): void
    {
        if (! $this->definition()->settings->features->updateCard) {
            return;
        }
        $card = $this->provider()->card($this->context(), $cardId);
        if ($card === null || ! ($card->permissions['update'] ?? true)) {
            return;
        }
        $this->editingCardId = $cardId;
        $this->editingVersion = $card->version;
        $this->cardForm = array_intersect_key($card->toArray(), array_flip(['title', 'description', 'priority', 'dueAt', 'stageId']));
        if (isset($card->metadata['source'])) {
            $this->cardForm['source'] = $card->metadata['source'];
        }
        $this->cardErrors = [];
        $this->cardFormOpen = true;
    }

    public function cancelCardForm(): void
    {
        $this->cardFormOpen = false;
        $this->editingCardId = null;
        $this->editingVersion = null;
        $this->cardErrors = [];
    }

    public function submitCardForm(): void
    {
        $operation = app(OperationIdFactory::class)->make();
        $coordinator = app(CardCoordinator::class);
        $command = $this->editingCardId === null
            ? $coordinator->create($operation, $this->cardForm, $this->definition())
            : $coordinator->update($operation, $this->editingCardId, $this->cardForm, $this->definition(), $this->editingVersion);
        $result = $this->execute($command);

        if ($result->status === ActionStatus::SUCCESS) {
            $this->cardFormOpen = false;
        }
    }

    public function archiveCard(string $cardId): void
    {
        $version = $this->loadedVersion($cardId);
        $this->execute(app(CardCoordinator::class)->archive(app(OperationIdFactory::class)->make(), $cardId, $version));
    }

    public function restoreCard(string $cardId, ?string $stageId = null): void
    {
        $version = null;
        foreach ($this->archiveItems as $item) {
            if (($item['id'] ?? null) === $cardId) {
                $version = $item['version'] ?? null;
                break;
            }
        }
        $this->execute(app(CardCoordinator::class)->restore(app(OperationIdFactory::class)->make(), $cardId, $stageId, $version));
        $this->archiveOpen = false;
    }
}

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

        $result = $this->execute(app(CardCoordinator::class)->create(
            app(OperationIdFactory::class)->make(),
            ['title' => $this->cardDraft, 'stageId' => $this->inlineStageId],
            $this->definition(),
        ));

        if ($result->status === ActionStatus::SUCCESS) {
            $this->cancelInlineCreate();
        }
    }

    public function openCardForm(?string $stageId = null): void
    {
        $this->cardFormOpen = true;
        $this->cardForm = ['title' => '', 'stageId' => $stageId ?? ($this->columns[0]['id'] ?? ''), 'description' => ''];
        $this->cardErrors = [];
    }

    public function submitCardForm(): void
    {
        $result = $this->execute(app(CardCoordinator::class)->create(
            app(OperationIdFactory::class)->make(),
            $this->cardForm,
            $this->definition(),
        ));

        if ($result->status === ActionStatus::SUCCESS) {
            $this->cardFormOpen = false;
        }
    }

    public function archiveCard(string $cardId): void
    {
        $this->execute(app(CardCoordinator::class)->archive(app(OperationIdFactory::class)->make(), $cardId));
    }

    public function restoreCard(string $cardId, ?string $stageId = null): void
    {
        $this->execute(app(CardCoordinator::class)->restore(app(OperationIdFactory::class)->make(), $cardId, $stageId));
        $this->archiveOpen = false;
    }
}

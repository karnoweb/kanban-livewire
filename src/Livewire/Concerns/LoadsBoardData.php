<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Livewire\Concerns;

use Karnoweb\Kanban\Actions\ActionDispatcher;
use Karnoweb\Kanban\Actions\ResultMapper;
use Karnoweb\Kanban\Contracts\DataProvider;
use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\ActionResult;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardDefinition;
use Karnoweb\Kanban\Data\BoardQuery;
use Karnoweb\Kanban\Data\CardData;
use Karnoweb\Kanban\Enums\ActionStatus;
use Karnoweb\Kanban\Enums\ViewMode;
use Karnoweb\Kanban\Exceptions\BoardAccessDenied;
use Karnoweb\Kanban\Exceptions\UnknownBoard;
use Karnoweb\Kanban\Providers\InlineDataProvider;
use Karnoweb\Kanban\Support\ContextFactory;
use Karnoweb\Kanban\Support\IdempotencyGuard;
use Karnoweb\Kanban\Support\PreferenceManager;
use Karnoweb\Kanban\Support\ScopeHasher;

trait LoadsBoardData
{
    protected function bootBoard(): void
    {
        try {
            $context = $this->context();
            $definition = $this->definition();
        } catch (BoardAccessDenied|UnknownBoard) {
            $this->accessDenied = true;
            $this->columns = [];
            $this->listItems = [];

            return;
        }

        $this->accessDenied = false;
        $this->scopeHash = app(ScopeHasher::class)->hash($context->scopeKey);
        $this->theme = $definition->settings->appearance->theme;
        $this->dir = $definition->settings->appearance->direction;
        $this->reducedMotion = $definition->settings->interaction->reducedMotion || $this->reducedMotion;
        $this->detailSurface = $definition->settings->views->detailSurface;
        $preferred = app(PreferenceManager::class)->view($context);
        $view = $preferred !== null && $definition->settings->views->allows($preferred)
            ? $preferred
            : $definition->settings->views->defaultView;
        $this->view = $view->value;
        $this->syncReorderFlag();
        $this->reload();
    }

    protected function context(): BoardContext
    {
        return app(ContextFactory::class)->make($this->boardKey, $this->instanceId !== '' ? $this->instanceId : null);
    }

    protected function definition(): BoardDefinition
    {
        return app(\Karnoweb\Kanban\Support\BoardRegistry::class)->resolve($this->boardKey, $this->context());
    }

    protected function provider(): DataProvider
    {
        $definition = $this->definition();

        if ($definition->providerClass === InlineDataProvider::class) {
            return new InlineDataProvider($definition->board, $definition->inlineStages, $definition->inlineCards);
        }

        /** @var DataProvider $provider */
        $provider = app($definition->providerClass);

        return $provider;
    }

    protected function query(?string $cursor = null, ?string $stageId = null): BoardQuery
    {
        $definition = $this->definition();

        return BoardQuery::make(
            $this->view,
            $this->search,
            $this->filters,
            $this->sort,
            $this->direction,
            $cursor,
            $definition->settings->pagination->pageSize,
            $stageId,
        );
    }

    protected function currentHash(): string
    {
        return $this->query()->hash();
    }

    protected function reload(): void
    {
        $definition = $this->definition();
        $context = $this->context();
        $provider = $this->provider();
        $query = $this->query();
        $this->queryHash = $query->hash();
        $counts = $provider->counts($context, $query);
        $columns = [];

        foreach ($provider->stages($context, $query) as $stage) {
            if ($stage->archived || ! $stage->visible) {
                continue;
            }

            $page = $provider->stageCards($context, $stage->id, $this->query(null, $stage->id));
            $count = $counts->forStage($stage->id);
            $cards = array_map(static fn (CardData $card): array => $card->toArray(), $page->items);
            $columns[] = [
                'id' => $stage->id,
                'title' => $stage->title,
                'color' => $stage->color,
                'order' => $stage->order,
                'readOnly' => $stage->readOnly,
                'disabled' => $stage->disabled,
                'wipLimit' => $stage->wipLimit,
                'wipMode' => $stage->wipMode->value,
                'totalCount' => $count?->totalCount ?? $page->totalCount,
                'countExact' => $count?->exact ?? $page->countExact,
                'loadedCount' => count($cards),
                'renderedCount' => $this->renderedCount(count($cards), $definition),
                'hasMore' => $page->hasMore,
                'nextCursor' => $page->nextCursor,
                'error' => null,
                'cards' => $cards,
            ];
        }

        $this->columns = $columns;

        if ($this->view === 'list') {
            $list = $provider->listCards($context, $this->query());
            $this->listItems = array_map(static fn (CardData $card): array => $card->toArray(), $list->items);
            $this->listCursor = $list->nextCursor;
            $this->listHasMore = $list->hasMore;
            $this->listTotal = $list->totalCount;
            $this->listExact = $list->countExact;
        }

        $this->syncReorderFlag();
    }

    /**
     * Every loaded card is rendered. Memory/DOM growth is bounded by server pagination
     * (Load More), never by hiding already-loaded cards, so no loaded card becomes unreachable.
     */
    protected function renderedCount(int $loaded, BoardDefinition $definition): int
    {
        return $loaded;
    }

    protected function execute(ActionCommand $command): ActionResult
    {
        $guard = app(IdempotencyGuard::class);

        if (! $guard->begin($this->instanceId, $command->operationId)) {
            $remembered = $guard->remembered($command->operationId);

            if ($remembered instanceof ActionResult) {
                $this->notice = app(ResultMapper::class)->notice($remembered);

                return $remembered;
            }

            $unknown = ActionResult::unknown($command->operationId);
            $this->unknownOperationId = $command->operationId;
            $this->notice = app(ResultMapper::class)->notice($unknown);

            return $unknown;
        }

        $result = app(ActionDispatcher::class)->dispatch($command, $this->context(), $this->definition());
        $guard->finish($this->instanceId, $command->operationId, $result);
        $this->notice = app(ResultMapper::class)->notice($result);
        $this->emitFor($result, $command);

        if ($result->status === ActionStatus::SUCCESS && ! $result->noop) {
            $this->pending = null;
            $this->pendingValues = [];
            $this->cardErrors = [];
            $this->reload();
        }

        if (in_array($result->status, [ActionStatus::CONFLICT, ActionStatus::FORBIDDEN, ActionStatus::NOT_FOUND, ActionStatus::WIP_LIMIT], true)) {
            $this->reload();
        }

        if ($result->status === ActionStatus::UNKNOWN_OUTCOME) {
            $this->unknownOperationId = $command->operationId;
        }

        if ($result->status === ActionStatus::VALIDATION_ERROR) {
            $this->cardErrors = $result->fieldErrors;
            $this->stageErrors = $result->fieldErrors;

            if (is_array($this->pending)) {
                $this->pending['errors'] = $result->fieldErrors;
            }
        }

        return $result;
    }

    protected function emitFor(ActionResult $result, ActionCommand $command): void
    {
        $name = match ($result->status) {
            ActionStatus::SUCCESS => match ($command->type) {
                \Karnoweb\Kanban\Enums\ActionType::CreateCard => 'kanban:card-created',
                \Karnoweb\Kanban\Enums\ActionType::CreateStage => 'kanban:stage-created',
                \Karnoweb\Kanban\Enums\ActionType::ExecuteBulkAction => 'kanban:bulk-completed',
                \Karnoweb\Kanban\Enums\ActionType::MoveCard, \Karnoweb\Kanban\Enums\ActionType::ReorderCard => 'kanban:move-confirmed',
                default => 'kanban:ready',
            },
            ActionStatus::CONFLICT => 'kanban:conflict',
            ActionStatus::FORBIDDEN, ActionStatus::DOMAIN_REJECTED, ActionStatus::WIP_LIMIT => 'kanban:move-rejected',
            default => 'kanban:loading',
        };

        $this->dispatch($name, operationId: $command->operationId, status: $result->status->value);
    }

    protected function syncReorderFlag(): void
    {
        try {
            $manual = $this->sort === 'manualOrder';
            $enabled = $this->definition()->settings->features->reorderCards && $manual;
        } catch (BoardAccessDenied|UnknownBoard) {
            $this->reorderAllowed = false;

            return;
        }

        $this->reorderAllowed = $enabled;
        $this->reorderBlockReason = $enabled ? '' : 'reorder_requires_manual_order';
    }

    protected function columnIndex(string $stageId): ?int
    {
        foreach ($this->columns as $index => $column) {
            if ($column['id'] === $stageId) {
                return $index;
            }
        }

        return null;
    }
}

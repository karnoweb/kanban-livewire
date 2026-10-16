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
        $prefs = app(PreferenceManager::class);
        $this->scopeHash = app(ScopeHasher::class)->hash($context->scopeKey);
        $this->theme = $prefs->theme($context) ?? $definition->settings->appearance->theme;
        $this->dir = $prefs->direction($context) ?? $definition->settings->appearance->direction;
        $this->density = $prefs->density($context) ?? $definition->settings->views->density;
        $this->reducedMotion = $definition->settings->interaction->reducedMotion
            || ! $definition->settings->interaction->animations
            || $this->reducedMotion;
        $this->detailSurface = $definition->settings->views->detailSurface;
        $preferred = $prefs->view($context);
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

        if ($definition->providerClass === \Karnoweb\Kanban\Providers\EloquentKanbanDataProvider::class) {
            return new BoardQuery(view: $this->view, stageId: $stageId, cursor: $cursor,
                limit: $definition->settings->pagination->pageSize,
                search: app(\Karnoweb\Kanban\Support\QueryNormalizer::class)->search($this->search),
                filters: $this->filters, sort: $this->sort, direction: $this->direction);
        }

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

    protected function loadedVersion(string $id): ?string
    {
        $items = array_merge($this->listItems, $this->archiveItems, ...array_column($this->columns, 'cards'));
        foreach ($items as $item) {
            if (($item['id'] ?? null) === $id) {
                return isset($item['version']) ? (string) $item['version'] : null;
            }
        }

        return null;
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
                'windowStart' => 0,
                'hasMore' => $page->hasMore,
                'nextCursor' => $page->nextCursor,
                'error' => null,
                'cards' => $cards,
                'metrics' => $provider instanceof \Karnoweb\Kanban\Contracts\ColumnMetricsProvider ? $provider->metrics($context, $stage->id, $query) : [],
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

    /** Loaded cards stay reachable through explicit window controls. */
    protected function renderedCount(int $loaded, BoardDefinition $definition): int
    {
        return $definition->settings->pagination->virtualization
            ? min($loaded, max(50, $definition->settings->pagination->pageSize * 2)) : $loaded;
    }

    protected function execute(ActionCommand $command): ActionResult
    {
        $guard = app(IdempotencyGuard::class);
        $context = $this->context();
        $guardId = hash('sha256', json_encode([$context->boardKey, $context->scopeKey, $context->actorId, $command->operationId], JSON_THROW_ON_ERROR));

        if (! $guard->begin($this->instanceId, $guardId)) {
            $remembered = $guard->remembered($guardId);

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
        $guard->finish($this->instanceId, $guardId, $result);
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

        $cardId = is_scalar($command->payload['cardId'] ?? null) ? (string) $command->payload['cardId'] : null;
        $fromStageId = is_scalar($command->payload['fromStageId'] ?? null) ? (string) $command->payload['fromStageId'] : null;
        $toStageId = is_scalar($command->payload['toStageId'] ?? null) ? (string) $command->payload['toStageId'] : null;

        $this->dispatch(
            $name,
            instanceId: $this->instanceId,
            boardKey: $this->boardKey,
            operationId: $command->operationId,
            status: $result->status->value,
            messageCode: $result->messageCode ?? $result->status->value,
            cardId: $cardId,
            fromStageId: $fromStageId,
            toStageId: $toStageId,
        );
    }

    protected function syncReorderFlag(): void
    {
        try {
            $manual = $this->sort === 'manualOrder';
            $enabled = $this->definition()->settings->features->reorderCards && $manual;
            if ($this->definition()->providerClass === \Karnoweb\Kanban\Providers\EloquentKanbanDataProvider::class) {
                $enabled = $enabled && ($this->provider()->capabilities($this->context())['manualOrder'] ?? false);
            }
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

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Livewire;

use Karnoweb\Kanban\Data\FeatureSettings;
use Karnoweb\Kanban\Livewire\Concerns\HandlesBulkActions;
use Karnoweb\Kanban\Livewire\Concerns\HandlesCardCrud;
use Karnoweb\Kanban\Livewire\Concerns\HandlesCardDetails;
use Karnoweb\Kanban\Livewire\Concerns\HandlesCustomActions;
use Karnoweb\Kanban\Livewire\Concerns\HandlesMoves;
use Karnoweb\Kanban\Livewire\Concerns\HandlesPreference;
use Karnoweb\Kanban\Livewire\Concerns\HandlesRealtime;
use Karnoweb\Kanban\Livewire\Concerns\HandlesStageCrud;
use Karnoweb\Kanban\Livewire\Concerns\LoadsBoardData;
use Karnoweb\Kanban\Livewire\Concerns\LoadsPages;
use Karnoweb\Kanban\Livewire\Concerns\ManagesBoardQuery;
use Karnoweb\Kanban\Livewire\Concerns\ManagesSelection;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Board extends Component
{
    use HandlesBulkActions;
    use HandlesCardCrud;
    use HandlesCardDetails;
    use HandlesCustomActions;
    use HandlesMoves;
    use HandlesPreference;
    use HandlesRealtime;
    use HandlesStageCrud;
    use LoadsBoardData;
    use LoadsPages;
    use ManagesBoardQuery;
    use ManagesSelection;

    #[Locked]
    public string $boardKey = '';

    #[Locked]
    public string $instanceId = '';

    #[Locked]
    public string $scopeHash = '';

    public string $view = 'board';

    public string $search = '';

    /** @var array<string, mixed> */
    public array $filters = [];

    public string $sort = 'manualOrder';

    public string $direction = 'asc';

    public string $theme = 'system';

    public string $dir = 'ltr';

    public string $density = 'comfortable';

    public string $viewport = 'desktop';

    public bool $reducedMotion = false;

    public bool $accessDenied = false;

    public string $connection = 'online';

    /** @var list<array<string, mixed>> */
    public array $columns = [];

    /** @var list<array<string, mixed>> */
    public array $listItems = [];

    public ?string $listCursor = null;

    public bool $listHasMore = false;

    public ?int $listTotal = null;

    public bool $listExact = true;

    /** @var list<string> */
    public array $selectedIds = [];

    #[Locked]
    public ?array $pending = null;

    /** @var array<string, mixed> client-editable form values; never trusted for the request itself */
    public array $pendingValues = [];

    /** @var array<string, mixed> */
    public array $notice = [];

    public string $cardDraft = '';

    public ?string $inlineStageId = null;

    /** @var array<string, string> */
    public array $cardErrors = [];

    public bool $cardFormOpen = false;

    /** @var array<string, mixed> */
    public array $cardForm = [];

    #[Locked]
    public ?string $editingCardId = null;

    #[Locked]
    public ?string $editingVersion = null;

    public bool $stageFormOpen = false;

    /** @var array<string, mixed> */
    public array $stageForm = [];

    /** @var array<string, string> */
    public array $stageErrors = [];

    public ?array $detail = null;

    #[Locked]
    public ?string $detailCardId = null;

    public string $detailSurface = 'modal';

    public bool $archiveOpen = false;

    /** @var list<array<string, mixed>> */
    public array $archiveItems = [];

    public string $queryHash = '';

    public ?string $unknownOperationId = null;

    public int $listenerSerial = 0;

    public bool $reorderAllowed = true;

    public string $reorderBlockReason = '';

    /** @var array<string, mixed> */
    public array $bulkSummary = [];

    public string $bulkMode = 'partial';

    public int $realtimeApplies = 0;

    public bool $listEnabled = true;

    public bool $boardEnabled = true;

    public array $collapsedStages = [];

    public function toggleStage(string $stageId): void
    {
        $allowed = array_column($this->provider()->stages($this->context(), $this->query()), 'id');
        if (! in_array($stageId, $allowed, true)) {
            return;
        }
        $this->collapsedStages = in_array($stageId, $this->collapsedStages, true)
            ? array_values(array_diff($this->collapsedStages, [$stageId])) : array_merge($this->collapsedStages, [$stageId]);
        app(\Karnoweb\Kanban\Support\PreferenceManager::class)->rememberCollapsed($this->context(), $this->collapsedStages);
    }

    public function moveCardToGroup(string $cardId, string $group): void
    {
        $card = $this->provider()->card($this->context(), $cardId);
        if ($card === null) {
            return;
        }
        $this->execute(new \Karnoweb\Kanban\Data\ActionCommand(
            app(\Karnoweb\Kanban\Support\OperationIdFactory::class)->make(),
            \Karnoweb\Kanban\Enums\ActionType::MoveCard,
            ['cardId' => $cardId, 'fromStageId' => $card->stageId, 'toStageId' => $card->stageId, 'toGroup' => $group],
            $this->loadedVersion($cardId),
        ));
    }

    public function mount(string $boardKey): void
    {
        $this->boardKey = $boardKey;
        $this->instanceId = 'i'.bin2hex(random_bytes(4));
        $this->bootBoard();
        $definition = null;

        if (! $this->accessDenied) {
            $definition = $this->definition();
            $this->collapsedStages = app(\Karnoweb\Kanban\Support\PreferenceManager::class)->collapsed($this->context());
            $this->listEnabled = $definition->settings->views->allows(\Karnoweb\Kanban\Enums\ViewMode::List);
            $this->boardEnabled = $definition->settings->views->allows(\Karnoweb\Kanban\Enums\ViewMode::Board);
            $this->dispatch('kanban:ready', boardKey: $this->boardKey);
        }
    }

    public function render(): mixed
    {
        $realtime = false;
        $channel = '';
        $features = new FeatureSettings;
        $columnWidth = '21rem';
        $sourceOptions = [];
        $groupOptions = [];
        $facetOptions = [];
        $filterFields = ['priority'];
        $sortOptions = ['manualOrder', 'priority', 'dueAt', 'title'];
        $cardTemplates = [];
        $defaultCardTemplate = 'kanban::components.card';
        $stageTemplate = 'kanban::components.stage';
        $listTemplate = 'kanban::components.list';
        $formTemplate = 'kanban::components.card-form';

        if (! $this->accessDenied) {
            $detailSections = $this->detailSectionsForRender();
            if ($this->provider() instanceof \Karnoweb\Kanban\Contracts\FilterOptionsProvider) {
                $facetOptions = $this->provider()->filterOptions($this->context());
            }
            $settings = $this->definition()->settings;
            $features = $settings->features;
            $columnWidth = $settings->views->columnWidth;
            $realtime = $settings->realtime->enabled || $settings->features->realtime;
            $channel = $realtime ? $this->channelName() : '';
            $sourceOptions = $this->definition()->sources ?? [];
            $resolver = app(\Karnoweb\Kanban\Support\ViewResolver::class);
            $defaultCardTemplate = $resolver->name('card', $this->definition()->views['card'] ?? null, $defaultCardTemplate);
            $stageTemplate = $resolver->name('stage', $this->definition()->views['stage'] ?? null, $stageTemplate);
            $listTemplate = $resolver->name('list', $this->definition()->views['list'] ?? null, $listTemplate);
            if ($this->definition()->providerClass === \Karnoweb\Kanban\Providers\EloquentKanbanDataProvider::class) {
                $definitions = app(\Karnoweb\Kanban\Models\BoardSources::class)->definitions($this->definition());
                $filterFields = null;
                $sortOptions = null;
                foreach ($definitions as $model) {
                    if ($model->formTemplate !== null && (($this->cardForm['source'] ?? null) === $model->source || count($definitions) === 1)) {
                        $formTemplate = $resolver->name('card', $model->formTemplate, $formTemplate);
                    }
                    if ($model->cardTemplate !== null) {
                        $cardTemplates[$model->source] = $resolver->name('card', $model->cardTemplate, $defaultCardTemplate);
                    }
                    $filterFields = $filterFields === null ? array_keys($model->filterMap) : array_intersect($filterFields, array_keys($model->filterMap));
                    $modelSorts = array_filter($model->sortFields, fn ($field) => $field === 'manualOrder' || isset($model->fieldMap[$field]));
                    $sortOptions = $sortOptions === null ? $modelSorts : array_intersect($sortOptions, $modelSorts);
                }
                $filterFields ??= [];
                $sortOptions ??= [];
                if ($this->definition()->swimlane !== null) {
                    $groupOptions = $this->provider()->groups($this->context(), $this->query());
                }
            }
        }

        $interaction = $this->accessDenied ? null : $this->definition()->settings->interaction;
        $transitionMatrix = $interaction === null
            ? ['policy' => 'deny', 'transitions' => []]
            : ['policy' => $interaction->transitionPolicy, 'transitions' => $interaction->transitions];

        return view('kanban::livewire.board', [
            'realtimeOn' => $realtime,
            'realtimeChannel' => $channel,
            'features' => $features,
            'dateSettings' => isset($settings) ? $settings->appearance->dates : \Karnoweb\Kanban\Data\DateSettings::fromArray(),
            'columnWidth' => $columnWidth,
            'transitionMatrix' => $transitionMatrix,
            'sourceOptions' => $sourceOptions,
            'groupOptions' => $groupOptions,
            'facetOptions' => $facetOptions,
            'filterFields' => $filterFields,
            'sortOptions' => $sortOptions,
            'cardTemplates' => $cardTemplates,
            'defaultCardTemplate' => $defaultCardTemplate,
            'stageTemplate' => $stageTemplate,
            'listTemplate' => $listTemplate,
            'formTemplate' => $formTemplate,
            'detailSections' => $detailSections ?? [],
            'drawerWidth' => isset($settings) ? $settings->views->drawerWidth : 560,
            'detailSectionViews' => $this->accessDenied ? [] : $this->definition()->detailSectionViews,
        ]);
    }
}

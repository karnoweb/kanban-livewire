<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Livewire;

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

    public bool $stageFormOpen = false;

    /** @var array<string, mixed> */
    public array $stageForm = [];

    /** @var array<string, string> */
    public array $stageErrors = [];

    public ?array $detail = null;

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

    public function mount(string $boardKey): void
    {
        $this->boardKey = $boardKey;
        $this->instanceId = 'i'.bin2hex(random_bytes(4));
        $this->bootBoard();
        $definition = null;

        if (! $this->accessDenied) {
            $definition = $this->definition();
            $this->listEnabled = $definition->settings->views->allows(\Karnoweb\Kanban\Enums\ViewMode::List);
            $this->boardEnabled = $definition->settings->views->allows(\Karnoweb\Kanban\Enums\ViewMode::Board);
            $this->dispatch('kanban:ready', boardKey: $this->boardKey);
        }
    }

    public function render(): mixed
    {
        $realtime = false;
        $channel = '';

        if (! $this->accessDenied) {
            $settings = $this->definition()->settings;
            $realtime = $settings->realtime->enabled || $settings->features->realtime;
            $channel = $realtime ? $this->channelName() : '';
        }

        return view('kanban::livewire.board', ['realtimeOn' => $realtime, 'realtimeChannel' => $channel]);
    }
}

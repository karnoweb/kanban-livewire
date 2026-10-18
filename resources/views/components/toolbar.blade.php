@php
    $showViewSwitch = ($features->viewSwitch ?? true) && $boardEnabled && $listEnabled;
    $hasStartGroup = $features->search || $features->filters;
    $hasEndGroup = $showViewSwitch
        || $features->densitySwitch
        || $features->createCard
        || $features->createStage
        || $features->archiveCard
        || $features->restoreCard
        || $features->deleteCard;
    $hasBulkBar = $features->bulk && count($selectedIds) > 0;
    $hasReorderHint = $features->reorderCards && ! $reorderAllowed;
    $shouldRenderToolbar = $features->toolbar && ($hasStartGroup || $hasEndGroup || $hasBulkBar || $hasReorderHint);
@endphp
@if($shouldRenderToolbar)
<header class="kb-toolbar" data-kb-part="toolbar">
    @if($hasStartGroup)
        <div class="kb-toolbar-group kb-toolbar-group--start" data-kb-part="toolbar-start">
            @if($features->search)
                @include('kanban::components.search-box')
            @endif
            @if($features->filters)
                @include('kanban::components.toolbar-facets')
                <details class="kb-toolbar-options" data-kb-part="filter-options" wire:ignore.self>
                    <summary class="btn btn-ghost">{{ __('kanban::kanban.more_filters') }}</summary>
                    <div class="kb-toolbar-options-body">@include('kanban::components.filter-panel')</div>
                </details>
            @endif
        </div>
    @endif
    @if($hasEndGroup)
        <div class="kb-toolbar-group kb-toolbar-group--end" data-kb-part="toolbar-end">
            @if($showViewSwitch)
                <div class="kb-switch kb-switch--view" role="group" aria-label="{{ __('kanban::kanban.board') }}" data-kb-part="view-switch">
                    <button type="button" class="btn kb-btn kb-btn--view" data-kb-part="view-board" wire:click="switchView('board')" aria-pressed="{{ $view === 'board' ? 'true' : 'false' }}">@include('kanban::components.icon', ['icon' => 'board']) {{ __('kanban::kanban.board') }}</button>
                    <button type="button" class="btn kb-btn kb-btn--view" data-kb-part="view-list" wire:click="switchView('list')" aria-pressed="{{ $view === 'list' ? 'true' : 'false' }}">@include('kanban::components.icon', ['icon' => 'list']) {{ __('kanban::kanban.list') }}</button>
                </div>
            @endif
            @if($features->filters)
                <details class="kb-toolbar-options kb-sort-menu" wire:ignore.self>
                    <summary class="btn kb-btn" aria-label="{{ __('kanban::kanban.sort') }}">↕ {{ __('kanban::kanban.sort') }} <span aria-hidden="true">⌄</span></summary>
                    <div class="kb-toolbar-options-body">@include('kanban::components.sort-options')</div>
                </details>
            @endif
            @if($features->createCard)
                <button type="button" class="btn-primary btn kb-btn kb-btn--primary" data-kb-part="toolbar-create-card" wire:click="openCardForm" aria-label="{{ __('kanban::kanban.create_card') }}" title="{{ __('kanban::kanban.create_card') }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></button>
            @endif
            @if($features->densitySwitch)
                <button type="button" class="btn kb-btn kb-density-toggle" data-kb-part="density-toggle"
                    wire:click="setDensity('{{ $density === 'compact' ? 'comfortable' : 'compact' }}')"
                    aria-pressed="{{ $density === 'compact' ? 'true' : 'false' }}"
                    aria-label="{{ __('kanban::kanban.density_compact') }}" title="{{ __('kanban::kanban.density_compact') }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 4h16v4H4zM4 10h16v4H4zM4 16h16v4H4z"/></svg>
                </button>
            @endif
            <details class="kb-toolbar-options kb-overflow-menu" wire:ignore.self>
                <summary class="btn kb-btn kb-menu-trigger" aria-label="{{ __('kanban::kanban.actions') }}">⋮</summary>
                <div class="kb-toolbar-options-body">
            @if($features->createStage)
                <button type="button" class="btn kb-btn kb-btn--secondary" data-kb-part="toolbar-create-stage" wire:click="openStageForm">{{ __('kanban::kanban.create_stage') }}</button>
            @endif
            @if($features->archiveCard || $features->restoreCard || $features->deleteCard)
                <button type="button" class="btn-ghost btn kb-btn kb-btn--ghost" data-kb-part="toolbar-archive" wire:click="openArchive">{{ __('kanban::kanban.archive_browser') }}</button>
            @endif

                </div>
            </details>
        </div>
    @endif
    @if($features->bulk)
        @include('kanban::components.bulk-toolbar')
    @endif
    @if($hasReorderHint)
        <p class="kb-reorder-reason" data-kb-part="reorder-reason">{{ __('kanban::kanban.reorder_blocked') }}</p>
    @endif
</header>
@endif

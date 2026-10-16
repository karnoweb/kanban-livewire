@php
    $showViewSwitch = ($features->viewSwitch ?? true) && $boardEnabled && $listEnabled;
    $hasStartGroup = $features->search || $showViewSwitch || $features->filters;
    $hasEndGroup = $features->themeSwitch
        || $features->directionSwitch
        || $features->densitySwitch
        || $features->createCard
        || $features->createStage
        || $features->archiveCard
        || $features->restoreCard;
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
            @if($showViewSwitch)
                <div class="kb-switch kb-switch--view" role="group" aria-label="{{ __('kanban::kanban.board') }}" data-kb-part="view-switch">
                    <button type="button" class="btn kb-btn kb-btn--view" data-kb-part="view-board" wire:click="switchView('board')" aria-pressed="{{ $view === 'board' ? 'true' : 'false' }}">{{ __('kanban::kanban.board') }}</button>
                    <button type="button" class="btn kb-btn kb-btn--view" data-kb-part="view-list" wire:click="switchView('list')" aria-pressed="{{ $view === 'list' ? 'true' : 'false' }}">{{ __('kanban::kanban.list') }}</button>
                </div>
            @endif
            @if($features->filters)
                <details class="kb-toolbar-options" data-kb-part="filter-options" wire:ignore.self>
                    <summary class="btn btn-ghost">{{ __('kanban::kanban.filters_sort') }}</summary>
                    <div class="kb-toolbar-options-body">@include('kanban::components.filter-panel')</div>
                </details>
            @endif
        </div>
    @endif
    @if($hasEndGroup)
        <div class="kb-toolbar-group kb-toolbar-group--end" data-kb-part="toolbar-end">
            @if($features->createCard)
                <button type="button" class="btn-primary btn kb-btn kb-btn--primary" data-kb-part="toolbar-create-card" wire:click="openCardForm">{{ __('kanban::kanban.create_card') }}</button>
            @endif
            @if($features->createStage)
                <button type="button" class="btn kb-btn kb-btn--secondary" data-kb-part="toolbar-create-stage" wire:click="openStageForm">{{ __('kanban::kanban.create_stage') }}</button>
            @endif
            @if($features->archiveCard || $features->restoreCard)
                <button type="button" class="btn-ghost btn kb-btn kb-btn--ghost" data-kb-part="toolbar-archive" wire:click="openArchive">{{ __('kanban::kanban.archive_browser') }}</button>
            @endif
            @if($features->themeSwitch || $features->directionSwitch || $features->densitySwitch)
            <details class="kb-toolbar-options" data-kb-part="appearance-options" wire:ignore.self>
                <summary class="btn btn-ghost">{{ __('kanban::kanban.appearance') }}</summary>
                <div class="kb-toolbar-options-body">
            @if($features->themeSwitch)
                <div class="kb-switch kb-switch--theme" role="group" aria-label="{{ __('kanban::kanban.theme') }}" data-kb-part="theme-switch">
                    <button type="button" class="btn kb-btn kb-btn--theme" data-kb-part="theme-light" wire:click="setTheme('light')" aria-pressed="{{ $theme === 'light' ? 'true' : 'false' }}">{{ __('kanban::kanban.theme_light') }}</button>
                    <button type="button" class="btn kb-btn kb-btn--theme" data-kb-part="theme-dark" wire:click="setTheme('dark')" aria-pressed="{{ $theme === 'dark' ? 'true' : 'false' }}">{{ __('kanban::kanban.theme_dark') }}</button>
                    <button type="button" class="btn kb-btn kb-btn--theme" data-kb-part="theme-system" wire:click="setTheme('system')" aria-pressed="{{ $theme === 'system' ? 'true' : 'false' }}">{{ __('kanban::kanban.theme_system') }}</button>
                </div>
            @endif
            @if($features->directionSwitch)
                <div class="kb-switch kb-switch--dir" role="group" aria-label="{{ __('kanban::kanban.direction') }}" data-kb-part="dir-switch">
                    <button type="button" class="btn kb-btn kb-btn--dir" data-kb-part="dir-ltr" wire:click="setDirection('ltr')" aria-pressed="{{ $dir === 'ltr' ? 'true' : 'false' }}">LTR</button>
                    <button type="button" class="btn kb-btn kb-btn--dir" data-kb-part="dir-rtl" wire:click="setDirection('rtl')" aria-pressed="{{ $dir === 'rtl' ? 'true' : 'false' }}">RTL</button>
                </div>
            @endif
            @if($features->densitySwitch)
                <div class="kb-switch kb-switch--density" role="group" aria-label="{{ __('kanban::kanban.density') }}" data-kb-part="density-switch">
                    <button type="button" class="btn kb-btn kb-btn--density" data-kb-part="density-comfortable" wire:click="setDensity('comfortable')" aria-pressed="{{ $density === 'comfortable' ? 'true' : 'false' }}">{{ __('kanban::kanban.density_comfortable') }}</button>
                    <button type="button" class="btn kb-btn kb-btn--density" data-kb-part="density-compact" wire:click="setDensity('compact')" aria-pressed="{{ $density === 'compact' ? 'true' : 'false' }}">{{ __('kanban::kanban.density_compact') }}</button>
                </div>
            @endif
                </div>
            </details>
            @endif
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

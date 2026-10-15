@include('kanban::components.search-box')
<header class="kb-toolbar">
    @if($boardEnabled && $listEnabled)
        <div class="kb-switch" role="group" aria-label="{{ __('kanban::kanban.board') }}">
            <button type="button" wire:click="switchView('board')" aria-pressed="{{ $view === 'board' ? 'true' : 'false' }}">{{ __('kanban::kanban.board') }}</button>
            <button type="button" wire:click="switchView('list')" aria-pressed="{{ $view === 'list' ? 'true' : 'false' }}">{{ __('kanban::kanban.list') }}</button>
        </div>
    @endif
    @include('kanban::components.filter-panel')
    <button type="button" wire:click="openCardForm">{{ __('kanban::kanban.create_card') }}</button>
    <button type="button" wire:click="openStageForm">{{ __('kanban::kanban.create_stage') }}</button>
    <button type="button" wire:click="openArchive">{{ __('kanban::kanban.archive_browser') }}</button>
    @include('kanban::components.bulk-toolbar')
    @if(! $reorderAllowed)
        <p class="kb-reorder-reason">{{ __('kanban::kanban.reorder_blocked') }}</p>
    @endif
</header>

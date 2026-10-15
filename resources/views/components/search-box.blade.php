<label class="kb-search">
    <span class="kb-sr">{{ __('kanban::kanban.search') }}</span>
    <input type="search" wire:model.live.debounce.300ms="search" aria-label="{{ __('kanban::kanban.search') }}">
</label>

<label class="kb-search" data-kb-part="search">
    <span class="kb-sr">{{ __('kanban::kanban.search') }}</span>
    <input type="search" class="input kb-search-input" data-kb-part="search-input" placeholder="{{ __('kanban::kanban.search') }}…" wire:model.live.debounce.300ms="search" aria-label="{{ __('kanban::kanban.search') }}">
</label>

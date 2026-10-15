<aside class="kb-drawer" role="dialog" aria-modal="true" wire:keydown.escape="closeDetails">
    <h2>{{ $detail['title'] ?? '' }}</h2>
    <button type="button" wire:click="closeDetails">{{ __('kanban::kanban.close') }}</button>
</aside>

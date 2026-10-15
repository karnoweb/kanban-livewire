<div class="kb-modal" role="dialog" aria-modal="true" wire:keydown.escape="closeDetails">
    <h2>{{ $detail['title'] ?? '' }}</h2>
    <p>{{ $detail['description'] ?? '' }}</p>
    <button type="button" wire:click="closeDetails">{{ __('kanban::kanban.close') }}</button>
    @include('kanban::components.card-actions', ['card' => $detail])
</div>

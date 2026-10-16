<aside role="dialog" aria-modal="true" aria-label="{{ __('kanban::kanban.details') }}" wire:keydown.escape="closeDetails" class="kb-drawer" data-kb-part="details-drawer">
    <header class="kb-modal-header" data-kb-part="details-header">
        <h2 class="kb-modal-title" data-kb-part="details-title">{{ $detail['title'] ?? '' }}</h2>
        <button type="button" class="btn-ghost btn kb-btn kb-btn--ghost" data-kb-part="details-close" wire:click="closeDetails">{{ __('kanban::kanban.close') }}</button>
    </header>
    @include('kanban::components.card-badges', ['card' => $detail])
    @if(!empty($detail['description']))
        <p class="kb-modal-body" data-kb-part="details-description">{{ $detail['description'] }}</p>
    @endif
    @include('kanban::components.card-attachments', ['card' => $detail])
    @include('kanban::components.card-assignees', ['card' => $detail])
</aside>

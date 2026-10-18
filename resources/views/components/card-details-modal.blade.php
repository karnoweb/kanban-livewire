<div role="dialog" aria-modal="true" aria-label="{{ __('kanban::kanban.details') }}" wire:keydown.escape="closeDetails" class="kb-modal" data-kb-part="details-modal">
    <header class="kb-modal-header" data-kb-part="details-header">
        <div>
            @if(!empty($detail['metadata']['code']))
                <span class="kb-card-code" data-kb-part="card-code">{{ $detail['metadata']['code'] }}</span>
            @endif
            <h2 class="kb-modal-title" data-kb-part="details-title">{{ $detail['title'] ?? '' }}</h2>
        </div>
        @include('kanban::components.close-button', ['closeAction' => 'closeDetails', 'closePart' => 'details-close'])
    </header>
    @include('kanban::components.card-detail-content')
    @include('kanban::components.card-detail-footer')
</div>

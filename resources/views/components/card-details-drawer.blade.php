<aside role="dialog" aria-modal="true" aria-label="{{ __('kanban::kanban.details') }}" wire:keydown.escape="closeDetails" class="kb-drawer" data-kb-part="details-drawer" style="--kb-drawer-width: {{ $drawerWidth ?? 560 }}px">
    <header class="kb-modal-header" data-kb-part="details-header">
        <div><span class="kb-card-code">{{ $detail['metadata']['code'] ?? $detail['id'] ?? '' }}</span><h2 class="kb-modal-title" data-kb-part="details-title">{{ $detail['title'] ?? '' }}</h2></div>
        @include('kanban::components.close-button', ['closeAction' => 'closeDetails', 'closePart' => 'details-close'])
    </header>
    @include('kanban::components.card-detail-content')
    @include('kanban::components.card-detail-footer')
</aside>

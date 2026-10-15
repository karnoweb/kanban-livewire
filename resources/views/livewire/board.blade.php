<div
    class="kb"
    dir="{{ $dir }}"
    data-theme="{{ $theme }}"
    data-view="{{ $view }}"
    data-viewport="{{ $viewport }}"
    data-reduced-motion="{{ $reducedMotion ? 'true' : 'false' }}"
    data-connection="{{ $connection }}"
    data-board="{{ $boardKey }}"
    data-kb-serial="{{ $listenerSerial }}"
    @if($realtimeOn)
    data-kb-realtime="true"
    data-kb-channel="{{ $realtimeChannel }}"
    @endif
>
    @include('kanban::components.theme-provider')
    @if($accessDenied)
        @include('kanban::components.error-state', ['kind' => 'permission-denied'])
    @else
        @include('kanban::components.toolbar')
        @include('kanban::components.connection-status')
        @if(($notice['status'] ?? null) === 'conflict')
            @include('kanban::components.conflict-banner')
        @endif
        @if($view === 'list')
            @include('kanban::components.list')
        @else
            @include('kanban::components.board')
        @endif
        @if($pending)
            @include('kanban::components.move-confirmation')
        @endif
        @if($cardFormOpen)
            @include('kanban::components.card-form')
        @endif
        @if($stageFormOpen)
            @include('kanban::components.stage-form')
        @endif
        @if($detail && $detailSurface === 'drawer')
            @include('kanban::components.card-details-drawer')
        @elseif($detail)
            @include('kanban::components.card-details-modal')
        @endif
        @if($archiveOpen)
            @include('kanban::components.archive-browser')
        @endif
        @include('kanban::components.realtime-warning')
    @endif
    <div class="kb-live" aria-live="polite">{{ $notice['messageCode'] ?? '' }}</div>
</div>

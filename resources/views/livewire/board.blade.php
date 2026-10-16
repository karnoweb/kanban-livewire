<div
    class="kb kb--view-{{ $view }} kb--theme-{{ $theme }} kb--dir-{{ $dir }} kb--density-{{ $density }}"
    dir="{{ $dir }}"
    style="--kb-column-width: {{ $columnWidth ?? '18.5rem' }}"
    data-kb-part="root"
    data-kb-theme="{{ $theme }}"
    @if($theme !== 'system') data-theme="{{ $theme }}" @endif
    data-view="{{ $view }}"
    data-density="{{ $density }}"
    data-viewport="{{ $viewport }}"
    data-reduced-motion="{{ $reducedMotion ? 'true' : 'false' }}"
    data-connection="{{ $connection }}"
    data-board="{{ $boardKey }}"
    data-instance="{{ $instanceId }}"
    data-reorder="{{ $reorderAllowed ? 'true' : 'false' }}"
    data-kb-serial="{{ $listenerSerial }}"
    data-kb-transitions='@json($transitionMatrix)'
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
        @include('kanban::components.notice-banner')
        @if($view === 'list')
            @include($listTemplate ?? 'kanban::components.list')
        @else
            @include('kanban::components.board')
        @endif
        @if($pending)
            @include('kanban::components.move-confirmation')
        @endif
        @if($cardFormOpen)
            @include($formTemplate ?? 'kanban::components.card-form')
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
    <div class="kb-live" aria-live="polite" data-kb-part="live-region">{{ $notice['messageCode'] ?? '' }}</div>
</div>

<header class="kb-stage-header">
    <h2>
        @if($column['color'])<span class="kb-accent" style="background: {{ $column['color'] }}"></span>@endif
        {{ $column['title'] }}
    </h2>
    @include('kanban::components.count-badge', ['column' => $column])
    @if($column['wipLimit'])
        <span class="kb-wip" data-exceeded="{{ ($column['loadedCount'] >= $column['wipLimit'] && $column['countExact']) ? 'true' : 'false' }}">
            {{ __('kanban::kanban.wip') }} {{ $column['totalCount'] ?? '—' }}/{{ $column['wipLimit'] }}
        </span>
    @endif
</header>

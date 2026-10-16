@php
    $wipExceeded = $column['wipLimit'] && (($column['totalCount'] ?? $column['loadedCount']) >= $column['wipLimit'] && $column['countExact']);
    $wipRatio = $column['wipLimit'] ? min(100, (int) round((($column['totalCount'] ?? $column['loadedCount']) / max(1, (int) $column['wipLimit'])) * 100)) : 0;
@endphp
<header class="kb-stage-header" data-kb-part="stage-header">
    <h2 class="kb-stage-title" data-kb-part="stage-title">
        @if($column['color'])<span class="kb-accent" data-kb-part="stage-accent" style="background: {{ $column['color'] }}"></span>@endif
        <span class="kb-stage-title-text">{{ $column['title'] }}</span>
        @if($column['readOnly'] ?? false)
            <span class="kb-stage-lock" data-kb-part="stage-readonly" title="{{ __('kanban::kanban.read_only_stage') }}">{{ __('kanban::kanban.read_only_stage') }}</span>
        @endif
    </h2>
    <div class="kb-stage-meta" data-kb-part="stage-meta">
        <button type="button" class="btn-ghost btn kb-btn kb-btn--ghost" data-kb-part="stage-collapse" wire:click="toggleStage(@js($column['id']))" aria-expanded="{{ in_array($column['id'], $collapsedStages ?? [], true) ? 'false' : 'true' }}">{{ __('kanban::kanban.collapse') }}</button>
        @include('kanban::components.count-badge', ['column' => $column])
        @if($column['wipLimit'])
            <span class="badge badge-outline kb-wip" data-kb-part="stage-wip" data-exceeded="{{ $wipExceeded ? 'true' : 'false' }}" style="--kb-wip-pct: {{ $wipRatio }}%">
                {{ __('kanban::kanban.wip') }} {{ $column['totalCount'] ?? '—' }}/{{ $column['wipLimit'] }}
            </span>
        @endif
    </div>
    @foreach($column['metrics'] ?? [] as $name => $value)
        <span class="kb-stage-metric" data-kb-part="stage-metric">{{ $name }}: {{ $value ?? '—' }}</span>
    @endforeach
</header>

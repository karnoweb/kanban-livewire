@php
    $stageCount = $column['totalCount'] ?? $column['loadedCount'];
    $wipLimit = $column['wipLimit'] ?? null;
    $wipRatio = $wipLimit ? min(100, (int) round(($stageCount / max(1, (int) $wipLimit)) * 100)) : 0;
    $wipExceeded = $wipLimit && $stageCount >= $wipLimit && ($column['countExact'] ?? false);
@endphp
<span class="badge badge-ghost kb-count" data-kb-part="stage-count" data-exact="{{ ($column['countExact'] ?? false) ? 'true' : 'false' }}" @if($wipLimit) aria-label="{{ __('kanban::kanban.wip') }} {{ $stageCount }}/{{ $wipLimit }}" @endif>
    @if($wipLimit)
        <span class="kb-count-progress" aria-hidden="true" data-exceeded="{{ $wipExceeded ? 'true' : 'false' }}" style="--kb-wip-pct: {{ $wipRatio }}%"><span></span></span>
        {{ $stageCount }}/{{ $wipLimit }}
    @else
        {{ $column['totalCount'] === null ? __('kanban::kanban.count_unknown') : ($column['countExact'] ? $column['totalCount'] : __('kanban::kanban.count_approx', ['count' => $column['totalCount']])) }}
    @endif
    <span class="kb-rendered" data-rendered="{{ $column['renderedCount'] }}"></span>
</span>

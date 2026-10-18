@php
    $stage = collect($columns)->firstWhere('id', $card['stageId']);
    $meta = is_array($card['metadata'] ?? null) ? $card['metadata'] : [];
    $code = isset($meta['code']) && is_string($meta['code']) ? $meta['code'] : null;
    $progress = isset($meta['progress']) && is_numeric($meta['progress']) ? max(0, min(100, (int) $meta['progress'])) : null;
    $prio = (string) ($card['priority'] ?? '');
    $selected = in_array($card['id'], $selectedIds, true);
@endphp
<article class="kb-row {{ $selected ? 'kb-row--selected' : '' }}" role="row" data-kb-part="list-row"
    data-card="{{ $card['id'] }}" data-stage="{{ $card['stageId'] ?? '' }}"
    wire:key="row-{{ $instanceId }}-{{ $boardKey }}-{{ $card['id'] }}"
    @if(!empty($stage['color'])) style="--kb-stage-color: {{ $stage['color'] }}" @endif>
    <div role="cell" class="kb-row-selection">
        @if($features->bulk)<input type="checkbox" class="kb-row-check" data-kb-part="list-row-select" wire:click="toggleSelected('{{ $card['id'] }}')" @checked($selected) aria-label="{{ $card['title'] }}">@endif
    </div>
    <div role="cell" class="kb-row-code"><span class="kb-card-code" data-kb-part="card-code">{{ $code ?? '—' }}</span></div>
    <div class="kb-row-main" role="cell" data-kb-part="list-row-main">
        <h3 class="kb-row-heading" data-kb-part="list-row-title">{{ $card['title'] }}</h3>
        @if(!empty($card['description']))<p class="kb-card-text kb-row-text" data-kb-part="list-row-description">{{ $card['description'] }}</p>@endif
    </div>
    <div role="cell"><span class="kb-stage-pill" data-kb-part="list-row-stage">{{ $stage['title'] ?? $card['stageId'] }}</span></div>
    <div role="cell">@if($prio !== '')<span class="kb-stat kb-stat--priority kb-stat--priority-{{ $prio }}" data-kb-part="list-row-priority">{{ \Illuminate\Support\Facades\Lang::has('kanban::kanban.priority_'.$prio) ? __('kanban::kanban.priority_'.$prio) : $prio }}</span>@else<span class="kb-table-empty">—</span>@endif</div>
    <div role="cell">@include('kanban::components.card-badges', ['card' => $card])</div>
    <div class="kb-row-progress" role="cell">
        @if($progress !== null)
            <div class="kb-card-progress" role="progressbar" aria-label="{{ $card['title'] }}" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"><span class="kb-card-progress-bar" style="inline-size: {{ $progress }}%"></span></div>
            <span data-kb-part="card-progress">{{ $progress }}%</span>
        @else<span class="kb-table-empty">—</span>@endif
    </div>
    <div role="cell">@if(!empty($card['dueAt']))<time dir="ltr" class="kb-stat" data-kb-part="card-due" datetime="{{ $card['dueAt'] }}">{{ app(\Karnoweb\Kanban\Support\DateFormatter::class)->format($card['dueAt'], settings: $dateSettings ?? null) }}</time>@else<span class="kb-table-empty">—</span>@endif</div>
    <div role="cell">@include('kanban::components.card-assignees', ['card' => $card])</div>
    <div role="cell">@if(isset($meta['points']) && is_numeric($meta['points']))<span class="kb-stat kb-stat--points" data-kb-part="card-points">{{ $meta['points'] }} pt</span>@else<span class="kb-table-empty">—</span>@endif</div>
    <div role="cell">@include('kanban::components.card-actions', ['card' => $card])</div>
</article>

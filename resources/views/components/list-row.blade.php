@php
    $stageTitle = collect($columns)->firstWhere('id', $card['stageId'])['title'] ?? $card['stageId'];
    $stageColor = collect($columns)->firstWhere('id', $card['stageId'])['color'] ?? null;
    $meta = is_array($card['metadata'] ?? null) ? $card['metadata'] : [];
    $code = isset($meta['code']) && is_string($meta['code']) ? $meta['code'] : null;
    $points = isset($meta['points']) && is_numeric($meta['points']) ? (int) $meta['points'] : null;
    $progress = isset($meta['progress']) && is_numeric($meta['progress']) ? max(0, min(100, (int) $meta['progress'])) : null;
    $checklistDone = isset($meta['checklistDone']) && is_numeric($meta['checklistDone']) ? (int) $meta['checklistDone'] : null;
    $checklistTotal = isset($meta['checklistTotal']) && is_numeric($meta['checklistTotal']) ? (int) $meta['checklistTotal'] : null;
    $commentsCount = isset($meta['commentsCount']) && is_numeric($meta['commentsCount']) ? (int) $meta['commentsCount'] : null;
    $prio = (string) ($card['priority'] ?? '');
    $selected = in_array($card['id'], $selectedIds, true);
@endphp
<article
    class="kb-row {{ $prio !== '' ? 'kb-row--priority-'.$prio : '' }} {{ $selected ? 'kb-row--selected' : '' }}"
    role="row"
    data-kb-part="list-row"
    data-card="{{ $card['id'] }}"
    data-stage="{{ $card['stageId'] ?? '' }}"
    @if($prio !== '') data-priority="{{ $prio }}" @endif
    wire:key="row-{{ $instanceId }}-{{ $boardKey }}-{{ $card['id'] }}"
    @if($stageColor) style="--kb-stage-color: {{ $stageColor }}" @endif
>
    <div class="kb-row-main" data-kb-part="list-row-main">
        <div class="kb-row-title" data-kb-part="list-row-header">
            @if($features->bulk)
                <input type="checkbox" class="kb-row-check" data-kb-part="list-row-select" wire:click="toggleSelected('{{ $card['id'] }}')" @checked($selected) aria-label="{{ $card['title'] }}">
            @endif
            @if($code)
                <span class="kb-card-code" data-kb-part="card-code">{{ $code }}</span>
            @endif
            <h3 class="kb-row-heading" data-kb-part="list-row-title">{{ $card['title'] }}</h3>
            <span class="kb-stage-pill" data-kb-part="list-row-stage">{{ $stageTitle }}</span>
            @if($prio !== '')
                <span class="kb-stat kb-stat--priority kb-stat--priority-{{ $prio }}" data-kb-part="list-row-priority">{{ $prio }}</span>
            @endif
        </div>
        @if(!empty($card['description']))
            <p class="kb-card-text kb-row-text" data-kb-part="list-row-description">{{ $card['description'] }}</p>
        @endif
        <div class="kb-row-sub" data-kb-part="list-row-sub">
            @include('kanban::components.card-badges', ['card' => $card])
            @include('kanban::components.card-attachments', ['card' => $card])
        </div>
    </div>
    <div class="kb-row-meta" data-kb-part="list-row-meta">
        <div class="kb-card-stats" data-kb-part="list-row-stats">
            @if(!empty($card['dueAt']))
                <time class="kb-stat kb-stat--due" data-kb-part="card-due" datetime="{{ $card['dueAt'] }}">📅 {{ $card['dueAt'] }}</time>
            @endif
            @if($checklistTotal !== null && $checklistTotal > 0)
                <span class="kb-stat kb-stat--checklist {{ $checklistDone === $checklistTotal ? 'is-complete' : '' }}" data-kb-part="card-checklist">☑ {{ $checklistDone ?? 0 }}/{{ $checklistTotal }}</span>
            @endif
            @if($commentsCount !== null && $commentsCount > 0)
                <span class="kb-stat kb-stat--comments" data-kb-part="card-comments">💬 {{ $commentsCount }}</span>
            @endif
            @if($points !== null)
                <span class="kb-stat kb-stat--points" data-kb-part="card-points">{{ $points }} pt</span>
            @endif
            @if($progress !== null)
                <span class="kb-stat kb-stat--progress" data-kb-part="card-progress">{{ $progress }}%</span>
            @endif
        </div>
        @include('kanban::components.card-assignees', ['card' => $card])
    </div>
    @include('kanban::components.card-actions', ['card' => $card])
</article>

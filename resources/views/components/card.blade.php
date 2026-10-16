@php
    $meta = is_array($card['metadata'] ?? null) ? $card['metadata'] : [];
    $code = isset($meta['code']) && is_string($meta['code']) ? $meta['code'] : null;
    $points = isset($meta['points']) && is_numeric($meta['points']) ? (int) $meta['points'] : null;
    $progress = isset($meta['progress']) && is_numeric($meta['progress']) ? max(0, min(100, (int) $meta['progress'])) : null;
    $checklistDone = isset($meta['checklistDone']) && is_numeric($meta['checklistDone']) ? (int) $meta['checklistDone'] : null;
    $checklistTotal = isset($meta['checklistTotal']) && is_numeric($meta['checklistTotal']) ? (int) $meta['checklistTotal'] : null;
    $commentsCount = isset($meta['commentsCount']) && is_numeric($meta['commentsCount']) ? (int) $meta['commentsCount'] : null;
    $blocked = ! empty($meta['blocked']);
    $selected = in_array($card['id'], $selectedIds, true);
    $prio = (string) ($card['priority'] ?? '');
    $canDrag = ($features->dragDrop && ($card['permissions']['move'] ?? true)) || ($reorderAllowed && ($card['permissions']['reorder'] ?? true));
@endphp
<article
    class="card kb-card {{ $prio !== '' ? 'kb-card--priority-'.$prio : '' }} {{ $selected ? 'kb-card--selected' : '' }} {{ $blocked ? 'kb-card--blocked' : '' }}"
    data-kb-part="card"
    @if($prio !== '') data-priority="{{ $prio }}" @endif
    id="{{ app(\Karnoweb\Kanban\Support\DomIdFactory::class)->make($instanceId, $boardKey, 'card', $card['id']) }}"
    wire:key="card-{{ $instanceId }}-{{ $boardKey }}-{{ $card['id'] }}-{{ $card['stageId'] }}"
    draggable="{{ $canDrag ? 'true' : 'false' }}"
    data-card="{{ $card['id'] }}"
    data-stage="{{ $card['stageId'] ?? '' }}"
    data-version="{{ $card['version'] ?? '' }}"
>
    @include('kanban::components.card-badges', ['card' => $card])

    <div class="kb-card-head" data-kb-part="card-header">
        @if($features->bulk)
            <input
                type="checkbox"
                class="checkbox checkbox-sm kb-card-check"
                data-kb-part="card-select"
                wire:click.stop="toggleSelected('{{ $card['id'] }}')"
                @checked($selected)
                aria-label="{{ $card['title'] }}"
            >
        @endif
        <button type="button" class="kb-drag" data-kb-part="card-drag-handle" aria-label="{{ __('kanban::kanban.move_to') }}" @if(! $canDrag) disabled @endif>⠿</button>
        <div class="kb-card-heading" data-kb-part="card-heading">
            @if($code)
                <span class="kb-card-code" data-kb-part="card-code">{{ $code }}</span>
            @endif
            <h3 class="kb-card-title" data-kb-part="card-title">{{ $card['title'] }}</h3>
        </div>
        @include('kanban::components.card-actions', ['card' => $card])
    </div>

    @if(!empty($card['description']))
        <p class="kb-card-text" data-kb-part="card-description">{{ $card['description'] }}</p>
    @endif

    @if($progress !== null)
        <div class="kb-card-progress" data-kb-part="card-progress" aria-label="{{ $card['title'] }}" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
            <span class="kb-card-progress-bar" style="inline-size: {{ $progress }}%"></span>
        </div>
    @endif

    @include('kanban::components.card-attachments', ['card' => $card])

    <footer class="kb-card-footer" data-kb-part="card-footer">
        <div class="kb-card-stats" data-kb-part="card-stats">
            @if($prio !== '')
                <span class="kb-stat kb-stat--priority kb-stat--priority-{{ $prio }}" data-kb-part="card-priority">{{ $prio }}</span>
            @endif
            @if(!empty($card['dueAt']))
                <time class="kb-stat kb-stat--due" data-kb-part="card-due" datetime="{{ $card['dueAt'] }}">📅 {{ $card['dueAt'] }}</time>
            @endif
            @if($checklistTotal !== null && $checklistTotal > 0)
                <span class="kb-stat kb-stat--checklist {{ $checklistDone === $checklistTotal ? 'is-complete' : '' }}" data-kb-part="card-checklist">☑ {{ $checklistDone ?? 0 }}/{{ $checklistTotal }}</span>
            @endif
            @if($commentsCount !== null && $commentsCount > 0)
                <span class="kb-stat kb-stat--comments" data-kb-part="card-comments">💬 {{ $commentsCount }}</span>
            @endif
            @if(!empty($card['attachments']))
                <span class="kb-stat kb-stat--files" data-kb-part="card-files-count">📎 {{ count($card['attachments']) }}</span>
            @endif
            @if($points !== null)
                <span class="kb-stat kb-stat--points" data-kb-part="card-points">{{ $points }} pt</span>
            @endif
        </div>
        @include('kanban::components.card-assignees', ['card' => $card])
    </footer>
</article>

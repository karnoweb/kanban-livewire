@php($activePriority = (string) ($filters['priority'] ?? ''))
<div class="kb-filters" data-kb-part="filters">
    <span class="kb-filters-label" data-kb-part="filters-label">{{ __('kanban::kanban.filters') }}</span>
    @if(in_array('priority', $filterFields ?? ['priority'], true))
    <div class="kb-filter-chips" role="group" aria-label="{{ __('kanban::kanban.filters') }}" data-kb-part="filter-priority">
        <button type="button" class="btn btn-outline kb-chip {{ $activePriority === '' ? 'is-active' : '' }}" data-kb-part="filter-priority-all" wire:click="setBoardFilter('priority', '')" aria-pressed="{{ $activePriority === '' ? 'true' : 'false' }}">{{ __('kanban::kanban.priority_all') }}</button>
        @foreach(['high' => 'priority_high', 'medium' => 'priority_medium', 'low' => 'priority_low'] as $prio => $labelKey)
            <button type="button" class="btn btn-outline kb-chip kb-chip--{{ $prio }} {{ $activePriority === $prio ? 'is-active' : '' }}" data-kb-part="filter-priority-{{ $prio }}" wire:click="setBoardFilter('priority', '{{ $prio }}')" aria-pressed="{{ $activePriority === $prio ? 'true' : 'false' }}">{{ __('kanban::kanban.'.$labelKey) }}</button>
        @endforeach
    </div>
    @endif
    @if(count($sourceOptions ?? []) > 1)
        <label>{{ __('kanban::kanban.source') }}
            <select class="select kb-input" data-kb-part="filter-source" wire:change="setBoardFilter('source', $event.target.value)">
                <option value="">{{ __('kanban::kanban.all') }}</option>
                @foreach($sourceOptions as $source)<option value="{{ $source }}" @selected(($filters['source'] ?? '') === $source)>{{ $source }}</option>@endforeach
            </select>
        </label>
    @endif
    @if($columns !== [])
        <label>{{ __('kanban::kanban.status') }}
            <select class="select kb-input" data-kb-part="filter-stage" wire:change="setBoardFilter('stage', $event.target.value)">
                <option value="">{{ __('kanban::kanban.all') }}</option>
                @foreach($columns as $item)<option value="{{ $item['id'] }}" @selected(($filters['stage'] ?? '') === $item['id'])>{{ $item['title'] }}</option>@endforeach
            </select>
        </label>
    @endif
    @if(($groupOptions['items'] ?? []) !== [])
        <label>{{ __('kanban::kanban.group') }}
            <select class="select kb-input" data-kb-part="filter-group" wire:change="setBoardFilter('group', $event.target.value)">
                <option value="">{{ __('kanban::kanban.all') }}</option>
                @foreach($groupOptions['items'] as $group)<option value="{{ $group['id'] }}" @selected(($filters['group'] ?? '') === $group['id'])>{{ $group['title'] }}</option>@endforeach
            </select>
        </label>
    @endif
    <button type="button" class="btn-ghost btn kb-btn kb-btn--ghost" data-kb-part="clear-filters" wire:click="clearFilters">{{ __('kanban::kanban.clear_search') }}</button>
</div>

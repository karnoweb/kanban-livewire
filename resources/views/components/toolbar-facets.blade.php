@foreach(['assignee' => 'assignees', 'badge' => 'labels'] as $field => $label)
    @if(($facetOptions[$field] ?? []) !== [])
    <label class="kb-facet">
        @include('kanban::components.icon', ['icon' => $field])
        <span class="sr-only">{{ __('kanban::kanban.'.$label) }}</span>
        <select aria-label="{{ __('kanban::kanban.'.$label) }}" data-kb-part="toolbar-filter-{{ $field }}" wire:change="setBoardFilter('{{ $field }}', $event.target.value)">
            <option value="">{{ __('kanban::kanban.'.$label) }}</option>
            @foreach($facetOptions[$field] as $value => $text)<option value="{{ $value }}" @selected(($filters[$field] ?? '') === (string) $value)>{{ $text }}</option>@endforeach
        </select>
    </label>
    @endif
@endforeach

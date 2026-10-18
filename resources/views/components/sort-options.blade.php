    <div class="kb-sort-chips" role="group" aria-label="{{ __('kanban::kanban.sort') }}" data-kb-part="sort-group">
        @foreach(['manualOrder' => 'Manual', 'priority' => 'Priority', 'dueAt' => 'Due', 'title' => 'A–Z'] as $field => $label)
            @continue(! in_array($field, $sortOptions ?? ['manualOrder', 'priority', 'dueAt', 'title'], true))
            <button type="button" class="btn btn-outline kb-chip {{ $sort === $field ? 'is-active' : '' }}" data-kb-part="sort-{{ $field }}" wire:click="sortBy('{{ $field }}')" aria-pressed="{{ $sort === $field ? 'true' : 'false' }}">{{ $label }}@if($sort === $field) {{ $direction === 'asc' ? '↑' : '↓' }}@endif</button>
        @endforeach
    </div>

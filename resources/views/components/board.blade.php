<div class="kb-board" data-scroll="x">
    @forelse($columns as $column)
        @include('kanban::components.stage', ['column' => $column])
    @empty
        @include('kanban::components.empty-state', ['kind' => 'empty-board'])
    @endforelse
</div>

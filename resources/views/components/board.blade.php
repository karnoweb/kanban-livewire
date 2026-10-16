<div class="kb-board" data-scroll="x" data-kb-part="board">
    @forelse($columns as $column)
        @include($stageTemplate ?? 'kanban::components.stage', ['column' => $column])
    @empty
        @include('kanban::components.empty-state', ['kind' => 'empty-board'])
    @endforelse
</div>

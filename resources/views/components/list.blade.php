<div class="kb-list" role="table" aria-label="{{ __('kanban::kanban.list') }}" data-kb-part="list">
    <div class="kb-list-scroll" data-kb-part="list-body">
        <div class="kb-table-head" role="row" data-kb-part="list-header">
            <div role="columnheader" aria-label="{{ __('kanban::kanban.title') }}"></div>
            <div role="columnheader">#</div>
            @foreach(['title', 'status', 'priority', 'labels', 'progress', 'due', 'assignees', 'points', 'actions'] as $field)
                <div role="columnheader">{{ __('kanban::kanban.'.$field) }}</div>
            @endforeach
        </div>
        @forelse($listItems as $card)
            @include('kanban::components.list-row', ['card' => $card])
        @empty
            @include('kanban::components.empty-state', ['kind' => ($search !== '' || $filters !== []) ? 'empty-filter' : 'empty-board'])
        @endforelse
        @if($listHasMore)
            <button type="button" class="kb-load-more" data-kb-part="list-load-more" wire:click="loadMoreList">{{ __('kanban::kanban.load_more') }}</button>
        @endif
    </div>
    <p class="kb-list-count" data-kb-part="list-count">{{ $listTotal === null ? __('kanban::kanban.count_unknown') : ($listExact ? $listTotal : __('kanban::kanban.count_approx', ['count' => $listTotal])) }}</p>
</div>

<div class="kb-list" role="table" aria-label="{{ __('kanban::kanban.list') }}">
    <div class="kb-list-head" role="row">
        <span role="columnheader">{{ __('kanban::kanban.title') }}</span>
        <button type="button" role="columnheader" wire:click="sortBy('dueAt')">due</button>
        <button type="button" role="columnheader" wire:click="sortBy('priority')">priority</button>
    </div>
    @forelse($listItems as $card)
        @include('kanban::components.list-row', ['card' => $card])
    @empty
        @include('kanban::components.empty-state', ['kind' => ($search !== '' || $filters !== []) ? 'empty-filter' : 'empty-board'])
    @endforelse
    @if($listHasMore)
        <button type="button" wire:click="loadMoreList">{{ __('kanban::kanban.load_more') }}</button>
    @endif
    <p class="kb-list-count">{{ $listTotal === null ? __('kanban::kanban.count_unknown') : ($listExact ? $listTotal : __('kanban::kanban.count_approx', ['count' => $listTotal])) }}</p>
</div>

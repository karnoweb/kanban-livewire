<p class="kb-connection" data-state="{{ $connection }}">
    @if($connection !== 'online'){{ __('kanban::kanban.offline') }}@endif
</p>

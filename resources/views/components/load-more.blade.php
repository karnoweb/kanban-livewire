@if($column['hasMore'] ?? false)
    <button type="button" class="kb-load-more" wire:click="loadMore('{{ $column['id'] }}')" wire:loading.attr="disabled">{{ __('kanban::kanban.load_more') }}</button>
@endif

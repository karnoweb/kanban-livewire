@if($selectedIds !== [])
    <div class="kb-bulk" aria-live="polite">
        <span>{{ __('kanban::kanban.selected', ['count' => count($selectedIds)]) }}</span>
        <button type="button" wire:click="runBulk('archive', 'partial')">{{ __('kanban::kanban.archive') }}</button>
        <button type="button" wire:click="runBulk('archive', 'all-or-nothing')">{{ __('kanban::kanban.bulk') }}</button>
        <button type="button" wire:click="clearSelection">{{ __('kanban::kanban.clear_search') }}</button>
        @if($bulkSummary !== [])
            <span data-success="{{ $bulkSummary['successCount'] ?? 0 }}" data-failed="{{ $bulkSummary['failureCount'] ?? 0 }}"></span>
        @endif
    </div>
@endif

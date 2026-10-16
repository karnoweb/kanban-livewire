@if($selectedIds !== [])
    <div class="kb-bulk" data-kb-part="bulk-bar" aria-live="polite">
        <span class="kb-bulk-count" data-kb-part="bulk-count">{{ __('kanban::kanban.selected', ['count' => count($selectedIds)]) }}</span>
        <button type="button" class="btn-error btn kb-btn kb-btn--danger" data-kb-part="bulk-archive-partial" wire:click="runBulk('archive', 'partial')">{{ __('kanban::kanban.archive') }}</button>
        <button type="button" class="btn kb-btn kb-btn--secondary" data-kb-part="bulk-archive-atomic" wire:click="runBulk('archive', 'all-or-nothing')">{{ __('kanban::kanban.bulk') }}</button>
        <button type="button" class="btn-ghost btn kb-btn kb-btn--ghost" data-kb-part="bulk-clear" wire:click="clearSelection">{{ __('kanban::kanban.clear_search') }}</button>
        @if($bulkSummary !== [])
            <span data-kb-part="bulk-summary" data-success="{{ $bulkSummary['successCount'] ?? 0 }}" data-failed="{{ $bulkSummary['failureCount'] ?? 0 }}"></span>
        @endif
    </div>
@endif

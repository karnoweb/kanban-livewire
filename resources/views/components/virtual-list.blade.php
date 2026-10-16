<div class="kb-virtual" data-kb-part="virtual-list" data-overscan="4" data-rendered="{{ $column['renderedCount'] }}" data-loaded="{{ $column['loadedCount'] }}">
    @if(($column['windowStart'] ?? 0) > 0)
        <button type="button" class="btn kb-btn" data-kb-part="window-previous" wire:click="shiftStageWindow(@js($column['id']), -1)">{{ __('kanban::kanban.previous_cards') }}</button>
    @endif
    @foreach(array_slice($column['cards'], $column['windowStart'] ?? 0, $column['renderedCount']) as $card)
        @include($cardTemplates[$card['metadata']['source'] ?? ''] ?? $defaultCardTemplate ?? 'kanban::components.card', ['card' => $card])
    @endforeach
    @if(($column['windowStart'] ?? 0) + $column['renderedCount'] < $column['loadedCount'])
        <button type="button" class="btn kb-btn" data-kb-part="window-next" wire:click="shiftStageWindow(@js($column['id']), 1)">{{ __('kanban::kanban.next_cards') }}</button>
    @endif
</div>

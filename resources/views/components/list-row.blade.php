<div class="kb-row" role="row" wire:key="row-{{ $instanceId }}-{{ $boardKey }}-{{ $card['id'] }}">
    <label><input type="checkbox" wire:click="toggleSelected('{{ $card['id'] }}')" @checked(in_array($card['id'], $selectedIds, true))> <span>{{ $card['title'] }}</span></label>
    <span>{{ $card['stageId'] }}</span>
    <span>{{ $card['priority'] ?? '' }}</span>
    <button type="button" wire:click="openDetails('{{ $card['id'] }}')">{{ __('kanban::kanban.details') }}</button>
    @foreach($columns as $target)
        @if($target['id'] !== $card['stageId'])
            <button type="button" wire:click="moveCardToStage('{{ $card['id'] }}', '{{ $target['id'] }}')">{{ __('kanban::kanban.move_to') }} {{ $target['title'] }}</button>
        @endif
    @endforeach
</div>

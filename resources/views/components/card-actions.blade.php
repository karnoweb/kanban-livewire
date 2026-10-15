<menu class="kb-card-menu">
    <button type="button" wire:click="openDetails('{{ $card['id'] }}')">{{ __('kanban::kanban.details') }}</button>
    <button type="button" wire:click="archiveCard('{{ $card['id'] }}')">{{ __('kanban::kanban.archive') }}</button>
    @foreach($columns as $target)
        @if($target['id'] !== ($card['stageId'] ?? null))
            <button type="button" wire:click="moveCardToStage('{{ $card['id'] }}', '{{ $target['id'] }}')">{{ $target['title'] }}</button>
        @endif
    @endforeach
    @foreach(($card['actions'] ?? []) as $action)
        @if($action['enabled'] ?? false)
            <button type="button" wire:click="runCardAction('{{ $card['id'] }}', '{{ $action['id'] }}')">{{ $action['label'] }}</button>
        @endif
    @endforeach
</menu>

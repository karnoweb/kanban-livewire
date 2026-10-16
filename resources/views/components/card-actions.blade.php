@php
    $hasCustomActions = $features->customActions && count(array_filter($card['actions'] ?? [], static fn (array $a): bool => (bool) ($a['enabled'] ?? false))) > 0;
    $hasMoveActions = $features->dragDrop && count($columns) > 1;
    $hasAnyCardMenu = $features->details || $features->updateCard || $features->archiveCard || $hasMoveActions || $hasCustomActions;
@endphp
@if($hasAnyCardMenu)
<details class="kb-menu" data-kb-part="card-menu" wire:ignore.self>
    <summary class="btn btn-ghost kb-menu-trigger" data-kb-part="card-menu-trigger" aria-label="{{ __('kanban::kanban.actions') }}">⋯</summary>
    <menu class="kb-card-menu" data-kb-part="card-menu-list">
        @if($features->details)
            <button type="button" class="btn btn-ghost kb-menu-item" data-kb-part="card-action-details" wire:click="openDetails('{{ $card['id'] }}')">{{ __('kanban::kanban.details') }}</button>
        @endif
        @if($features->archiveCard)
            <button type="button" class="btn btn-ghost kb-menu-item kb-menu-item--danger" data-kb-part="card-action-archive" wire:click="archiveCard('{{ $card['id'] }}')">{{ __('kanban::kanban.archive') }}</button>
        @endif
        @if($features->updateCard && ($card['permissions']['update'] ?? true))
            <button type="button" class="btn btn-ghost kb-menu-item" data-kb-part="card-action-edit" wire:click="editCard(@js($card['id']))">{{ __('kanban::kanban.update_card') }}</button>
        @endif
        @if($hasMoveActions)
            @foreach($columns as $target)
                @if($target['id'] !== ($card['stageId'] ?? null))
                    <button type="button" class="btn btn-ghost kb-menu-item" data-kb-part="card-action-move" data-target-stage="{{ $target['id'] }}" wire:click="moveCardToStage('{{ $card['id'] }}', '{{ $target['id'] }}')">{{ __('kanban::kanban.move_to') }}: {{ $target['title'] }}</button>
                @endif
            @endforeach
        @endif
        @if($hasCustomActions)
            @foreach(($card['actions'] ?? []) as $action)
                @if($action['enabled'] ?? false)
                    <button type="button" class="btn btn-ghost kb-menu-item" data-kb-part="card-action-custom" data-action="{{ $action['id'] }}" wire:click="runCardAction('{{ $card['id'] }}', '{{ $action['id'] }}')">{{ $action['label'] }}</button>
                @endif
            @endforeach
        @endif
    </menu>
</details>
@endif

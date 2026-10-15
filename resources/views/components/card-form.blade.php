<form class="kb-card-form" wire:submit="submitCardForm">
    <label>{{ __('kanban::kanban.title') }} <input wire:model="cardForm.title"></label>
    <label>stage <input wire:model="cardForm.stageId"></label>
    @foreach($cardErrors as $field => $error)<span data-field="{{ $field }}">{{ $error }}</span>@endforeach
    <button type="submit">{{ __('kanban::kanban.save') }}</button>
</form>

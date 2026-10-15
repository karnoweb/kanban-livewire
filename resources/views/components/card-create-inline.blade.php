<form class="kb-inline-create" wire:submit="submitInlineCard">
    <label>{{ __('kanban::kanban.title') }}
        <input wire:model="cardDraft" wire:keydown.escape="cancelInlineCreate" wire:keydown.enter="submitInlineCard">
    </label>
    @error('title') <span>{{ $message }}</span> @enderror
    @if(!empty($cardErrors['title']))<span class="kb-field-error">{{ $cardErrors['title'] }}</span>@endif
    <button type="submit">{{ __('kanban::kanban.save') }}</button>
    <button type="button" wire:click="cancelInlineCreate">{{ __('kanban::kanban.cancel') }}</button>
</form>

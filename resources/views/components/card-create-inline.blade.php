<form class="kb-inline-create" data-kb-part="inline-create" wire:submit="submitInlineCard">
    <label class="kb-field">{{ __('kanban::kanban.title') }}
        <input class="input kb-input" wire:model="cardDraft" wire:keydown.escape="cancelInlineCreate" wire:keydown.enter="submitInlineCard" autofocus>
    </label>
    @error('title') <span class="kb-field-error">{{ $message }}</span> @enderror
    @if(!empty($cardErrors['title']))<span class="kb-field-error">{{ $cardErrors['title'] }}</span>@endif
    <div class="kb-form-actions" data-kb-part="inline-create-actions">
        <button type="submit" class="btn-primary btn kb-btn kb-btn--primary">{{ __('kanban::kanban.save') }}</button>
        <button type="button" class="btn-ghost btn kb-btn kb-btn--ghost" wire:click="cancelInlineCreate">{{ __('kanban::kanban.cancel') }}</button>
    </div>
</form>

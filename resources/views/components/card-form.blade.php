<form role="dialog" aria-modal="true" aria-label="{{ __('kanban::kanban.create_card') }}" wire:keydown.escape="cancelCardForm" class="kb-card-form" data-kb-part="card-form" wire:submit="submitCardForm">
    <label class="kb-field" data-kb-part="card-form-title">{{ __('kanban::kanban.title') }} <input class="input kb-input" wire:model="cardForm.title"></label>
    <label class="kb-field" data-kb-part="card-form-stage">{{ __('kanban::kanban.status') }} <input class="input kb-input" wire:model="cardForm.stageId" @if($editingCardId) readonly @endif></label>
    @if(count($sourceOptions ?? []) > 1)
        <label class="kb-field">{{ __('kanban::kanban.source') }}
            <select class="select kb-input" wire:model="cardForm.source" @if($editingCardId) disabled @endif><option value="">{{ __('kanban::kanban.select_source') }}</option>
                @foreach($sourceOptions as $source)<option value="{{ $source }}">{{ $source }}</option>@endforeach
            </select>
        </label>
    @endif
    @foreach($cardErrors as $field => $error)<span class="kb-field-error" data-field="{{ $field }}">{{ $error }}</span>@endforeach
    <div class="kb-form-actions" data-kb-part="card-form-actions">
        <button type="submit" class="btn-primary btn kb-btn kb-btn--primary">{{ __('kanban::kanban.save') }}</button>
        <button type="button" class="btn kb-btn" wire:click="cancelCardForm">{{ __('kanban::kanban.cancel') }}</button>
    </div>
</form>

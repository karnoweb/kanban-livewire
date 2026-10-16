<form role="dialog" aria-modal="true" aria-label="{{ __('kanban::kanban.create_stage') }}" wire:keydown.escape="$set('stageFormOpen', false)" class="kb-stage-form" data-kb-part="stage-form" wire:submit="submitStageForm">
    <label class="kb-field" data-kb-part="stage-form-title">{{ __('kanban::kanban.title') }} <input class="input kb-input" wire:model="stageForm.title"></label>
    <label class="kb-field" data-kb-part="stage-form-color">color <input class="input kb-input" wire:model="stageForm.color"></label>
    @foreach($stageErrors as $field => $error)<span class="kb-field-error">{{ $error }}</span>@endforeach
    <div class="kb-form-actions" data-kb-part="stage-form-actions">
        <button type="submit" class="btn-primary btn kb-btn kb-btn--primary">{{ __('kanban::kanban.save') }}</button>
        <button type="button" class="btn btn-ghost kb-btn" wire:click="$set('stageFormOpen', false)">{{ __('kanban::kanban.cancel') }}</button>
    </div>
</form>

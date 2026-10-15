<form class="kb-stage-form" wire:submit="submitStageForm">
    <label>{{ __('kanban::kanban.title') }} <input wire:model="stageForm.title"></label>
    <label>color <input wire:model="stageForm.color"></label>
    @foreach($stageErrors as $field => $error)<span>{{ $error }}</span>@endforeach
    <button type="submit">{{ __('kanban::kanban.save') }}</button>
</form>

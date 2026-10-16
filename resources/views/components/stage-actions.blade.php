@if($features->updateStage || $features->archiveStage)
<menu class="kb-stage-menu" data-kb-part="stage-actions">
    @if($features->updateStage)
        <button type="button" class="kb-stage-action" data-kb-part="stage-edit" wire:click="openStageForm('{{ $column['id'] }}')">{{ __('kanban::kanban.create_stage') }}</button>
    @endif
    @if($features->archiveStage)
        <button type="button" class="kb-stage-action kb-stage-action--danger" data-kb-part="stage-archive" wire:click="archiveStage('{{ $column['id'] }}')">{{ __('kanban::kanban.archive') }}</button>
    @endif
</menu>
@endif

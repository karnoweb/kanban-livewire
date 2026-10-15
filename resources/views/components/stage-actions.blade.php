<menu class="kb-stage-menu">
    <button type="button" wire:click="openStageForm('{{ $column['id'] }}')">{{ __('kanban::kanban.create_stage') }}</button>
    <button type="button" wire:click="archiveStage('{{ $column['id'] }}')">{{ __('kanban::kanban.archive') }}</button>
</menu>

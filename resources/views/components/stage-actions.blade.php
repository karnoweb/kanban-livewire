@if($features->updateStage || $features->archiveStage)
<details class="kb-menu kb-stage-options" data-kb-part="stage-actions" wire:ignore.self>
<summary class="kb-menu-trigger" aria-label="{{ __('kanban::kanban.stage_actions') }}">⋯</summary>
<menu class="kb-stage-menu kb-card-menu">
    @if($features->updateStage)
        <button type="button" class="kb-stage-action" data-kb-part="stage-edit" wire:click="openStageForm('{{ $column['id'] }}')">{{ __('kanban::kanban.update_stage') }}</button>
    @endif
    @if($features->archiveStage)
        <button type="button" class="kb-stage-action kb-stage-action--danger" data-kb-part="stage-archive" wire:click="archiveStage('{{ $column['id'] }}')">{{ __('kanban::kanban.archive') }}</button>
    @endif
</menu>
</details>
@endif

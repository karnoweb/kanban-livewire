<div class="kb-error" role="alert" data-error="{{ $kind }}">
    <p>{{ __('kanban::kanban.'.str_replace('-', '_', $kind === 'permission-denied' ? 'permission_denied' : ($kind === 'error' ? 'error' : $kind))) }}</p>
    @if(($stageId ?? null))
        <button type="button" wire:click="retryStage('{{ $stageId }}')">{{ __('kanban::kanban.retry') }}</button>
    @endif
</div>

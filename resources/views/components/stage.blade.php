<section
    class="kb-stage {{ ($column['error'] ?? null) ? 'kb-stage-error' : '' }}"
    style="--kb-column-width: 18rem"
    data-stage="{{ $column['id'] }}"
    wire:key="stage-{{ $instanceId }}-{{ $column['id'] }}"
>
    @include('kanban::components.stage-header', ['column' => $column])
    @include('kanban::components.stage-actions', ['column' => $column])
    @if($column['error'])
        @include('kanban::components.error-state', ['kind' => 'error', 'stageId' => $column['id']])
    @endif
    <div class="kb-stage-scroll" tabindex="0">
        @if($column['cards'] === [] && $search === '' && $filters === [])
            @include('kanban::components.empty-state', ['kind' => 'empty-stage'])
        @elseif($column['cards'] === [])
            @include('kanban::components.empty-state', ['kind' => 'empty-filter'])
        @else
            @include('kanban::components.virtual-list', ['column' => $column])
        @endif
        @if($inlineStageId === $column['id'])
            @include('kanban::components.card-create-inline')
        @else
            <button type="button" wire:click="openInlineCreate('{{ $column['id'] }}')">{{ __('kanban::kanban.create_card') }}</button>
        @endif
    </div>
    @include('kanban::components.load-more', ['column' => $column])
</section>

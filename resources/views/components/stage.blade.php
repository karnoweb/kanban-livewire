@php
    $wipExceeded = ($column['wipLimit'] ?? null) && (($column['totalCount'] ?? $column['loadedCount']) >= $column['wipLimit']) && ($column['countExact'] ?? false);
@endphp
<section
    class="kb-stage {{ ($column['error'] ?? null) ? 'kb-stage-error' : '' }} {{ ($column['readOnly'] ?? false) ? 'kb-stage--readonly' : '' }} {{ ($column['disabled'] ?? false) ? 'kb-stage--disabled' : '' }} {{ $wipExceeded ? 'kb-stage--wip-exceeded' : '' }}"
    style="--kb-column-width: {{ $columnWidth ?? '21rem' }}; @if($column['color']) --kb-stage-color: {{ $column['color'] }}; @endif"
    data-kb-part="stage"
    data-stage="{{ $column['id'] }}"
    data-readonly="{{ ($column['readOnly'] ?? false) ? 'true' : 'false' }}"
    data-disabled="{{ ($column['disabled'] ?? false) ? 'true' : 'false' }}"
    data-wip-limit="{{ $column['wipLimit'] ?? '' }}"
    data-wip-mode="{{ $column['wipMode'] ?? 'off' }}"
    data-total-count="{{ $column['totalCount'] ?? '' }}"
    wire:key="stage-{{ $instanceId }}-{{ $column['id'] }}"
>
    @include('kanban::components.stage-header', ['column' => $column])
    @if($column['error'])
        @include('kanban::components.error-state', ['kind' => 'error', 'stageId' => $column['id']])
    @endif
    <div class="kb-stage-scroll" data-kb-part="stage-body" tabindex="0">
        @if($column['cards'] === [] && $search === '' && $filters === [])
            @include('kanban::components.empty-state', ['kind' => 'empty-stage'])
        @elseif($column['cards'] === [])
            @include('kanban::components.empty-state', ['kind' => 'empty-filter'])
        @else
            @include('kanban::components.virtual-list', ['column' => $column])
        @endif
    </div>
    @include('kanban::components.load-more', ['column' => $column])
    <footer class="kb-stage-footer" data-kb-part="stage-footer">
        @if($features->createCard && ! ($column['readOnly'] ?? false) && ! ($column['disabled'] ?? false))
            @if($inlineStageId === $column['id'])
                @include('kanban::components.card-create-inline')
            @else
                <button type="button" class="kb-add" data-kb-part="stage-add-card" wire:click="openInlineCreate('{{ $column['id'] }}')">+ {{ __('kanban::kanban.create_card') }}</button>
            @endif
        @endif
    </footer>
</section>

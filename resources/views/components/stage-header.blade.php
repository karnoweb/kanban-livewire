<header class="kb-stage-header" data-kb-part="stage-header">
    <h2 class="kb-stage-title" data-kb-part="stage-title">
        @if($column['color'])<span class="kb-accent" data-kb-part="stage-accent" style="background: {{ $column['color'] }}"></span>@endif
        <span class="kb-stage-title-text">{{ $column['title'] }}</span>
        @include('kanban::components.count-badge', ['column' => $column])
        @if($column['readOnly'] ?? false)
            <span class="kb-stage-lock" data-kb-part="stage-readonly" title="{{ __('kanban::kanban.read_only_stage') }}">{{ __('kanban::kanban.read_only_stage') }}</span>
        @endif
    </h2>
    <div class="kb-stage-controls">
        @if($features->createCard && ! ($column['readOnly'] ?? false) && ! ($column['disabled'] ?? false))
            <button type="button" class="kb-stage-quick" data-kb-part="stage-quick-create" wire:click="openCardForm(@js($column['id']))" aria-label="{{ __('kanban::kanban.create_card') }}">+</button>
        @endif
        @include('kanban::components.stage-actions', ['column' => $column])
    </div>
    @foreach($column['metrics'] ?? [] as $name => $value)
        <span class="kb-stage-metric" data-kb-part="stage-metric">{{ $name }}: {{ $value ?? '—' }}</span>
    @endforeach
</header>

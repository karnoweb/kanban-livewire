<div class="kb-virtual" data-overscan="4" data-rendered="{{ $column['renderedCount'] }}" data-loaded="{{ $column['loadedCount'] }}">
    @foreach(array_slice($column['cards'], 0, $column['renderedCount']) as $card)
        @include('kanban::components.card', ['card' => $card])
    @endforeach
</div>

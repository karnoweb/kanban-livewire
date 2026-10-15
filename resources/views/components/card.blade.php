<article
    class="kb-card"
    id="{{ app(\Karnoweb\Kanban\Support\DomIdFactory::class)->make($instanceId, $boardKey, 'card', $card['id']) }}"
    wire:key="card-{{ $instanceId }}-{{ $boardKey }}-{{ $card['id'] }}-{{ $card['stageId'] }}"
    draggable="{{ $reorderAllowed ? 'true' : 'false' }}"
    data-card="{{ $card['id'] }}"
    data-version="{{ $card['version'] ?? '' }}"
>
    <button type="button" class="kb-drag" aria-label="{{ __('kanban::kanban.move_to') }}" @if(! $reorderAllowed) disabled @endif>↕</button>
    <h3>{{ $card['title'] }}</h3>
    @if(!empty($card['description']))<p>{{ $card['description'] }}</p>@endif
    @include('kanban::components.card-badges', ['card' => $card])
    @include('kanban::components.card-assignees', ['card' => $card])
    @include('kanban::components.card-attachments', ['card' => $card])
    @if(!empty($card['dueAt']))<time datetime="{{ $card['dueAt'] }}">{{ $card['dueAt'] }}</time>@endif
    @include('kanban::components.card-actions', ['card' => $card])
</article>

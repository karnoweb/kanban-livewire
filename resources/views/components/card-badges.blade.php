@if(!empty($card['badges']))
<div class="kb-badges" data-kb-part="card-badges">
    @foreach(array_slice($card['badges'] ?? [], 0, 3) as $badge)
        <span
            class="badge {{ ['info' => 'badge-info', 'success' => 'badge-success', 'warning' => 'badge-warning', 'danger' => 'badge-error'][$badge['tone'] ?? 'neutral'] ?? 'badge-neutral' }} kb-badge kb-badge-{{ $badge['tone'] ?? 'neutral' }}"
            data-kb-part="card-badge"
            data-tone="{{ $badge['tone'] ?? 'neutral' }}"
            @if(!empty($badge['description'])) title="{{ $badge['description'] }}" @endif
        >{{ $badge['text'] ?? '' }}</span>
    @endforeach
    @if(count($card['badges'] ?? []) > 3)
        <span class="kb-badge" title="{{ collect(array_slice($card['badges'], 3))->pluck('text')->implode(', ') }}">+{{ count($card['badges']) - 3 }}</span>
    @endif
</div>
@endif

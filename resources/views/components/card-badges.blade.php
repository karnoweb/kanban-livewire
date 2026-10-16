<div class="kb-badges" data-kb-part="card-badges">
    @foreach(($card['badges'] ?? []) as $badge)
        <span
            class="badge {{ ['info' => 'badge-info', 'success' => 'badge-success', 'warning' => 'badge-warning', 'danger' => 'badge-error'][$badge['tone'] ?? 'neutral'] ?? 'badge-neutral' }} kb-badge kb-badge-{{ $badge['tone'] ?? 'neutral' }}"
            data-kb-part="card-badge"
            data-tone="{{ $badge['tone'] ?? 'neutral' }}"
            @if(!empty($badge['description'])) title="{{ $badge['description'] }}" @endif
        >{{ $badge['text'] ?? '' }}</span>
    @endforeach
</div>

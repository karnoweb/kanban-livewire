<div class="kb-badges">
    @foreach(($card['badges'] ?? []) as $badge)
        <span class="kb-badge kb-badge-{{ $badge['tone'] ?? 'neutral' }}">{{ $badge['text'] ?? '' }}</span>
    @endforeach
</div>

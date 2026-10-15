<section class="kb-archive">
    <h2>{{ __('kanban::kanban.archive_browser') }}</h2>
    @foreach($archiveItems as $card)
        <article wire:key="arch-{{ $instanceId }}-{{ $card['id'] }}">
            <span>{{ $card['title'] }}</span>
            <button type="button" wire:click="restoreCard('{{ $card['id'] }}', '{{ $card['stageId'] }}')">{{ __('kanban::kanban.restore') }}</button>
        </article>
    @endforeach
</section>

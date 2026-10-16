<section role="dialog" aria-modal="true" aria-label="{{ __('kanban::kanban.archive_browser') }}" wire:keydown.escape="$set('archiveOpen', false)" class="kb-archive" data-kb-part="archive-browser">
    <header class="kb-modal-header">
        <h2>{{ __('kanban::kanban.archive_browser') }}</h2>
        <button type="button" class="btn-ghost btn kb-btn kb-btn--ghost" wire:click="$set('archiveOpen', false)">{{ __('kanban::kanban.close') }}</button>
    </header>
    @foreach($archiveItems as $card)
        <article class="kb-archive-row" data-kb-part="archive-item" wire:key="arch-{{ $instanceId }}-{{ $card['id'] }}">
            <span class="kb-archive-title">{{ $card['title'] }}</span>
            <button type="button" class="btn kb-btn kb-btn--secondary" wire:click="restoreCard('{{ $card['id'] }}', '{{ $card['stageId'] }}')">{{ __('kanban::kanban.restore') }}</button>
        </article>
    @endforeach
</section>

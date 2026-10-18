<section role="dialog" aria-modal="true" aria-label="{{ __('kanban::kanban.archive_browser') }}" wire:keydown.escape="$set('archiveOpen', false)" class="kb-archive" data-kb-part="archive-browser">
    <header class="kb-modal-header">
        <h2>{{ __('kanban::kanban.archive_browser') }}</h2>
        @include('kanban::components.close-button', ['closeAction' => 'closeArchive'])
    </header>
    @forelse($archiveItems as $card)
        <article class="kb-archive-row" data-kb-part="archive-item" wire:key="arch-{{ $instanceId }}-{{ $card['id'] }}">
            <span class="kb-archive-title">{{ $card['title'] }}</span>
            <div class="kb-archive-actions">
            @if($features->restoreCard)
            <button type="button" class="btn kb-btn kb-btn--secondary" wire:click="restoreCard('{{ $card['id'] }}', '{{ $card['stageId'] }}')">{{ __('kanban::kanban.restore') }}</button>
            @endif
            @if($features->deleteCard && ($card['permissions']['delete'] ?? true))
                <button type="button" class="btn kb-btn kb-btn--danger" data-kb-part="archive-delete" wire:click="deleteCard(@js($card['id']))" wire:confirm="{{ __('kanban::kanban.delete_confirm') }}">{{ __('kanban::kanban.delete_card') }}</button>
            @endif
            </div>
        </article>
    @empty
        <p class="kb-empty">{{ __('kanban::kanban.empty_archive') }}</p>
    @endforelse
</section>

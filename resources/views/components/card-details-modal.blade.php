<div role="dialog" aria-modal="true" aria-label="{{ __('kanban::kanban.details') }}" wire:keydown.escape="closeDetails" class="kb-modal" data-kb-part="details-modal">
    <header class="kb-modal-header" data-kb-part="details-header">
        <div>
            @if(!empty($detail['metadata']['code']))
                <span class="kb-card-code" data-kb-part="card-code">{{ $detail['metadata']['code'] }}</span>
            @endif
            <h2 class="kb-modal-title" data-kb-part="details-title">{{ $detail['title'] ?? '' }}</h2>
        </div>
        <button type="button" class="btn-ghost btn kb-btn kb-btn--ghost" data-kb-part="details-close" wire:click="closeDetails">{{ __('kanban::kanban.close') }}</button>
    </header>
    @include('kanban::components.card-badges', ['card' => $detail])
    @if(!empty($detail['description']))
        <p class="kb-modal-body" data-kb-part="details-description">{{ $detail['description'] }}</p>
    @endif
    @include('kanban::components.card-attachments', ['card' => $detail])
    @include('kanban::components.card-assignees', ['card' => $detail])
    @php($url = $detail['metadata']['url'] ?? null)
    <footer class="kb-modal-footer" data-kb-part="details-footer">
        @if(is_string($url) && preg_match('#^(https?://|/)#i', $url) === 1)
            <a class="kb-link" data-kb-part="details-link" href="{{ $url }}">{{ __('kanban::kanban.open') }}</a>
        @endif
        @include('kanban::components.card-actions', ['card' => $detail])
    </footer>
</div>

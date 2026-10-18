@php
    $rawUrl = $detail['metadata']['url'] ?? null;
    $url = null;
    try {
        $url = is_string($rawUrl) ? app(\Karnoweb\Kanban\Support\UrlSanitizer::class)->sanitize($rawUrl) : null;
    } catch (\InvalidArgumentException) {
        $url = null;
    }
@endphp
<footer class="kb-modal-footer" data-kb-part="details-footer">
    @if($url !== null)
        <a class="kb-link" data-kb-part="details-link" href="{{ $url }}">{{ __('kanban::kanban.open') }}</a>
    @endif
    @include('kanban::components.card-actions', ['card' => $detail])
</footer>

@php
    $cover = collect($card['attachments'] ?? [])->first(static fn ($file) => is_array($file) && (str_starts_with(strtolower((string) ($file['mime'] ?? '')), 'image/') || preg_match('/\.(png|jpe?g|gif|webp|avif)$/i', (string) parse_url($file['url'] ?? '', PHP_URL_PATH)) || preg_match('/\.(png|jpe?g|gif|webp|avif)$/i', (string) ($file['name'] ?? ''))));
    $coverUrl = $cover ? app(\Karnoweb\Kanban\Support\UrlSanitizer::class)->sanitize(($cover['thumbnailUrl'] ?? null) ?: ($cover['url'] ?? null)) : null;
@endphp
@if($coverUrl)
    <img class="kb-card-cover" data-kb-part="card-cover" src="{{ $coverUrl }}" alt="{{ $cover['name'] ?? '' }}" loading="lazy" decoding="async" draggable="false">
@endif

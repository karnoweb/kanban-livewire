@if(!empty($card['attachments']))
<ul class="kb-attachments" data-kb-part="card-attachments">
    @foreach(($card['attachments'] ?? []) as $attachment)
        <li class="kb-attachment" data-kb-part="card-attachment">
            @if(!empty($attachment['thumbnailUrl']))
                <img class="kb-attachment-thumb" src="{{ $attachment['thumbnailUrl'] }}" alt="">
            @endif
            <a class="kb-attachment-link" href="{{ $attachment['url'] }}" rel="noopener">📎 {{ $attachment['name'] }}</a>
            @if(!empty($attachment['size']))
                <span class="kb-attachment-size">{{ number_format((int) $attachment['size'] / 1024, 0) }} KB</span>
            @endif
        </li>
    @endforeach
</ul>
@endif

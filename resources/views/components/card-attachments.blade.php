<ul class="kb-attachments">
    @foreach(($card['attachments'] ?? []) as $attachment)
        <li>
            @if(!empty($attachment['thumbnailUrl']))<img src="{{ $attachment['thumbnailUrl'] }}" alt="">@endif
            <a href="{{ $attachment['url'] }}" rel="noopener">{{ $attachment['name'] }}</a>
        </li>
    @endforeach
</ul>

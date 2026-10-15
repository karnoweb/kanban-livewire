<ul class="kb-assignees">
    @foreach(($card['assignees'] ?? []) as $assignee)
        <li>
            @if(!empty($assignee['avatarUrl']))
                <img src="{{ $assignee['avatarUrl'] }}" alt="{{ $assignee['name'] }}">
            @else
                <span aria-hidden="true">{{ mb_substr($assignee['name'], 0, 1) }}</span>
            @endif
            <span>{{ $assignee['name'] }}</span>
        </li>
    @endforeach
</ul>

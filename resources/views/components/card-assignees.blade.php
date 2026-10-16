<ul class="kb-assignees" data-kb-part="card-assignees">
    @foreach(($card['assignees'] ?? []) as $assignee)
        <li class="kb-assignee" data-kb-part="card-assignee" title="{{ $assignee['name'] }}{{ !empty($assignee['label']) ? ' · '.$assignee['label'] : '' }}">
            @if(!empty($assignee['avatarUrl']))
                <img class="kb-assignee-avatar" src="{{ $assignee['avatarUrl'] }}" alt="{{ $assignee['name'] }}">
            @else
                <span class="kb-assignee-initials" aria-hidden="true">{{ mb_strtoupper(mb_substr($assignee['name'], 0, 2)) }}</span>
            @endif
            <span class="kb-assignee-name">{{ $assignee['name'] }}</span>
        </li>
    @endforeach
</ul>

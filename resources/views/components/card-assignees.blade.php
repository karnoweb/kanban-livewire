@if(!empty($card['assignees']))
<ul class="kb-assignees" data-kb-part="card-assignees">
    @foreach(array_slice($card['assignees'], 0, 3) as $assignee)
        <li class="kb-assignee" data-kb-part="card-assignee" title="{{ $assignee['name'] }}{{ !empty($assignee['label']) ? ' · '.$assignee['label'] : '' }}">
            @if(!empty($assignee['avatarUrl']))
                <img class="kb-assignee-avatar" src="{{ $assignee['avatarUrl'] }}" alt="{{ $assignee['name'] }}">
            @else
                <span class="kb-assignee-initials" aria-hidden="true">{{ mb_strtoupper(mb_substr($assignee['name'], 0, 2)) }}</span>
            @endif
            <span class="kb-assignee-name">{{ $assignee['name'] }}</span>
        </li>
    @endforeach
    @if(count($card['assignees']) > 3)
        <li class="kb-assignee kb-assignee-more">
            <details class="kb-toolbar-options kb-assignee-overflow" data-kb-part="assignee-overflow" wire:ignore.self>
                <summary dir="ltr" aria-label="{{ __('kanban::kanban.more_assignees', ['count' => count($card['assignees']) - 3]) }}" title="{{ __('kanban::kanban.more_assignees', ['count' => count($card['assignees']) - 3]) }}">+{{ count($card['assignees']) - 3 }}</summary>
                <div class="kb-toolbar-options-body kb-people-popover" data-kb-part="assignee-overflow-panel">
                    @foreach(array_slice($card['assignees'], 3) as $person)
                        <div class="kb-person">
                            @if(!empty($person['avatarUrl']))<img class="kb-assignee-avatar" src="{{ $person['avatarUrl'] }}" alt="">
                            @else<span class="kb-assignee-initials" aria-hidden="true">{{ mb_strtoupper(mb_substr($person['name'], 0, 2)) }}</span>@endif
                            <span class="kb-person-info"><strong>{{ $person['name'] }}</strong>@if(!empty($person['label']))<small>{{ $person['label'] }}</small>@endif</span>
                        </div>
                    @endforeach
                </div>
            </details>
        </li>
    @endif
</ul>
@endif

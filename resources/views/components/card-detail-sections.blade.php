@foreach($sections as $section)
    <section class="kb-detail-section kb-detail-extension" data-kb-part="details-section" data-section="{{ $section['key'] }}">
        <h3>{{ $section['title'] }}</h3>
        @if(is_string($section['summary'] ?? null) && $section['summary'] !== '')
            <p class="kb-detail-hint">{{ $section['summary'] }}</p>
        @endif
        @if(!($section['loaded'] ?? false))
            @if(($section['state'] ?? 'unloaded') === 'unavailable')
                <p class="kb-detail-hint">{{ __('kanban::kanban.section_unavailable') }}</p>
            @else
                <button type="button" class="btn kb-btn kb-detail-load" wire:click="loadDetailSection(@js($section['key']))" wire:loading.attr="disabled" wire:target="loadDetailSection">
                    {{ __('kanban::kanban.load_details') }}
                </button>
            @endif
        @else
            @php($content = $section['content'] ?? [])
            @if(isset($detailSectionViews[$section['key']]))
                @include($detailSectionViews[$section['key']], ['section' => $section, 'content' => $content])
            @else
            @if(($content['state'] ?? '') === 'unavailable')
                <p class="kb-detail-hint">{{ __('kanban::kanban.section_unavailable') }}</p>
            @elseif(($content['items'] ?? []) === [])
                <p class="kb-detail-hint">{{ __('kanban::kanban.no_section_items') }}</p>
            @else
                <div class="kb-detail-extension-items">
                    @foreach($content['items'] as $item)
                        <article class="kb-detail-extension-item">
                            @if(!empty($item['url']))<a class="kb-link" href="{{ $item['url'] }}">{{ $item['label'] }}</a>@else<strong>{{ $item['label'] }}</strong>@endif
                            @if(!empty($item['thumbnailUrl']))<img class="kb-detail-thumbnail" loading="lazy" src="{{ $item['thumbnailUrl'] }}" alt="{{ $item['label'] }}">@endif
                            @if(is_string($item['status'] ?? null))<span class="kb-detail-extension-status">{{ $item['status'] }}</span>@endif
                            @if(is_string($item['value'] ?? null))<p>{{ $item['value'] }}</p>@endif
                            @if(is_string($item['description'] ?? null))<p class="kb-detail-hint">{{ $item['description'] }}</p>@endif
                        </article>
                    @endforeach
                </div>
            @endif
            @endif
            @foreach(($content['actions'] ?? $section['actions'] ?? []) as $action => $label)
                <button type="button" class="btn kb-btn" wire:click="performDetailAction(@js($section['key']), @js($action))" wire:loading.attr="disabled" wire:target="performDetailAction">
                    {{ $label }}
                </button>
            @endforeach
            @if(is_string($content['nextCursor'] ?? null) && $content['nextCursor'] !== '')
                <button type="button" class="btn kb-btn kb-detail-load" wire:click="loadDetailSection(@js($section['key']), @js($content['nextCursor']))" wire:loading.attr="disabled" wire:target="loadDetailSection">
                    {{ __('kanban::kanban.load_more_details') }}
                </button>
            @endif
        @endif
    </section>
@endforeach

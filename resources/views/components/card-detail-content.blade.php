@php
    $meta = is_array($detail['metadata'] ?? null) ? $detail['metadata'] : [];
    $stage = collect($columns)->firstWhere('id', $detail['stageId'] ?? '');
    $rawItems = $detail['checklist'] ?? [];
    $items = is_array($rawItems) ? array_values(array_filter($rawItems, 'is_array')) : [];
    $comments = is_array($detail['comments'] ?? null) ? array_values(array_filter($detail['comments'], 'is_array')) : [];
    $done = is_numeric($meta['checklistDone'] ?? null) ? (int) $meta['checklistDone'] : count(array_filter($items, static fn ($item) => !empty($item['done'])));
    $total = is_numeric($meta['checklistTotal'] ?? null) ? (int) $meta['checklistTotal'] : count($items);
    $commentsCount = is_numeric($meta['commentsCount'] ?? null) ? (int) $meta['commentsCount'] : count($comments);
@endphp
<div class="kb-detail-content" data-kb-part="details-content">
    @include('kanban::components.card-cover', ['card' => $detail])
    <dl class="kb-detail-properties">
        @if(!empty($meta['source']))<div><dt>{{ __('kanban::kanban.source') }}</dt><dd>{{ $meta['source'] }}</dd></div>@endif
        @if($stage)<div><dt>{{ __('kanban::kanban.status') }}</dt><dd>{{ $stage['title'] }}</dd></div>@endif
        @if(!empty($detail['priority']))<div><dt>{{ __('kanban::kanban.priority') }}</dt><dd>{{ \Illuminate\Support\Facades\Lang::has('kanban::kanban.priority_'.$detail['priority']) ? __('kanban::kanban.priority_'.$detail['priority']) : $detail['priority'] }}</dd></div>@endif
        @foreach(['dueAt' => 'due', 'createdAt' => 'created'] as $key => $label)
            @if(!empty($detail[$key]))<div><dt>{{ __('kanban::kanban.'.$label) }}</dt><dd><time dir="ltr" datetime="{{ $detail[$key] }}">{{ app(\Karnoweb\Kanban\Support\DateFormatter::class)->format($detail[$key], $key === 'createdAt', $dateSettings ?? null) }}</time></dd></div>@endif
        @endforeach
        @if(isset($meta['points']) && is_numeric($meta['points']))<div><dt>{{ __('kanban::kanban.points') }}</dt><dd>{{ $meta['points'] }} pt</dd></div>@endif
        @if(!empty($detail['assignees']))<div><dt>{{ __('kanban::kanban.assignees') }}</dt><dd>@include('kanban::components.card-assignees', ['card' => $detail])</dd></div>@endif
    </dl>
    @if(!empty($detail['description']))
        <section class="kb-detail-section"><h3>{{ __('kanban::kanban.description') }}</h3><p class="kb-modal-body" data-kb-part="details-description">{{ $detail['description'] }}</p></section>
    @endif
    @if(!empty($detail['badges']))<section class="kb-detail-section"><h3>{{ __('kanban::kanban.labels') }}</h3>@include('kanban::components.card-badges', ['card' => $detail])</section>@endif
    @if($total > 0 || $items !== [])
        <section class="kb-detail-section" data-kb-part="details-checklist">
            <h3>{{ __('kanban::kanban.checklist') }} <span>{{ $done }}/{{ $total }}</span></h3>
            @if($total > 0)<progress class="kb-detail-progress" value="{{ max(0, min($done, $total)) }}" max="{{ $total }}">{{ $done }}/{{ $total }}</progress>@endif
            @foreach($items as $item)
                @if(is_string($item['title'] ?? null))<div class="kb-detail-check"><span aria-label="{{ __(!empty($item['done']) ? 'kanban::kanban.completed' : 'kanban::kanban.incomplete') }}">{{ !empty($item['done']) ? '☑' : '☐' }}</span><span>{{ $item['title'] }}</span></div>@endif
            @endforeach
        </section>
    @endif
    @if(!empty($detail['attachments']))<section class="kb-detail-section" data-kb-part="details-attachments"><h3>{{ __('kanban::kanban.attachments') }} <span>{{ count($detail['attachments']) }}</span></h3>@include('kanban::components.card-attachments', ['card' => $detail])</section>@endif
    @if($commentsCount > 0 || $comments !== [])
        <section class="kb-detail-section" data-kb-part="details-comments">
            <h3>{{ __('kanban::kanban.comments') }} <span>{{ $commentsCount }}</span></h3>
            @foreach($comments as $comment)
                @if(is_string($comment['body'] ?? null))
                    <article class="kb-detail-comment">
                        <header>@if(is_string($comment['author'] ?? null))<strong>{{ $comment['author'] }}</strong>@endif @if(is_string($comment['createdAt'] ?? null))<time dir="ltr" datetime="{{ $comment['createdAt'] }}">{{ app(\Karnoweb\Kanban\Support\DateFormatter::class)->format($comment['createdAt'], true, $dateSettings ?? null) }}</time>@endif</header>
                        <p>{{ $comment['body'] }}</p>
                    </article>
                @endif
            @endforeach
            @if($comments === [])<p class="kb-detail-hint">{{ __('kanban::kanban.comments_summary_only') }}</p>@endif
        </section>
    @endif
    @include('kanban::components.card-detail-sections', ['sections' => $detailSections ?? []])
</div>

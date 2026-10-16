<span class="badge badge-ghost kb-count" data-kb-part="stage-count" data-exact="{{ ($column['countExact'] ?? false) ? 'true' : 'false' }}">
    {{ $column['totalCount'] === null ? __('kanban::kanban.count_unknown') : ($column['countExact'] ? __('kanban::kanban.count_loaded', ['loaded' => $column['loadedCount'], 'total' => $column['totalCount']]) : __('kanban::kanban.count_approx', ['count' => $column['totalCount']])) }}
    <span class="kb-rendered" data-rendered="{{ $column['renderedCount'] }}"></span>
</span>

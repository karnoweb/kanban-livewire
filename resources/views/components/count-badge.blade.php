<span class="kb-count" data-exact="{{ ($column['countExact'] ?? false) ? 'true' : 'false' }}">
    {{ __('kanban::kanban.count_loaded', ['loaded' => $column['loadedCount'], 'total' => ($column['totalCount'] === null ? __('kanban::kanban.count_unknown') : (($column['countExact'] ?? false) ? $column['totalCount'] : __('kanban::kanban.count_approx', ['count' => $column['totalCount']])))]) }}
    <span class="kb-rendered" data-rendered="{{ $column['renderedCount'] }}"></span>
</span>

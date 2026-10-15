<?php

declare(strict_types=1);

return [
    'page_size' => 25,
    'max_page_size' => 100,
    'default_view' => 'board',
    'allowed_views' => ['board', 'list'],
    'default_transition_policy' => 'deny',
    'features' => [
        'drag_drop' => true,
        'reorder_cards' => true,
        'reorder_stages' => true,
        'create_card' => true,
        'update_card' => true,
        'create_stage' => true,
        'update_stage' => true,
        'archive_card' => true,
        'archive_stage' => true,
        'restore_card' => true,
        'search' => true,
        'filters' => true,
        'bulk' => true,
        'wip' => true,
        'details' => true,
        'virtualization' => true,
        'realtime' => false,
        'custom_actions' => true,
    ],
    'appearance' => [
        'theme' => 'system',
        'direction' => 'ltr',
        'density' => 'comfortable',
        'column_width' => '18rem',
        'detail_surface' => 'modal',
    ],
    'interaction' => [
        'animations' => true,
        'reduced_motion' => false,
        'keyboard_alternative' => true,
        'confirmation' => 'none',
    ],
    'realtime' => [
        'enabled' => false,
        'refresh' => 'invalidate',
        'reconnect' => true,
        'dedupe_limit' => 200,
        'coalesce_ms' => 50,
    ],
    'security' => [
        'allowed_url_schemes' => ['http', 'https'],
        'metadata_bytes' => 8192,
        'search_length' => 200,
        'bulk_ids' => 100,
        'rate_limit_per_minute' => null,
    ],
    'assets' => [
        'publish_tag' => 'kanban-assets',
        'prefixed_path' => 'vendor/kanban',
    ],
    'telemetry' => [
        'enabled' => false,
    ],
    'limits' => [
        'search_length' => 200,
        'bulk_ids' => 100,
        'metadata_bytes' => 8192,
        'id_length' => 128,
        'title_length' => 300,
    ],
    'sort_fields' => ['manualOrder', 'dueAt', 'createdAt', 'title', 'priority'],
    'filter_fields' => ['assignee', 'priority', 'stage', 'dueFrom', 'dueTo', 'createdFrom', 'createdTo', 'badge', 'metadata'],
];

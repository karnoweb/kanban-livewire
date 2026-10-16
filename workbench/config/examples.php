<?php

declare(strict_types=1);

return [
    'tasks.board' => [
        'slug' => 'tasks',
        'title' => 'Tasks',
        'tag' => 'Task manager',
        'summary' => 'انتقال آزاد، WIP روی Doing، لیست/برد، فیلتر، بایگانی گروهی و ایجاد کارت/ستون.',
        'doc' => 'docs/examples/task-manager.md',
    ],
    'crm.deals' => [
        'slug' => 'crm',
        'title' => 'CRM Deals',
        'tag' => 'CRM',
        'summary' => 'چهار مرحله؛ انتقال به «بسته» فقط از مذاکره و با یادداشت اجباری.',
        'doc' => 'docs/examples/crm.md',
    ],
    'tickets.board' => [
        'slug' => 'tickets',
        'title' => 'Support Tickets',
        'tag' => 'Ticketing',
        'summary' => 'مسیر تیکت محدود؛ ستون Closed فقط‌خواندنی؛ Realtime خصوصی (Reverb).',
        'doc' => 'docs/examples/ticketing.md',
    ],
    'orders.board' => [
        'slug' => 'orders',
        'title' => 'Orders',
        'tag' => 'Orders & approvals',
        'summary' => 'تأیید مالی فقط وقتی handler میزبان reviewApproved را در metadata کارت ببیند.',
        'doc' => 'docs/examples/orders-approvals.md',
    ],
];

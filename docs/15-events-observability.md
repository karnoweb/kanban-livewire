# رویدادهای Livewire و پایش

## رویدادهای مرورگر (پس از پاسخ سرور)

از `@script` / dispatch در کامپوننت `Board` — برای integration میزبان:

| رویداد | زمان تقریبی |
| --- | --- |
| `kanban:ready` | بارگذاری اولیه |
| `kanban:move-requested` | قبل از تأیید |
| `kanban:move-confirmed` | ارسال به سرور |
| `kanban:move-rejected` | policy / transition |
| `kanban:card-created` | create موفق |
| `kanban:stage-created` | stage موفق |
| `kanban:bulk-completed` | پایان bulk |
| `kanban:conflict` | version |
| `kanban:realtime-status-changed` | اتصال realtime |

**گوش دادن در Blade:**

```blade
<div
    x-on:kanban:move-rejected.window="$dispatch('toast', { message: 'انتقال مجاز نیست' })"
>
    <livewire:kanban-board board-key="crm.deals" />
</div>
```

(نیاز به Alpine در layout — همان stack Livewire 4.)

## Telemetry

`config/kanban.php`:

```php
'telemetry' => ['enabled' => false],
```

وقتی فعال باشد، `TelemetryDispatcher` عنوان، یادداشت، URL و metadata خام را از audit حذف می‌کند.

## Audit

قرارداد `AuditSink` — پیش‌فرض `NullAuditSink`. برای لاگ سازمانی bind کنید:

```php
$this->app->singleton(\Karnoweb\Kanban\Contracts\AuditSink::class, \App\Kanban\KanbanAuditSink::class);
```

## Monitor

`Monitor` — پیش‌فرض `NullMonitor`؛ برای متریک داخلی.

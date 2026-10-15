# شروع سریع

میزبان باید `ScopeResolver` را bind کند و یک factory ثبت کند.

```php
$registry->register('tasks.board', \App\Kanban\TaskBoardFactory::class);
```

```blade
<livewire:kanban-board board-key="tasks.board" />
```

Factory در هر درخواست `BoardDefinition` می‌سازد. داده کوچک با `stages()` و `cards()` به `InlineDataProvider` می‌رود. داده بزرگ `provider()` صفحه‌ای می‌خواهد.
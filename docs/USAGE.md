# نقشه استفاده از پکیج

## مسئولیت

| لایه | مالک |
| --- | --- |
| UI برد/list، Livewire، قراردادها | پکیج |
| رکوردها، authorization، تراکنش، قوانین دامنه | اپ میزبان |
| migration مدل کارت | میزبان (اختیاری) |

## مسیر پیاده‌سازی (ترتیب پیشنهادی)

| مرحله | مستند |
| --- | --- |
| 1. نصب CSS/JS | [02-installation-assets.md](02-installation-assets.md) |
| 2. ScopeResolver + اولین برد | [01-quickstart.md](01-quickstart.md) |
| 3. Registry و Builder | [03-builder-registry.md](03-builder-registry.md) |
| 4. Settings | [04-dtos-settings.md](04-dtos-settings.md) |
| 5. خواندن | [05-data-provider.md](05-data-provider.md) |
| 6. نوشتن | [06-handler-actions.md](06-handler-actions.md) |
| 7. انتقال/WIP | [08-move-confirm-wip.md](08-move-confirm-wip.md) |
| 8. سناریوهای CRM/Task/Ticket/Order | [examples/README.md](examples/README.md) |

## Eloquent (میان‌بر)

```php
return (new BoardBuilder)
    ->key('support.tickets')
    ->board(new BoardData('support.tickets', 'Tickets', $context->scopeKey))
    ->sources(['tickets'])
    ->settings(['pagination' => ['page_size' => 25]])
    ->build();
```

قبل از آن: `KanbanModelRegistry::register('tickets', Ticket::class)` و `HasKanbanCards` — [21-trait-eloquent-guide.md](21-trait-eloquent-guide.md).

## Blade

```blade
<livewire:kanban-board board-key="support.tickets" />
```

## قوانین دامنه

- خواندن: `visibleQuery()` / provider scoped
- نوشتن: `ActionHandler` یا hookهای مدل — نه update مستقیم status از UI bypass
- سرویس موجود: داخل handler صدا بزنید؛ `ActionResult` برگردانید

## جزئیات کارت

```php
->detailsProvider(HostCardDetailsProvider::class)
```

[DRAWER.md](DRAWER.md)

## تست

```bash
composer test
npm run build   # در مخزن پکیج برای assets
```

[16-testing-scenarios.md](16-testing-scenarios.md)

## مرجع API

[20-api-reference.md](20-api-reference.md)

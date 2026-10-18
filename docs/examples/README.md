# نمونه‌های پیاده‌سازی (گام‌به‌گام)

این پوشه برای **توسعه‌دهنده میزبان** است: هر فایل یک سناریوی واقعی را از صفر تا کد نهایی توضیح می‌دهد. منبع حقیقت کد اجرایی در مخزن پکیج است:

| سناریو | کلید برد | مسیر دمو | فایل راهنما |
| --- | --- | --- | --- |
| CRM — مراحل ثابت، انتقال محدود، یادداشت اجباری | `crm.deals` | `/crm` | [crm.md](crm.md) |
| Task — برد انعطاف‌پذیر، WIP، ایجاد/بایگانی | `tasks.board` | `/tasks` | [task-manager.md](task-manager.md) |
| Ticket — ماتریس انتقال، ستون بسته فقط‌خواندنی | `tickets.board` | `/tickets` | [ticketing.md](ticketing.md) |
| Order — گیت دامنه قبل از تأیید مالی | `orders.board` | `/orders` | [orders-approvals.md](orders-approvals.md) |

## پیش‌نیاز

1. نصب پکیج و دارایی‌ها: [02-installation-assets.md](../02-installation-assets.md)
2. `ScopeResolver` میزبان (مثلاً `tenant_id` کاربر): [01-quickstart.md](../01-quickstart.md)
3. ثبت factory در `BoardRegistry` داخل Service Provider

## الگوی مشترک همه سناریوها

```text
Route (/crm, /tasks, …)
  → <livewire:kanban-board board-key="…" />
    → BoardRegistry → *BoardFactory::make()
      → BoardDefinition (settings + provider + handler)
        → DataProvider (خواندن ستون/کارت)
        → ActionHandler (نوشتن + قوانین دامنه)
```

**قانون طلایی:** هر محدودیتی که در UI با `interaction.transitions` یا `features` می‌بینید، باید **دوباره در handler** اعمال شود. UI جلوگیری می‌کند؛ handler تضمین می‌کند.

## اجرای دمو در workbench

```bash
composer test -- --filter=ExamplePagesTest
```

یا سرور workbench (طبق [README_Test.md](../../README_Test.md)) و باز کردن `/login-as/alice` سپس `/crm`.

کدهای نمونه در پوشه `workbench/app/Kanban/` و seed مراحل در `workbench/database/seeders/KanbanDemoSeeder.php` هستند. در اپلیکیشن واقعی همان کلاس‌ها را در `app/Kanban/` کپی و namespace را به `App\Kanban` تغییر دهید.

## قراردادهای مهم

| تنظیم | معنی |
| --- | --- |
| `interaction.transition_policy` = `deny` | فقط جفت‌های لیست `transitions` مجازند |
| `interaction.transition_policy` = `allow` | هر انتقال بین ستون‌ها (مگر محدودیت handler/WIP) |
| `confirmation_rules` | کلید `from>to` — فرم تأیید قبل از ارسال `MoveCard` |
| `features.create_card` / `create_stage` | دکمه UI؛ handler باید `CreateCard`/`CreateStage` را رد کند |
| `StageData::readOnly` | ستون مقصد/مبدأ drag نامعتبر (مثلاً Closed) |

جزئیات: [08-move-confirm-wip.md](../08-move-confirm-wip.md)، [24-workflow-enum-ranking.md](../24-workflow-enum-ranking.md).

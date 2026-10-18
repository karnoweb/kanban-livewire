# Ticketing — ماتریس انتقال، ستون Closed فقط‌خواندنی، Realtime

## سناریو کسب‌وکار

پشتیبانی تیکت‌ها را روی Kanban می‌بیند:

- ستون‌ها ثابت: Open → Answering → Waiting on customer → Closed.
- **ستون جدید از UI ساخته نمی‌شود** (`create_stage` = false).
- انتقال **فقط** طبق ماتریس (نه هر جهشی):
  - `open` → `answering`
  - `answering` → `waiting` یا `closed`
- ستون **Closed** فقط‌خواندنی است: کارت جدید در آن ساخته نمی‌شود و drop به آن از UI مسدود است.
- چند tenant: کاربر tenant دیگر تیکت را در query نمی‌بیند (`scope_key` از `ScopeResolver`).
- Realtime برای به‌روزرسانی هم‌زمان فعال است (Reverb/Echo).

یادداشت اجباری برای هر move در این نمونه **نیست** (برخلاف CRM); می‌توانید با `confirmation_rules` اضافه کنید.

## نقشه انتقال

```text
open ──► answering ──► waiting
              │
              └──► closed (readOnly)
```

## گام ۱ — Seed

```php
'tickets.board' => [
    'stages' => [
        'open' => ['title' => 'Open', 'color' => '#ef4444'],
        'answering' => ['title' => 'Answering', 'color' => '#3b82f6'],
        'waiting' => ['title' => 'Waiting on customer', 'color' => '#f59e0b'],
        'closed' => ['title' => 'Closed', 'color' => '#64748b'],
    ],
],
```

## گام ۲ — Factory (کد نهایی)

```php
<?php

declare(strict_types=1);

namespace App\Kanban;

final class TicketBoardFactory extends WorkbenchBoardFactory
{
    protected function key(): string
    {
        return 'tickets.board';
    }

    protected function title(): string
    {
        return 'Tickets';
    }

    protected function provider(): string
    {
        return TicketProvider::class;
    }

    protected function handler(): string
    {
        return TicketHandler::class;
    }

    protected function settings(): array
    {
        return [
            'interaction' => [
                'transition_policy' => 'deny',
                'transitions' => [
                    'open' => ['answering'],
                    'answering' => ['waiting', 'closed'],
                ],
            ],
            'features' => [
                'create_stage' => false,
                'bulk' => false,
                'realtime' => true,
            ],
            'realtime' => ['enabled' => true],
        ];
    }
}
```

## گام ۳ — Provider: `readOnly` روی Closed

UI و سرور (در adapter Eloquent) از `StageData::readOnly` استفاده می‌کنند:

```php
<?php

declare(strict_types=1);

namespace App\Kanban;

use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardQuery;
use Karnoweb\Kanban\Data\StageData;
use Karnoweb\Kanban\Enums\WipMode;
use Workbench\App\Models\DemoStage;

final class TicketProvider extends WorkbenchReader
{
    public function stages(BoardContext $context, BoardQuery $query): array
    {
        return DemoStage::query()
            ->where('scope_key', $context->scopeKey)
            ->where('board_key', $context->boardKey)
            ->where('archived', false)
            ->orderBy('sort_order')
            ->get()
            ->map(function (DemoStage $stage): StageData {
                $id = (string) $stage->public_id;

                return new StageData(
                    $id,
                    (string) $stage->board_key,
                    (string) $stage->title,
                    (int) $stage->sort_order,
                    readOnly: $id === 'closed',
                    wipLimit: $stage->wip_limit === null ? null : (int) $stage->wip_limit,
                    wipMode: WipMode::tryFrom((string) $stage->wip_mode) ?? WipMode::Off,
                    color: $stage->color,
                );
            })
            ->all();
    }
}
```

`TicketHandler` = `WorkbenchWriter` بدون override — ماتریس انتقال را UI اعمال می‌کند؛ برای اطمینان production همان ماتریس را در `move()` handler تکرار کنید (الگوی CRM).

## گام ۴ — ScopeResolver و چند tenant

```php
return new BoardContext(
    $boardKey,
    (string) $user->tenant_id,  // scope_key
    (string) $user->getAuthIdentifier(),
    $instanceId ?: 'default',
);
```

Reader همیشه `where('scope_key', $context->scopeKey)` می‌زند — کاربر `tenant-b` کارت‌های `tenant-a` را نمی‌بیند.

تست: `/login-as/alice` (tenant-a) و `/login-as/carol` (tenant-b).

## گام ۵ — Realtime

1. در factory: `'realtime' => ['enabled' => true]` و `'features' => ['realtime' => true]`.
2. در Service Provider میزبان:

```php
Broadcast::channel('kanban.{scopeHash}.{board}', fn ($user, $scopeHash, $board) =>
    app(ChannelAuthorizer::class)->allows($scopeHash, $board)
);

Event::listen(KanbanModelChanged::class, function ($change) use ($definitionResolver) {
    // اگر برد realtime دارد → RealtimePublisher::publish(...)
});
```

نمونه کامل: `workbench/app/Providers/KanbanBoardServiceProvider.php`.

جزئیات: [12-realtime.md](../12-realtime.md).

## گام ۶ — Route

```php
Route::get('/tickets', fn () => view('kanban.board', ['key' => 'tickets.board']));
```

## گام ۷ — تست رفتار

| اقدام | نتیجه |
| --- | --- |
| Drag به `closed` از `open` (بدون گذر از answering) | `transition_denied` |
| Drop روی ستون Closed برای کارت جدید | ستون readOnly — دکمه + در header قفل |
| کاربر tenant دیگر | لیست خالی یا فقط کارت همان tenant |

## افزودن یادداشت اجباری (مثل CRM)

به `interaction` اضافه کنید:

```php
'confirmation_rules' => [
    'answering>closed' => [
        'mode' => 'form',
        'fields' => ['note' => ['required' => true]],
    ],
],
```

و در `TicketHandler::move` مقدار `formData.note` را validate و در جدول comment تیکت ذخیره کنید.

## Drawer / SLA (گام بعدی)

برای SLA، کامنت صفحه‌بندی‌شده و پیوست، `CardDetailsProvider` میزبان را به factory وصل کنید — [DRAWER.md](../DRAWER.md).

## کد نهایی workbench

| فایل |
| --- |
| `workbench/app/Kanban/TicketBoardFactory.php` |
| `workbench/app/Kanban/TicketProvider.php` |
| `workbench/app/Kanban/TicketHandler.php` |
| `workbench/app/Providers/KanbanBoardServiceProvider.php` (broadcast + event) |

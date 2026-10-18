# Task Manager — برد انعطاف‌پذیر، WIP، لیست و بایگانی گروهی

## سناریو کسب‌وکار

تیم محصول یک برد وظیفه می‌خواهد که:

- ستون‌ها **ثابت پیش‌فرض** هستند (`todo`, `doing`, `done`) اما **تیم لید** می‌تواند ستون اضافه کند (`create_stage` = true).
- اعضا کارت جدید در هر ستون بسازند.
- روی ستون **Doing** حداکثر **۳** کارت هم‌زمان (WIP strict).
- بین ستون‌ها **آزاد** جابه‌جا شوند (`transition_policy` = `allow`).
- فیلتر مسئول، جستجو، نمای board/list، بایگانی تکی و **بایگانی گروهی** فعال باشد.

این سناریو عمداً مخالف CRM است: قوانین سخت در handler کمترند؛ WIP و مجوزها اصلی‌ترین محدودیت‌اند.

## نقشه مراحل

| `public_id` | WIP |
| --- | --- |
| `todo` | — |
| `doing` | 3 (strict) |
| `done` | — |

Seed: `KanbanDemoSeeder::BOARDS['tasks.board']`.

## گام ۱ — Seed ستون‌ها با WIP

```php
'tasks.board' => [
    'stages' => [
        'todo' => ['title' => 'To do', 'color' => '#6366f1'],
        'doing' => ['title' => 'Doing', 'color' => '#0ea5e9'],
        'done' => ['title' => 'Done', 'color' => '#10b981'],
    ],
    'wip' => ['doing' => 3],
],
```

در insert ستون: `wip_limit` = 3 و `wip_mode` = `strict` فقط برای `doing`.

## گام ۲ — ثبت برد

```php
$registry->register('tasks.board', \App\Kanban\TaskBoardFactory::class);
```

## گام ۳ — Factory (کد نهایی)

```php
<?php

declare(strict_types=1);

namespace App\Kanban;

final class TaskBoardFactory extends WorkbenchBoardFactory
{
    protected function key(): string
    {
        return 'tasks.board';
    }

    protected function title(): string
    {
        return 'Tasks';
    }

    protected function provider(): string
    {
        return TaskProvider::class;
    }

    protected function handler(): string
    {
        return TaskHandler::class;
    }

    protected function settings(): array
    {
        return [
            'interaction' => ['transition_policy' => 'allow'],
            'features' => [
                'wip' => true,
                'bulk' => true,
                'create_card' => true,
                'create_stage' => true,
                'filters' => true,
                'search' => true,
                'archive_card' => true,
                'reorder_cards' => true,
                'realtime' => false,
            ],
            'views' => ['allowed' => ['board', 'list']],
        ];
    }
}
```

`TaskProvider` / `TaskHandler` در workbench فقط `extends WorkbenchReader` و `extends WorkbenchWriter` هستند — تمام CRUD، WIP و bulk در writer مشترک پیاده شده است.

## گام ۴ — رفتار handler (نیازی به override نیست)

| عملیات | رفتار در `WorkbenchWriter` |
| --- | --- |
| `MoveCard` / `ReorderCard` | بررسی `expectedVersion`، WIP مقصد، به‌روزرسانی `manual_order` |
| `CreateCard` | عنوان اجباری؛ WIP مقصد |
| `CreateStage` | عنوان اجباری؛ `public_id` تصادفی |
| `ExecuteBulkAction` | بایگانی گروهی کارت‌ها |
| WIP full | `ActionStatus::WIP_LIMIT` |

WIP در UI نشان داده می‌شود؛ **رد نهایی** داخل تراکنش handler است ([08-move-confirm-wip.md](../08-move-confirm-wip.md)).

## گام ۵ — Provider: فیلتر مسئول

`WorkbenchReader::filterOptions` و `base()` فیلتر `assignee` را از `metadata.assignees` JSON می‌خوانند. هنگام ساخت کارت، assignee را در metadata قرار دهید:

```php
'metadata' => [
    'assignees' => [
        ['id' => 'u-alice', 'name' => 'Alice Karimi', 'label' => 'Tech Lead'],
    ],
],
```

## گام ۶ — Route

```php
Route::get('/tasks', fn () => view('kanban.board', ['key' => 'tasks.board']));
```

پیش‌فرض workbench: `Route::redirect('/', '/tasks');`

## گام ۷ — چک‌لیست تست دستی

1. چهارمین کارت را به Doing بکشید → پیام WIP.
2. از لیست view، فیلتر assignee را عوض کنید → فقط کارت‌های همان شخص.
3. چند کارت انتخاب و bulk archive → شمارنده ستون‌ها به‌روز شود.
4. ستون جدید از هدر ستون → در DB `demo_stages` ردیف جدید.

## محدود کردن ایجاد ستون (مثلاً فقط لید)

همان الگوی CRM:

```php
'create_stage' => auth()->user()?->can('manageTaskBoard') ?? false,
```

و در handler:

```php
if ($command->type === ActionType::CreateStage && ! Gate::allows('manageTaskBoard')) {
    return new ActionResult(ActionStatus::FORBIDDEN, messageCode: 'feature_disabled');
}
```

## کد نهایی workbench

| فایل |
| --- |
| `workbench/app/Kanban/TaskBoardFactory.php` |
| `workbench/app/Kanban/TaskProvider.php` |
| `workbench/app/Kanban/TaskHandler.php` |
| `workbench/app/Kanban/WorkbenchReader.php` |
| `workbench/app/Kanban/WorkbenchWriter.php` |

## مدل Eloquent

برای `KanbanTask` و trait: مسیر `/models/tasks` و `EloquentBoardFactory` — [21-trait-eloquent-guide.md](../21-trait-eloquent-guide.md).

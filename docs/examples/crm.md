# CRM — مراحل ثابت، انتقال خطی، یادداشت اجباری

## سناریو کسب‌وکار

برد فروش CRM چهار مرحله **ثابت** دارد: Lead → Discovery → Proposal → Won. کارشناس فروش:

- نمی‌تواند ستون یا معامله را از UI اضافه/حذف کند.
- فقط **یک قدم به جلو** در خط لوله جابه‌جا می‌کند (`new → progress → negotiation → closed`).
- برای **هر** جابه‌جایی بین ستون‌ها باید «یادداشت انجام کار» (transition note) ثبت کند.
- پرش از Lead مستقیم به Won یا برگشت از Won ممنوع است.

**مدیر بخش** (در نسخه کامل اپ شما): تنها کسی که می‌تواند ستون جدید یا معامله دستی ایجاد کند — در workbench این نقش شبیه‌سازی نشده؛ [گام ۷](#گام-۷-اختیاری-فقط-مدیر-بخش-ایجاد-کارتستون) الگوی آن را می‌آورد.

## نقشه مراحل

| `public_id` ستون | عنوان نمونه |
| --- | --- |
| `new` | جدید · Lead |
| `progress` | پیگیری · Discovery |
| `negotiation` | مذاکره · Proposal |
| `closed` | بسته‌شده · Won |

## گام ۱ — داده: ستون‌های ثابت در دیتابیس

ستون‌ها را با migration/seed با **شناسه ثابت** (`public_id`) بسازید؛ UI نباید ستون جدید بسازد.

```php
// database/seeders/CrmPipelineSeeder.php (نمونه — معادل KanbanDemoSeeder برای crm.deals)
foreach ([
    'new' => ['title' => 'جدید · Lead', 'color' => '#3b82f6'],
    'progress' => ['title' => 'پیگیری · Discovery', 'color' => '#8b5cf6'],
    'negotiation' => ['title' => 'مذاکره · Proposal', 'color' => '#f59e0b'],
    'closed' => ['title' => 'بسته‌شده · Won', 'color' => '#10b981'],
] as $id => $def) {
    DemoStage::query()->updateOrCreate(
        ['board_key' => 'crm.deals', 'scope_key' => $tenantId, 'public_id' => $id],
        ['title' => $def['title'], 'sort_order' => $order++, 'color' => $def['color']],
    );
}
```

معاملات (کارت‌ها) معمولاً از API/فرم CRM ساخته می‌شوند، نه از Kanban.

## گام ۲ — ثبت برد

```php
// app/Providers/KanbanServiceProvider.php
$registry->register('crm.deals', \App\Kanban\CrmBoardFactory::class);
```

## گام ۳ — Factory: UI را قفل کنید

سه لایه هم‌زمان:

1. `features.create_card` و `create_stage` = `false` — دکمه‌ها نمایش داده نمی‌شوند.
2. `transition_policy` = `deny` + ماتریس `transitions` — drag فقط به ستون مجاز بعدی.
3. `confirmation_rules` — قبل از ارسال move، فرم با فیلد `note` اجباری.

```php
<?php

declare(strict_types=1);

namespace App\Kanban;

final class CrmBoardFactory extends WorkbenchBoardFactory
{
    protected function key(): string
    {
        return 'crm.deals';
    }

    protected function title(): string
    {
        return 'CRM';
    }

    protected function provider(): string
    {
        return CrmProvider::class;
    }

    protected function handler(): string
    {
        return CrmHandler::class;
    }

    public function resolvedSettings(): array
    {
        $settings = parent::resolvedSettings();
        $settings['features']['create_card'] = false;
        $settings['features']['create_stage'] = false;
        $settings['features']['bulk'] = false;
        $settings['interaction'] = $this->settings()['interaction'];

        return $settings;
    }

    protected function settings(): array
    {
        return [
            'interaction' => [
                'transition_policy' => 'deny',
                'transitions' => [
                    'new' => ['progress'],
                    'progress' => ['negotiation'],
                    'negotiation' => ['closed'],
                ],
                'confirmation_rules' => array_fill_keys(
                    ['new>progress', 'progress>negotiation', 'negotiation>closed'],
                    ['mode' => 'form', 'fields' => ['note' => ['required' => true]]],
                ),
            ],
            'features' => [
                'create_card' => false,
                'create_stage' => false,
                'bulk' => false,
                'filters' => true,
                'realtime' => false,
            ],
        ];
    }
}
```

`CrmProvider` و `CrmHandler` در workbench خالی‌اند و به `WorkbenchReader` / `WorkbenchWriter` وصل می‌شوند؛ در اپ واقعی می‌توانید همان reader/writer مشترک تیم را reuse کنید.

## گام ۴ — Handler: اعتبارسنجی سمت سرور (الزامی)

بدون این بخش، درخواست دستی API/Livewire می‌تواند قوانین UI را دور بزند.

**الف) مسدود کردن ایجاد کارت/ستون** (حتی اگر کسی `features` را جعل کند):

```php
// داخل ActionHandler::execute، قبل از match نوع عملیات:
if ($context->boardKey === 'crm.deals' && in_array($command->type, [ActionType::CreateCard, ActionType::CreateStage], true)) {
    return new ActionResult(ActionStatus::VALIDATION_ERROR, messageCode: 'validation_failed');
}
```

**ب) اعتبار انتقال + یادداشت** (در متد `move`):

```php
if ($context->boardKey === 'crm.deals' && $from !== $to) {
    $allowed = [
        'new' => 'progress',
        'progress' => 'negotiation',
        'negotiation' => 'closed',
    ];
    $transitionNote = $command->payload['formData']['note'] ?? null;
    if (($allowed[$from] ?? null) !== $to
        || ! is_string($transitionNote)
        || trim($transitionNote) === ''
        || strlen($transitionNote) > 5000
    ) {
        return new ActionResult(ActionStatus::VALIDATION_ERROR, messageCode: 'validation_failed');
    }
}
```

پس از تأیید، یادداشت را در metadata/جدول audit ذخیره کنید (workbench آن را به `comments` کارت append می‌کند).

پیاده‌سازی کامل این منطق در workbench: `workbench/app/Kanban/WorkbenchWriter.php` (متدهای `execute` و `move`).

## گام ۵ — Route و Blade

```php
Route::get('/crm', fn () => view('kanban.board', ['key' => 'crm.deals']));
```

```blade
{{-- resources/views/kanban/board.blade.php --}}
<livewire:kanban-board :board-key="$key" />
```

## گام ۶ — تست رفتار

| اقدام | نتیجه مورد انتظار |
| --- | --- |
| Drag کارت از `new` به `closed` | رویداد `kanban:move-rejected`، کد `transition_denied` |
| Move مجاز بدون پر کردن note در فرم | `validation_failed` از handler |
| Move مجاز با note | `stage_id` عوض می‌شود، `version` +۱ |
| `CreateCard` از API | `validation_failed` |

تست خودکار: `tests/Workbench/ExamplePagesTest.php::test_disallowed_transition_emits_rejected_event_and_renders_notice_banner`.

## گام ۷ (اختیاری) — فقط مدیر بخش: ایجاد کارت/ستون

UI را بر اساس نقش در **همان درخواست** factory باز کنید؛ handler همچنان باید `Gate` را چک کند.

```php
protected function settings(): array
{
    $isManager = auth()->user()?->can('manageCrmPipeline') ?? false;

    return [
        'interaction' => [ /* همان ماتریس CRM */ ],
        'features' => [
            'create_card' => $isManager,
            'create_stage' => $isManager,
            'bulk' => false,
            // ...
        ],
    ];
}
```

```php
// CrmHandler::execute
if ($context->boardKey === 'crm.deals' && in_array($command->type, [ActionType::CreateCard, ActionType::CreateStage], true)) {
    if (! auth()->user()?->can('manageCrmPipeline')) {
        return new ActionResult(ActionStatus::FORBIDDEN, messageCode: 'feature_disabled');
    }
}
```

## کد نهایی (فایل‌های workbench)

| فایل | مسئولیت |
| --- | --- |
| `workbench/app/Kanban/CrmBoardFactory.php` | تنظیمات interaction + features |
| `workbench/app/Kanban/CrmProvider.php` | `extends WorkbenchReader` |
| `workbench/app/Kanban/CrmHandler.php` | `extends WorkbenchWriter` (+ override مدیر در اپ شما) |
| `workbench/app/Kanban/WorkbenchWriter.php` | قوانین CRM در `execute`/`move` |
| `workbench/database/seeders/KanbanDemoSeeder.php` | ثابت `BOARDS['crm.deals']` |

## ارتباط با مدل Eloquent

اگر به‌جای `DemoCard` از `Opportunity` با `HasKanbanCards` استفاده می‌کنید، ماتریس انتقال را در definition مدل هم منعکس کنید (`->transitions([...])`) و در hook `before` move سرویس CRM را صدا بزنید. مرجع: [21-trait-eloquent-guide.md](../21-trait-eloquent-guide.md) و [EXAMPLES-CRM-ORDERS-TICKETS.md](../EXAMPLES-CRM-ORDERS-TICKETS.md).

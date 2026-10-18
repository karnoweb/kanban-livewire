# Orders — گردش تأیید، گیت دامنه در Handler

## سناریو کسب‌وکار

برد تحویل سفارش سه مرحله دارد:

1. **Review** — بررسی عملیاتی
2. **Finance Approval** — تأیید مالی (فقط سفارش‌هایی که review در سیستم ثبت شده)
3. **Shipped** — ارسال شده

قوانین:

- ستون ثابت؛ `create_stage` = false.
- انتقال UI محدود: `review → finance → shipped` (`transition_policy` = `deny`).
- حتی اگر UI اجازه دهد، **ورود به Finance** بدون `reviewApproved` در دامنه سفارش **رد** می‌شود (`DOMAIN_REJECTED`).
- پکیج سند حسابداری یا پرداخت نمی‌سازد — فقط handler میزبان سرویس مالی را صدا می‌زند.

## نقشه

```text
review ──► finance ──► shipped
           ▲
           └── نیاز به metadata.reviewApproved === true
```

## گام ۱ — Seed و داده نمونه

```php
'orders.board' => [
    'stages' => [
        'review' => ['title' => 'Review', 'color' => '#f59e0b'],
        'finance' => ['title' => 'Finance Approval', 'color' => '#8b5cf6'],
        'shipped' => ['title' => 'Shipped', 'color' => '#10b981'],
    ],
],
```

در seeder، کارت `101` برای نمایش مسیر موفق:

```php
if ($board === 'orders.board' && $number === 1) {
    $metadata['reviewApproved'] = true;
}
```

کارت‌های دیگر `blocked` و بدون تأیید — drag به Finance باید خطا بدهد.

## گام ۲ — Factory (کد نهایی)

```php
<?php

declare(strict_types=1);

namespace App\Kanban;

final class OrdersBoardFactory extends WorkbenchBoardFactory
{
    protected function key(): string
    {
        return 'orders.board';
    }

    protected function title(): string
    {
        return 'Orders';
    }

    protected function provider(): string
    {
        return OrdersProvider::class;
    }

    protected function handler(): string
    {
        return OrdersHandler::class;
    }

    protected function settings(): array
    {
        return [
            'interaction' => [
                'transition_policy' => 'deny',
                'transitions' => [
                    'review' => ['finance'],
                    'finance' => ['shipped'],
                ],
            ],
            'features' => [
                'create_stage' => false,
                'bulk' => false,
                'wip' => false,
                'realtime' => false,
            ],
        ];
    }
}
```

`OrdersProvider` = `WorkbenchReader` (بدون تغییر).

## گام ۳ — Handler: گیت قبل از `parent::execute`

```php
<?php

declare(strict_types=1);

namespace App\Kanban;

use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\ActionResult;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Enums\ActionStatus;
use Karnoweb\Kanban\Enums\ActionType;
use Workbench\App\Models\DemoCard;

/**
 * Orders sample: finance stage only accepts cards reviewed in the handler (domain gate).
 */
final class OrdersHandler extends WorkbenchWriter
{
    public function execute(ActionCommand $command, BoardContext $context): ActionResult
    {
        if ($command->type === ActionType::MoveCard) {
            $to = (string) ($command->payload['toStageId'] ?? '');
            $cardId = (string) ($command->payload['cardId'] ?? '');

            if ($to === 'finance') {
                $card = DemoCard::query()
                    ->where('scope_key', $context->scopeKey)
                    ->where('board_key', $context->boardKey)
                    ->where('public_id', $cardId)
                    ->first();

                if (! $card instanceof DemoCard) {
                    return new ActionResult(ActionStatus::NOT_FOUND, messageCode: 'not_found');
                }

                $meta = is_array($card->metadata) ? $card->metadata : [];

                if (! ($meta['reviewApproved'] ?? false)) {
                    return new ActionResult(ActionStatus::DOMAIN_REJECTED, messageCode: 'orders_review_required');
                }
            }
        }

        return parent::execute($command, $context);
    }
}
```

### تولید: جایگزین metadata با سرویس واقعی

```php
if ($to === 'finance') {
    $order = Order::query()->forKanbanScope($context)->find($cardId);
    if (! app(OrderReviewService::class)->isApproved($order)) {
        return new ActionResult(ActionStatus::DOMAIN_REJECTED, messageCode: 'orders_review_required');
    }
}
```

عمل تأیید review باید **خارج از Kanban** (دکمه «Approve review» در پنل سفارش) `reviewApproved` یا رکورد audit را set کند؛ سپس move به Finance مجاز می‌شود.

## گام ۴ — ترجمه پیام خطا (اختیاری)

در `lang/fa/kanban.php` میزبان:

```php
'orders_review_required' => 'تا زمانی که بررسی عملیات تأیید نشود، انتقال به تأیید مالی ممکن نیست.',
```

کد `messageCode` از handler به UI برمی‌گردد.

## گام ۵ — Route

```php
Route::get('/orders', fn () => view('kanban.board', ['key' => 'orders.board']));
```

## گام ۶ — تست دستی

| کارت | اقدام | نتیجه |
| --- | --- | --- |
| 101 (`reviewApproved`) | review → finance | موفق |
| 102 (بدون تأیید) | review → finance | `orders_review_required` |
| هر کارت | review → shipped (پرش) | `transition_denied` در UI |

## گام ۷ — تأیید مالی با فرم (اختیاری)

برای ثبت شماره سند یا مبلغ هنگام ورود به Finance:

```php
'confirmation_rules' => [
    'review>finance' => [
        'mode' => 'form',
        'fields' => [
            'approval_ref' => ['required' => true, 'string', 'max:64'],
        ],
    ],
],
```

در `OrdersHandler` بعد از گیت `reviewApproved`، `formData.approval_ref` را validate کنید و در تراکنش `FinanceService::recordApproval($order, $ref)` را صدا بزنید؛ سپس `parent::execute` برای تغییر stage.

## حذف سفارش

اگر retention اجازه نمی‌دهد:

```php
'features' => ['delete_card' => false],
```

`WorkbenchWriter::deleteCard` با `feature_disabled` جواب می‌دهد.

## کد نهایی workbench

| فایل |
| --- |
| `workbench/app/Kanban/OrdersBoardFactory.php` |
| `workbench/app/Kanban/OrdersHandler.php` |
| `workbench/app/Kanban/OrdersProvider.php` |
| `workbench/database/seeders/KanbanDemoSeeder.php` (`reviewApproved` روی کارت 101) |

## مقایسه با CRM و Ticket

| | CRM | Ticket | Order |
| --- | --- | --- | --- |
| ماتریس UI | خطی + note | شاخه‌دار | خطی |
| گیت دامنه در handler | note + مسیر | (پیشنهاد: تکرار ماتریس) | `reviewApproved` |
| readOnly stage | — | `closed` | — |
| ایجاد کارت/ستون | ممنوع | stage ممنوع | stage ممنوع |

مرجع یک‌جا: [README.md](README.md).

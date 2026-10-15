<?php

declare(strict_types=1);

$docs = [
    '01-quickstart.md' => <<<'MD'
# شروع سریع

میزبان باید `ScopeResolver` را bind کند و یک factory ثبت کند.

```php
$registry->register('tasks.board', \App\Kanban\TaskBoardFactory::class);
```

```blade
<livewire:kanban-board board-key="tasks.board" />
```

Factory در هر درخواست `BoardDefinition` می‌سازد. داده کوچک با `stages()` و `cards()` به `InlineDataProvider` می‌رود. داده بزرگ `provider()` صفحه‌ای می‌خواهد.
MD,
    '02-installation-assets.md' => <<<'MD'
# نصب و دارایی

CSS و JS ازپیش‌ساخته در `resources/dist` هستند و با تگ `kanban-assets` به `public/vendor/kanban` منتشر می‌شوند. Tailwind میزبان لازم نیست. برای اسکن کلاس‌ها، در صورت تمایل `@source` را به مسیر viewهای پکیج بدهید. اسکریپت پکیج `Alpine.start` یا `Livewire.start` را صدا نمی‌زند. CSP باید `self` را برای همان مسیر vendor اجازه دهد.
MD,
    '03-builder-registry.md' => <<<'MD'
# Builder و Registry

`Kanban::init()` نمونه تازه می‌دهد. `pipeline()` همان `board()` است و `setting()` همان `settings()` است. `build()` کلید، برد، provider و handler را اعتبارسنجی می‌کند. Registry کلید تکراری را رد می‌کند و factory را در هر درخواست دوباره resolve می‌کند.
MD,
    '04-dtos-settings.md' => <<<'MD'
# DTO و تنظیمات

`KanbanSettings` گروه‌های views، features، pagination، appearance، realtime، interaction و security دارد. خاموش کردن هم‌زمان Board و List نامعتبر است. `pageSize` پیش‌فرض ۲۵ و سقف ۱۰۰ است. سیاست انتقال پیش‌فرض `deny` است. متادیتا سقف بایت دارد و URL فقط http/https یا مسیر نسبی است.
MD,
    '05-data-provider.md' => <<<'MD'
# DataProvider

متدهای الزامی: `board`, `stages`, `stageCards`, `listCards`, `counts`, `card`, `archives`, `capabilities`, `operationStatus`. فیلتر و شمارش روی منبع انجام می‌شود. `PageResult` می‌تواند `totalCount = null` باشد. cursor فقط offset و hash کوئری را دارد و شناسه tenant داخل آن نیست.
MD,
    '06-handler-actions.md' => <<<'MD'
# ActionHandler

تنها ورودی `execute(ActionCommand, BoardContext)` است. نوع فرمان از `ActionType` است: moveCard، reorderCard، createCard، updateCard، archiveCard، restoreCard، createStage، updateStage، reorderStage، archiveStage، executeCardAction، executeBulkAction. میزبان تراکنش، WIP، نسخه و idempotency با `operationId` را خودش تضمین می‌کند. نبود فرمان باید `UNSUPPORTED` برگرداند، نه خطای ۵۰۰.
MD,
    '07-board-list-ui.md' => <<<'MD'
# Board و List

تغییر نما دستی است. عرض موبایل نما را عوض نمی‌کند. هر ستون اسکرول و load-more مستقل دارد. List نمای عملیاتی است. Virtualization فقط کارت‌های پنجره را در DOM می‌گذارد و داده صفحه‌نشده را دانلود نمی‌کند.
MD,
    '08-move-confirm-wip.md' => <<<'MD'
# انتقال، تأیید و WIP

چهار حالت تأیید: none، generic، custom، form. Cancel هیچ اجرایی به handler نمی‌فرستد. خطای فیلد فرم را پاک نمی‌کند. WIP در UI فقط نشان داده می‌شود؛ رد ظرفیت را handler داخل تراکنش میزبان انجام می‌دهد. حالت strict آخرین ظرفیت را به بیش از یک درخواست نمی‌دهد.
MD,
    '09-crud-archive.md' => <<<'MD'
# ساخت و بایگانی

ساخت کارت از انتهای ستون و از List انجام می‌شود. Enter ثبت و Escape لغو است. بایگانی حذف سخت نیست. بایگانی ستون دارای کارت فعال، بدون سیاست صریح میزبان، رد می‌شود. جزئیات در modal یا drawer از Provider تازه خوانده می‌شود.
MD,
    '10-filters-bulk.md' => <<<'MD'
# فیلتر و عملیات گروهی

فیلدهای sort و filter فهرست سفید دارند. پاسخ دیررس با query hash دور ریخته می‌شود. Bulk دو حالت `partial` و `all-or-nothing` دارد. اتمی بودن را تراکنش میزبان تضمین می‌کند. انتخاب روی شناسه کارت است نه ایندکس صفحه.
MD,
    '11-views-customization.md' => <<<'MD'
# جایگزینی View

`cardView`، `columnView` و `listView` فقط نام view مجاز (حروف، نقطه، دو نقطه) می‌پذیرند. مسیر `..` رد می‌شود. View سفارشی باید متن را escape کند. کلاس‌های CSS با پیشوند `kb-` هستند.
MD,
    '12-realtime.md' => <<<'MD'
# Realtime

رویداد بعد از commit توسط میزبان با `BroadcastEventFactory` ساخته می‌شود. کانال `private-kanban.{scopeHash}.{board}` است. Bridge رویداد تکراری و نسخه قدیمی را دور می‌ریزد و شکاف نسخه را با reload پر می‌کند. Echo را میزبان می‌سازد.
MD,
    '13-multitenancy-security.md' => <<<'MD'
# چندمستأجری و امنیت

`ContextFactory::make($boardKey, $instanceId, $clientScopeKey)` آرگومان سوم را دور می‌ریزد. کارت tenant دیگر `null` یا `NOT_FOUND` است و عنوانش برنمی‌گردد. عنوان HTML در Blade escape می‌شود.
MD,
    '14-preferences-templates.md' => <<<'MD'
# ترجیح و قالب

`PreferenceStore` نمای Board/List را با کلید کاربر، hash محدوده و برد نگه می‌دارد. پیاده‌سازی پیش‌فرض session است. اگر session نباشد `NullPreferenceStore` عملکرد برد را متوقف نمی‌کند. قالب‌های CRM، Ticketing، Task و Orders در workbench هستند و قانون دامنه را داخل هسته پکیج نمی‌گذارند.
MD,
    '15-events-observability.md' => <<<'MD'
# رویداد UI و پایش

رویدادهای Livewire بعد از نتیجه سرور: `kanban:ready`, `kanban:move-requested`, `kanban:move-confirmed`, `kanban:move-rejected`, `kanban:card-created`, `kanban:stage-created`, `kanban:bulk-completed`, `kanban:conflict`, `kanban:realtime-status-changed`. `TelemetryDispatcher` عنوان، یادداشت، URL و متادیتا را از audit حذف می‌کند.
MD,
    '16-testing-scenarios.md' => <<<'MD'
# تست

```bash
vendor/bin/phpunit
```

سناریوها در `tests/Scenarios/KB001...` تا `KB044` هستند و هر کدام یک فایل Markdown فارسی دارند. Fixture حافظه‌ای در `tests/Fixtures` است و جدول پکیج نمی‌سازد.
MD,
    '17-performance.md' => <<<'MD'
# کارایی

ده ستون و ده هزار کارت منبع، در بار اول حداکثر ۲۵۰ `CardData` می‌سازند (۲۵ در هر ستون). شمارش کل جدا از تعداد بارگذاری‌شده است. محیط مرجع همین ماشین توسعه است؛ عدد p95 مرورگر بدون Playwright در این مخزن اندازه‌گیری نشده و ادعای ۲٫۵ ثانیه برای مرورگر واقعی ثبت نمی‌شود. بودجه داده صفحه‌ای در KB-010 سنجیده می‌شود.
MD,
    '18-troubleshooting.md' => <<<'MD'
# رفع اشکال

اگر برد خالی و پیام دسترسی دیدید، `ScopeResolver` bind نشده است. اگر انتقال همیشه ممنوع است، سیاست پیش‌فرض `deny` است و باید `transitions` یا `allow` بدهید. اگر CSS نیامد، تگ `kanban-assets` را منتشر کنید. اگر رویداد Realtime اثر ندارد، `realtime.enabled` و feature را با هم روشن کنید و Echo میزبان را به کانال خصوصی وصل کنید.
MD,
    '19-upgrade-compatibility.md' => <<<'MD'
# سازگاری

PHP `^8.3`، Laravel `^13`، Livewire `^4`. Testbench هم‌نسخه `^11` است. API عمومی امضاهای `BoardBuilder`، `DataProvider::stageCards` و `ActionHandler::execute` است. نام متد آزاد از مرورگر پذیرفته نمی‌شود.
MD,
    '20-api-reference.md' => <<<'MD'
# مرجع API

- `Kanban::init(): BoardBuilder`
- `BoardBuilder::key/board/pipeline/stages/cards/provider/handler/settings/setting/onMove/onClick/cardView/columnView/listView/build`
- `BoardRegistry::register(string $key, class-string $factory): void`
- `BoardRegistry::resolve(string $key, BoardContext $context): BoardDefinition`
- `DataProvider::stageCards(BoardContext, string $stageId, BoardQuery): PageResult`
- `ActionHandler::execute(ActionCommand, BoardContext): ActionResult`
- `ActionStatus`: success, validation_error, forbidden, not_found, conflict, wip_limit, domain_rejected, rate_limited, retryable_error, unknown_outcome, unsupported

کامپوننت Livewire: `<livewire:kanban-board board-key="..." />`
MD,
];

foreach ($docs as $name => $contents) {
    file_put_contents(__DIR__.'/../docs/'.$name, $contents);
}

$examples = [
    'crm.md' => 'CRM چهار مرحله دارد. انتقال به بسته فقط با یادداشت و از مرحله مذاکره مجاز است. تعارض نسخه را handler با expectedVersion برمی‌گرداند.',
    'ticketing.md' => 'تیکت بسته readOnly است. Realtime خصوصی است. کاربر tenant دیگر عنوان تیکت را نمی‌بیند.',
    'task-manager.md' => 'ساخت کارت و ستون، WIP، فیلتر مسئول، بایگانی گروهی و بازگشت به List در همین قراردادها انجام می‌شود.',
    'orders-approvals.md' => 'مرحله تأیید مالی فقط وقتی handler میزبان اجازه دهد باز می‌شود. پکیج پرداخت یا سند حسابداری نمی‌سازد.',
];

foreach ($examples as $name => $text) {
    file_put_contents(__DIR__.'/../docs/examples/'.$name, "# {$name}\n\n{$text}\n\nنمونه کد اجرایی در `workbench/app/Kanban` است.\n");
}

file_put_contents(__DIR__.'/../docs/README.md', "# راهنما\n\nاز `01-quickstart.md` تا `20-api-reference.md` و پوشه `examples`.\n");
echo "docs\n";

# Karnoweb Laravel Livewire Kanban

رابط Board و List برای Laravel 13 و Livewire 4. پکیج جدول، migration یا مدل کسب‌وکار ندارد. داده و تغییر را میزبان با `DataProvider` و `ActionHandler` انجام می‌دهد.

## اتصال اختیاری مدل‌های لاراول

مدل با `HasKanbanCards` و یک متد `kanbanDefinition()`، نگاشت فیلدها، محدوده خواندن و مجوز عملیات را تعریف می‌کند. سپس مدل را در `KanbanModelRegistry` ثبت و در Factory با `sources(['tasks'])` انتخاب کنید. روش‌های فعلی `provider()` و `handler()` همچنان معتبرند. Trait به‌تنهایی منبع را در هیچ بردی نمایش نمی‌دهد.

راهنمای اجرایی: [اتصال مدل](docs/21-trait-eloquent-guide.md)، [برد چندمنبعی](docs/22-multi-source-boards.md)، [کارایی](docs/23-eloquent-performance.md)، [گردش‌کار و رتبه](docs/24-workflow-enum-ranking.md)، [آمار و گروه‌بندی](docs/25-metrics-swimlanes.md)، [تقویم](docs/26-calendar-integration.md) و [ارتقا](docs/27-migration-and-upgrade.md).

نمونه‌های مستقل جدید پس از اجرای migration/seed محیط نمونه در `/models/tasks`، `/models/tickets`، `/models/classes`، `/models/mixed` و `/models/compact` هستند. گزارش توسعه سازمانی و محدودیت‌های تأیید محیط را در `docs/ENTERPRISE-IMPLEMENTATION-STATUS-FA.md` ببینید؛ وضعیت نسخه قبلی، تأیید انتشار این توسعه محسوب نمی‌شود.

## نصب

```bash
composer require karnoweb/laravel-livewire-kanban
```

میزبان دارای DaisyUI: CSS منبع بسته را در فایل سراسری import کنید؛ [راهنمای کامل و متغیرهای میزبان](docs/11-views-customization.md). DaisyUI 5.7.50 با تنظیمات پیش‌فرض بررسی شده است.

دارایی آماده فقط برای میزبان بدون DaisyUI (شامل Tailwind و DaisyUI):

```html
<link rel="stylesheet" href="{{ asset('vendor/kanban/kanban.css') }}">
<script src="{{ asset('vendor/kanban/kanban.js') }}" defer></script>
```

انتشار دارایی:

```bash
php artisan vendor:publish --tag=kanban-assets
php artisan vendor:publish --tag=kanban-config
```

`kanban.js` نباید Alpine، Livewire یا Echo را دوباره راه‌اندازی کند. Echo را خود میزبان، فقط وقتی Realtime روشن است، می‌سازد.

## اجرای Workbench (توسعه و دمو)

محیط نمونه با [Orchestra Testbench](https://github.com/orchestral/testbench) و پوشه `workbench/` است؛ اپلیکیشن Laravel جدا نیست.

**پیش‌نیاز:** PHP 8.3+، Composer، SQLite (پیش‌فرض فایل `workbench/database/workbench.sqlite`).

```bash
cd kanban-livewire
composer install
php vendor/bin/testbench migrate --force
php vendor/bin/testbench db:seed --class="Workbench\Database\Seeders\KanbanDemoSeeder" --force
php vendor/bin/testbench serve --host=127.0.0.1 --port=8000
```

مرورگر: [http://127.0.0.1:8000/tasks](http://127.0.0.1:8000/tasks)

برای ورود بدون فرم لاگین:

| آدرس | کاربر دمو |
| --- | --- |
| `/login-as/alice` | tenant-a |
| `/login-as/bob` | tenant-a |
| `/login-as/carol` | tenant-b |

**صفحات نمونه** (هر کدام `BoardFactory` و تنظیمات جدا طبق `docs/examples/`):

| مسیر | سناریو |
| --- | --- |
| `/tasks` | Task manager — انتقال آزاد، WIP، لیست/برد |
| `/crm` | CRM — انتقال محدود، تأیید بستن معامله |
| `/tickets` | Ticketing — مسیر محدود، ستون Closed فقط‌خواندنی |
| `/orders` | Orders — گیت تأیید مالی در handler میزبان |
| `/multi` | دو برد روی یک صفحه |

**دارایی front:** CSS/JS از `resources/dist/` سرو می‌شوند. بعد از تغییر `resources/css/kanban.css` یا `resources/js/`:

```bash
npm ci
npm run build
```

**Realtime (اختیاری، برای برد Tickets):** در ترمینال دوم:

```bash
php vendor/bin/testbench reverb:start
```

بدون Reverb، drag و ایجاد کارت روی بیشتر بردها کار می‌کند؛ فقط به‌روزرسانی زنده بین کلاینت‌ها نیاز به Reverb دارد.

**تست:**

```bash
vendor/bin/phpunit --no-coverage
# فقط workbench:
vendor/bin/phpunit --no-coverage --testsuite=Workbench
```

## ثبت برد

```php
use Karnoweb\Kanban\Support\BoardRegistry;

public function boot(BoardRegistry $registry): void
{
    $registry->register('crm.deals', \App\Kanban\CrmBoardFactory::class);
}
```

هویت و tenant فقط از `ScopeResolver` سرور می‌آید. `board-key` مجوز نیست.

```blade
<livewire:kanban-board board-key="crm.deals" />
```

## قرارداد کوتاه

- `Kanban::init()` هر بار یک `BoardBuilder` تازه می‌دهد.
- `build()` یک `BoardDefinition` تغییرناپذیر برمی‌گرداند.
- Closure در state کامپوننت Livewire ذخیره نمی‌شود؛ `onMove()` فقط کلاس `ActionHandler` می‌پذیرد.
- انتقال پیش‌فرض `deny` است.
- `UNKNOWN_OUTCOME` موفقیت نیست. فرمان با `operationId` تازه تکرار نمی‌شود.
- صفحه‌بندی cursor-based است. پیش‌فرض هر ستون ۲۵ کارت است.

راهنمای کامل در `docs/` است (از جمله [راهنمای سفارشی‌سازی ظاهر و سلکتورهای CSS](docs/11-views-customization.md)). سناریوهای پذیرش `KB-001` تا `KB-044` در `tests/Scenarios` هستند.

مجوز این مخزن تا اعلام مالک `proprietary` است و مجوز متن‌باز محسوب نمی‌شود.

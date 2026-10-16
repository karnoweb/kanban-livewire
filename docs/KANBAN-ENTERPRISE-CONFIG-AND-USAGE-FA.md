> به‌روزرسانی ظاهر: CSS منبع اکنون به DaisyUI 5 وابسته است. میزبان دارای DaisyUI باید آن را در CSS سراسری import کند و فایل مستقل dist را اضافه نکند. قرارداد پوسته، متغیرها و تمام انتخابگرها: [راهنمای سفارشی‌سازی](11-views-customization.md).

# راهنمای جامع نصب، کانفیگ و استفاده سازمانی — Laravel Livewire Kanban

> **نسخه راهنما:** 2.1 — 2026-10-16\
> **مبنای کد موجود:** `kanban-livewire.zip` ارسالی کاربر، با Composer package: `karnoweb/laravel-livewire-kanban`\
> **وضعیت:** قرارداد **هدف پس از اجرای پرامپت** + راهنمای روش‌های فعلی؛ نمونه‌های دارای برچسب «جدید» تا زمان پیاده‌سازی Agent قابل اجرا نیستند.\
> **مخاطب:** توسعه‌دهنده Laravel 13 / PHP 8.3+ / Livewire 4 که می‌خواهد Kanban را برای Task، CRM، Ticket، کلاس یا سفارش استفاده کند.

## فهرست

1. هدف و مرز مسئولیت\
2. تفاوت API موجود و API جدید\
3. پیش‌نیاز و نصب\
4. کانفیگ موجود: همه گروه‌ها\
5. تنظیمات جدید و پیشنهاد نهایی\
6. امنیت و ScopeResolver\
7. ساده‌ترین اتصال با Trait\
8. ثبت منابع و ساخت BoardFactory\
9. انتخاب منابع برای هر برد\
10. تعریف ستون‌ها با Enum\
11. نگاشت فیلدهای غیرهم‌نام\
12. قوانین مشاهده و عملیات\
13. گردش‌کار، تأیید و فرم سفارشی\
14. جست‌وجو و فیلترهای خودکار\
15. صفحه‌بندی و بهینه‌سازی Eloquent\
16. رتبه‌بندی و جابه‌جایی\
17. شمارنده‌ها، آمار ستون، WIP\
18. Swimlane و گروه‌بندی\
19. کارت غنی، جزئیات، پیوست و چک‌لیست\
20. نمایش Board/List/compact و RTL\
21. Realtime، کش و مدیریت تعارض\
22. اتصال به Calendar\
23. سناریوهای واقعی CRM، تیکت، کلاس و سفارش\
24. مسیر جایگزین بدون Trait\
25. نصب بدون دیتابیس و محدوده Migration\
26. اشکال‌زدایی و عیب‌یابی\
27. تست‌ها و دستورهای اعتبارسنجی\
28. ارتقا بدون شکستن پروژه‌های قبلی\
29. ماتریس امکانات و چک‌لیست انتشار

---

## 1. هدف محصول و مرز مسئولیت

هسته مسئول **نمایش برد/لیست، گردش عملیات UI، قوانین عمومی، صفحه‌بندی، واکشی کنترل‌شده، وضعیت عملیات، Realtime و دسترس‌پذیری** است.

مالک مدل‌های `Task`, `Ticket`, `EducationalClass`, `Deal`, `Order`، جدول‌ها، قواعد مالی، اطلاعات مشتری، فایل، اعلان، یادآور و تاریخچه دامنه‌ای **اپلیکیشن میزبان** است. هیچ جدول یا Migration اجباری برای هسته نباید وجود داشته باشد. پکیج نباید مفهوم tenant/branch/club را خودش تعریف کند. اگر میزبان چنین مفاهیمی دارد، تنها از `BoardContext`/`ScopeResolver` و قوانین مدل استفاده کند.

سه روش مصرف:

- **ساده و خودکار [جدید]:** Trait + `kanbanDefinition()` + ثبت منبع + BoardFactory. مناسب اغلب ماژول‌های Eloquent.
- **پیشرفته [موجود]:** پیاده‌سازی `DataProvider` و `ActionHandler`؛ مناسب API، دیتابیس خارجی یا قوانین خاص.
- **نمایش داده کوچک [موجود]:** `Kanban::init()->stages()->cards()->build()` و `InlineDataProvider`؛ مناسب دمو و داده کم، نه برد بزرگ.

## 2. وضعیت واقعی APIها: موجود در برابر جدید

| API / قابلیت | وضعیت ZIP فعلی | قرارداد هدف پس از Agent |
|---|---|---|
| `Kanban::init()`, `BoardBuilder` | موجود | حفظ بدون breaking change |
| `BoardRegistry::register()` و `BoardFactory` | موجود | حفظ |
| `DataProvider`, `ActionHandler` | موجود | حفظ با adapter جدید |
| `BoardContext`, `BoardQuery`, `CardData`, `StageData` | موجود | حفظ و گسترش سازگار |
| `<livewire:kanban-board board-key="..." />` | موجود | حفظ |
| `HasKanbanCards` | **وجود ندارد** | اضافه شود |
| `KanbanModelDefinition` | **وجود ندارد** | اضافه شود |
| `KanbanModelRegistry` | **وجود ندارد** | اضافه شود |
| `BoardBuilder::sources()` | **وجود ندارد** | اضافه شود |
| `EloquentKanbanDataProvider` خودکار | **وجود ندارد** | اضافه شود |
| `ColumnMetricsProvider` و `SwimlaneDefinition` | **وجود ندارد** | اضافه شود |
| auto stage from Enum، automatic scoped SQL filter | **وجود ندارد** | اضافه شود |
| sortable rank adapter / Eloquent Handler | **در این شکل وجود ندارد** | اضافه شود |
| پایه WIP/confirmation/Realtime/Bulk/Views | موجود | حفظ و تکمیل |

**راهنمای اجرایی:** مثال‌های «جدید» زیر، قرارداد API نهایی برای ساخت توسط Agent هستند، نه ادعای وجود همین متدها در ZIP. اگر کلاس‌ها را قبل از اجرای پرامپت پیدا نکردی، خطا طبیعی است؛ ابتدا توسعه را انجام بده.

## 3. نصب و آماده‌سازی میزبان

### 3.1 پیش‌نیازهای واقعی

طبق `composer.json` فعلی: PHP `^8.3`، `illuminate/support` نسخه `^13.0`، Livewire `^4.0`. پروژه‌ای که Laravel 12 دارد با این نسخه Composer فعلاً سازگار فرض نمی‌شود. پکیج به Blade و assetهای پکیج نیاز دارد. Eloquent adapter جدید فقط زمانی نیازمند اتصال دیتابیس است که واقعاً استفاده شود.

### 3.2 نصب Package

اگر بسته از Packagist قابل دسترسی باشد:

```bash
composer require karnoweb/laravel-livewire-kanban
```

اگر هنوز منتشر نشده، در پروژه میزبان از Composer `path` repository یا VCS مخزن خودتان استفاده کنید؛ نام بسته `karnoweb/laravel-livewire-kanban` باشد. Composer package version فعلی `13.0.0` داخل فایل است؛ برچسب نسخه/وجود آن روی Packagist در این بررسی تأیید نشده است.

### 3.3 انتشار تنظیمات و استایل‌ها (در کد موجود)

```bash
php artisan vendor:publish --tag=kanban-config
php artisan vendor:publish --tag=kanban-assets
# فقط در صورت نیاز به جایگزینی Viewها:
php artisan vendor:publish --tag=kanban-views
```

- کانفیگ در `config/kanban.php` میزبان قرار می‌گیرد.
- فایل‌های CSS/JS بسته در `public/vendor/kanban` منتشر می‌شوند.
- در layout میزبان assetهای منتشرشده را وارد کن، ترجیحاً با helper `asset()` و بدون درج مکرر در چند جای صفحه؛ اگر بسته در نسخه بعد helper خودکار asset ارائه کرد همان روش مستند را استفاده کن.
- Livewire 4 و Alpine که از قبل در میزبان‌اند نباید دوباره inject شوند.
- اطمینان حاصل کن CSS سراسری میزبان (یا CSS مستقل بسته برای میزبان بدون DaisyUI) و JavaScript بسته منتشر شده و browser با 200 دریافت می‌کند؛ build development فقط در مخزن پکیج لازم است.
- از اجرای همزمان JS build قدیمی و جدید جلوگیری کن.

## 4. کانفیگ موجود: شرح تنظیمات واقعی `config/kanban.php`

### 4.1 نمای اولیه و page size

| کلید | پیش‌فرض واقعی | معنی |
|---|---|---|
| `page_size` | `25` | تعداد کارت پایه در هر بار درخواست |
| `max_page_size` | `100` | سقف مجاز صفحه |
| `default_view` | `board` | نمای اولیه |
| `allowed_views` | `['board','list']` | نماهای قابل انتخاب |
| `default_transition_policy` | `deny` | رفتار در نبود مجوز انتقال |

### 4.2 `features`

تمام کلیدهای موجود (پیش‌فرض در پرانتز):

- `drag_drop` (true)، `reorder_cards` (true)، `reorder_stages` (true)
- `create_card` (true)، `update_card` (true)، `create_stage` (true)، `update_stage` (true)
- `archive_card` (true)، `archive_stage` (true)، `restore_card` (true)
- `search` (true)، `filters` (true)، `bulk` (true)، `wip` (true)
- `details` (true)، `virtualization` (true)، `realtime` (**false**)
- `custom_actions` (true)، `toolbar` (true)، `view_switch` (true)
- `theme_switch` (true)، `direction_switch` (true)، `density_switch` (true)

**نکته امنیتی:** خاموش‌کردن یک گزینه در UI نباید فقط دکمه را مخفی کند؛ `ActionDispatcher` و Handler نیز باید آن عملیات را رد کنند. منبع حقیقت تنظیمات از سرور است.

### 4.3 `appearance`

| کلید | پیش‌فرض |
|---|---|
| `theme` | `system` |
| `direction` | `ltr` |
| `density` | `comfortable` |
| `column_width` | `18rem` |
| `detail_surface` | `modal` |

برای فارسی می‌توان direction را `rtl` تعریف کرد؛ تغییر باید با `KanbanSettings` مدل شود تا تنظیم اعمال شود، نه فقط config global.

### 4.4 `interaction`

`animations=true`, `reduced_motion=false`, `keyboard_alternative=true`, `confirmation='none'`.

### 4.5 `realtime`

`enabled=false`, `refresh='invalidate'`, `reconnect=true`, `dedupe_limit=200`, `coalesce_ms=50`.

Realtime فقط وقتی فعال شود که Broadcasting، مجوز channel و زیرساخت Pub/Sub میزبان صحیح باشد. Reverb تنها driver ممکن نیست؛ قرارداد پکیج باید بر مبنای Laravel Broadcasting بماند.

### 4.6 `security` و `limits`

- `security.allowed_url_schemes=['http','https']`
- `security.metadata_bytes=8192`
- `security.search_length=200`
- `security.bulk_ids=100`
- `security.rate_limit_per_minute=null`
- `limits.search_length=200`, `bulk_ids=100`, `metadata_bytes=8192`, `id_length=128`, `title_length=300`.

### 4.7 مجوز sort/filter

- `sort_fields`: `manualOrder`, `dueAt`, `createdAt`, `title`, `priority`
- `filter_fields`: `assignee`, `priority`, `stage`, `dueFrom`, `dueTo`, `createdFrom`, `createdTo`, `badge`, `metadata`.

این فهرست‌ها allowlist عمومی‌اند؛ **مدل هم باید ستون‌ها و operators مجاز خود را تعیین کند**. یک sort/filter تعریف‌نشده نباید به نام ستون دلخواه SQL تبدیل شود.

### 4.8 سایر بخش‌ها

`assets.publish_tag='kanban-assets'`, `assets.prefixed_path='vendor/kanban'`, `telemetry.enabled=false`.

**تکلیف Agent:** صحت استفاده تمام کلیدهای فوق را بررسی کند و در `KanbanSettings::fromArray()` هیچ تنظیم موجود یا جدیدی را خاموش و بی‌صدا نادیده نگیرد.

## 5. کانفیگ جدید پیشنهاد‌شده [پس از اجرای پرامپت]

این نمونه **قرارداد پیشنهادی** برای افزودن به `config/kanban.php` است؛ مقدارها باید در کد و تست نهایی دقیقاً با آن منطبق شوند:

```php
'models' => [
    'auto_discover' => false,          // اسکن خودکار کلاس‌ها ممنوع
    'allow_unlisted_sources' => false, // منبع ناشناخته رد شود
    'default_board_sources' => [],     // هر برد صریحاً انتخاب کند
],
'eloquent' => [
    'enabled' => true,
    'query_mode' => 'db_first',
    'default_page_size' => 25,
    'cursor' => ['signed' => true, 'bind_to_scope' => true],
    'count_mode' => 'exact',
    'eager_load' => 'declared_only',
    'allow_raw_sort' => false,
    'allow_raw_filter' => false,
],
'ordering' => [
    'strategy' => 'rank',
    'rank_column' => null,             // مدل باید نگاشت خودش را اعلام کند
    'rebalance' => 'local',
    'mixed_sources_require_adapter' => true,
],
'metrics' => ['enabled' => true, 'cache_ttl' => 0],
'swimlanes' => ['enabled' => true, 'max_groups' => 30],
'cache' => ['enabled' => false, 'ttl' => 30],
```

**قرارداد اولویت تنظیمات:** config سراسری → settings هر برد → تنظیمات تعریف مدل → مجوز دامنه/سرور. هیچ override نمایش نباید اجازه سرور را افزایش دهد. مقادیر عددی مقید به سقف‌های امنیتی سراسری بمانند.

## 6. ساخت `ScopeResolver` در برنامه میزبان [موجود]

این قسمت **واقعاً در کد فعلی لازم است**. بدون آن برد نباید داده نشان دهد.

```php
<?php
namespace App\Kanban;

use Karnoweb\Kanban\Contracts\ScopeResolver;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Exceptions\BoardAccessDenied;

final class AppScopeResolver implements ScopeResolver
{
    public function resolve(string $boardKey, ?string $instanceId = null): BoardContext
    {
        $user = auth()->user();
        if (!$user) {
            throw new BoardAccessDenied('Unauthenticated.'); // امضای exception را در پروژه نهایی تأیید کن
        }

        // مثال: اگر پروژه tenant ندارد می‌توان scope ثابت داشت؛
        // اگر tenant دارد، مقدار آن را صرفاً از auth/session معتبر سرور بخوان.
        $scopeKey = 'workspace:main';

        return new BoardContext(
            boardKey: $boardKey,
            scopeKey: $scopeKey,
            actorId: (string) $user->getAuthIdentifier(),
            instanceId: $instanceId ?? 'default',
        );
    }
}
```

در `AppServiceProvider::register()`:

```php
$this->app->bind(
    \Karnoweb\Kanban\Contracts\ScopeResolver::class,
    \App\Kanban\AppScopeResolver::class,
);
```

توجه: اگر برد فقط برای نقش‌های خاصی است، علاوه بر login، مجوز `boardKey` در Resolver یا Factory و مجوز ردیف در Query اعمال شود. کاربر عادی نباید با تغییر `board-key` در HTML به برد مدیریتی وارد شود.

## 7. سریع‌ترین اتصال مدل با Trait [جدید — API هدف]

فرض: مدل `Task` جدولش `tasks` است و ستون‌های `id`, `title`, `description`, `status`, `sort_rank`, `user_id`, `priority`, `due_at` دارد. **ستون‌ها فقط نمونه‌اند؛ خود پکیج جدول یا این فیلدها را ایجاد نمی‌کند.**

**نمونه کامل روش توسعه‌دهنده: یک متد برای تنظیمات مدل**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Karnoweb\Kanban\Concerns\HasKanbanCards;                // جدید
use Karnoweb\Kanban\Models\KanbanModelDefinition;          // جدید
use Karnoweb\Kanban\Data\BoardContext;

final class Task extends Model
{
    use HasKanbanCards;

    public static function kanbanDefinition(): KanbanModelDefinition
    {
        return KanbanModelDefinition::make(source: 'tasks', model: static::class)
            ->fields([
                'id' => 'id',
                'title' => 'title',
                'description' => 'description',
                'stage' => 'status',
                'rank' => 'sort_rank',
                'priority' => 'priority',
                'dueAt' => 'due_at',
            ])
            ->stages(\App\Enums\TaskStatus::class)
            ->visibleQuery(function (Builder $query, BoardContext $context): Builder {
                return $query->where('user_id', $context->actorId);
            })
            ->authorize(function (string $ability, ?Model $record, BoardContext $context): bool {
                return $record === null
                    ? $ability === 'create'
                    : (string) $record->user_id === $context->actorId;
            })
            ->filters(['priority', 'status', 'due_at'])
            ->sorts(['manualOrder', 'dueAt', 'createdAt', 'title']);
    }
}
```

**نکته قرارداد:** متدهای زنجیره‌ای بالا API هدف‌اند. Agent باید شکل دقیق این قرارداد و type validation را پیاده و تست کند؛ اگر نام‌های نهایی کمی متفاوت شدند همه مثال‌ها را یکپارچه تغییر دهد. هیچ `kanbanDefinition` نباید مدل/Closure را به کلاینت ارسال کند.

**نکته مجوز:** برای پروژه چندشعبه‌ای، به‌جای `where('user_id', ...)` باید Query Scope معتبر خودت را در همان متد وارد کنی (مثلاً `where('organization_id', $ctx->scope...)` با تفکیک هویت معتبر)؛ وجود Trait مجوز پیش‌فرض ایجاد نمی‌کند.

## 8. ثبت مدل‌ها و تعریف برد [جدید + زیرساخت موجود]

### 8.1 ثبت یک‌باره منابع [جدید]

داخل `AppServiceProvider::boot()` یا Provider اختصاصی:

```php
$models = app(\Karnoweb\Kanban\Models\KanbanModelRegistry::class);
$models->register('tasks', \App\Models\Task::class);
$models->register('tickets', \App\Models\Ticket::class);
$models->register('classes', \App\Models\EducationalClass::class);
```

فقط یک بار در زمان boot و بدون نگهداری state کاربر در رجیستری. در اپ بدون کلاس آموزشی، رجیستر `classes` را حذف کن.

### 8.2 کارخانه برد فقط Task [زیرساخت فعلی + `sources()` جدید]

```php
<?php
namespace App\Kanban;

use Karnoweb\Kanban\Contracts\BoardFactory;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardData;
use Karnoweb\Kanban\Data\BoardDefinition;
use Karnoweb\Kanban\Facades\Kanban;

final class TaskBoardFactory implements BoardFactory
{
    public function make(BoardContext $context): BoardDefinition
    {
        return Kanban::init()
            ->key('tasks.board')
            ->board(new BoardData(
                id: 'tasks.board',
                title: 'وظایف من',
                scopeKey: $context->scopeKey,
            ))
            ->sources(['tasks']) // جدید؛ تنها منبع مجاز این برد
            ->settings([
                'views' => ['default' => 'board', 'allowed' => ['board','list']],
                'appearance' => ['direction' => 'rtl', 'theme' => 'system'],
                'pagination' => ['page_size' => 25],
                'interaction' => ['transition_policy' => 'deny'],
            ])
            ->build();
    }
}
```

**تصمیم لازم در Builder:** وقتی `sources()` وجود دارد و provider/handler دستی معرفی نشده‌اند، adapter Eloquent خودکار انتخاب شود؛ در غیر این صورت provider/handler سفارشی و صریح مصرف‌کننده اولویت داشته باشد. اگر منابع Eloquent و غیر-Eloquent ترکیب شدند، مصرف‌کننده باید combined provider بدهد یا adapter استاندارد جدید معرفی شود.

### 8.3 ثبت Factory [موجود]

در `AppServiceProvider::boot()`:

```php
app(\Karnoweb\Kanban\Support\BoardRegistry::class)
    ->register('tasks.board', \App\Kanban\TaskBoardFactory::class);
```

و در Blade:

```blade
<livewire:kanban-board board-key="tasks.board" />
```

**این نام کامپوننت در نسخه ZIP فعلی واقعی است.**

## 9. هر برد فقط منابع انتخابی خودش [مهم‌ترین قاعده]

یک مدل دارای Trait ممکن است در چندین برد ظاهر شود، ولی اینکه در کدام برد حاضر باشد فقط از Factory همان برد مشخص می‌شود:

| Board key | `sources()` | کاربرد |
|---|---|---|
| `tasks.board` | `['tasks']` | فقط وظایف |
| `tickets.board` | `['tickets']` | فقط تیکت‌ها |
| `classes.board` | `['classes']` | فقط کلاس‌های آموزشی |
| `operations.board` | `['tasks','tickets']` | کارهای مشترک |
| `all.board` | `['tasks','tickets','classes']` | فقط اگر صریحاً و با مجوز تعریف شود |

سه لایه کنترل الزاماً جدا هستند:

1. مدل در Model Registry ثبت شده.
2. برد، `sources()` مجاز خودش را محدود می‌کند.
3. کاربر می‌تواند فقط در میان همین منابع UI filter انجام دهد.

### 9.1 برد چندمنبعی

اگر `Task.status='doing'` و `Ticket.state='in_progress'` هر دو باید در ستون «در حال انجام» باشند، Factory باید `stageMap` بدهد. نمونه **قرارداد پیشنهادی**:

```php
->sources(['tasks', 'tickets'])
->stageMap([
    'tasks' => ['pending' => 'todo', 'doing' => 'doing', 'done' => 'done'],
    'tickets' => ['open' => 'todo', 'in_progress' => 'doing', 'closed' => 'done'],
])
```

اگر نگاشت ندارد، fail-fast با پیام واضح؛ به‌هیچ‌وجه دو وضعیت با نام مشابه ولی معنای متفاوت را خودکار یکی نکن.

### 9.2 شناسه کارت در برد مختلط

از شناسه `tasks:15` و `tickets:15` استفاده کن؛ هنگام عملیات، سرور باید منبع و ID را parse، با allowlist برد مقایسه و رکورد را با query scoped واکشی کند. هیچ مدل/جدول از نام ارسالی کاربر مستقیماً ساخته نشود.

### 9.3 empty allowlist

`->sources([])` به معنی «هیچ منبعی» است، نه «همه منابع». برای حالت همه تنها `->sources(['tasks','tickets',...])` با تصمیم صریح مجاز است.

## 10. تعریف ستون‌ها با Enum [جدید]

در مدل ساده، Enum وضعیت‌ها می‌تواند منبع تعریف ستون‌ها باشد:

```php
<?php
namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case Doing = 'doing';
    case Done = 'done';
}
```

مقادیر ستون از Enum تولید شوند. برای عنوان فارسی، رنگ، ترتیب، محدودیت WIP و انتقال‌های مجاز دو راه پیشنهاد می‌شود:

- Enum می‌تواند قرارداد اختیاری `KanbanStageEnum` را پیاده کند و `label()/color()/order()/wipLimit()/allowedTransitions()` بدهد.
- یا همین نگاشت در `kanbanDefinition()` باشد. **کاربر مجبور به پیاده‌سازی interface پیچیده برای سه وضعیت ساده نباشد.**

قانون انتقال نمونه: pending → doing؛ doing → pending/done؛ done → هیچ. default انتقال `deny` است؛ هر انتقال صریحاً allow شود.

## 11. فیلدهای غیرهم‌نام، رابطه‌ها و ذخیره‌سازی

پکیج نباید مدل را مجبور به `status`, `sort_rank`, `title` یا `user_id` کند.

| مدل | نام فیلد واقعی | نگاشت تعریف |
|---|---|---|
| `Task` | `status`, `title`, `due_at` | `stage`, `title`, `dueAt` |
| `Ticket` | `state`, `subject`, `sla_deadline` | `stage`, `title`, `dueAt` |
| `Deal` | `pipeline_stage`, `customer_name`, `value` | `stage`, `title`, metric: sum(value) |
| `EducationalClass` | `approval_state`, `name`, `start_at` | `stage`, `title`, `dueAt` |

برای فیلدهایی که رابطه هستند، Eloquent provider باید `with()` دقیق و محدود بزند. Callback computed فقط روی رکوردهای صفحه فعلی اجرا شود؛ محاسبات فیلتر و شمارش باید SQL-side باشند.

اگر مدل امکان update مستقیم stage ندارد (مثلاً تیکت بستن نیاز به سرویس domain دارد)، definition باید Handler اختصاصی آن عملیات را معرفی کند؛ خودکارسازی نباید business rule را دور بزند.

## 12. امنیت و مجوزها

### 12.1 دو نوع قانون مستقل

- **Query visibility:** اصلاً کدام ردیف‌ها از دیتابیس خوانده شوند؟ در SQL، پیش از count و pagination.
- **Action authorization:** روی یک ردیف مجاز، چه عملیاتی می‌توان انجام داد؟ در سرور و داخل transaction.

`canMove=true` روی کارت برای UX کافی نیست؛ ActionHandler باید دوباره verify کند.

### 12.2 استراتژی‌های معتبر

1. `visibleQuery(Builder, BoardContext)` ساده برای مالکیت، tenant و ACL (فهرست مجوزها).
2. Laravel Policies/Gates برای check جزئیات و عملیات، با scoped fetch.
3. handler دامنه برای تأییدهایی مانند پرداخت سفارش، SLA تیکت، حضور کلاس.

بدون visibleQuery/قانون قابل تأیید برای برد Eloquent حساس، fail-closed؛ هیچ fallback به `Model::query()` بدون شرط مجاز نباشد.

### 12.3 مثال‌ها

- کاربر ۱۲، برد `tasks.board`: فقط `user_id=12`.
- مدیر شعبه B: فقط مدل‌های مجاز همان شعبه. `branch_id` در مدل میزبان باشد، نه هسته.
- `tickets.board`: حتی اگر کلاینت `tasks:12` فرستاد، بازگرداندن `NOT_FOUND`/`DENIED` بدون لو دادن عنوان.
- `counts()` نباید تعداد کارت‌های tenant دیگر را افشا کند.

## 13. عملیات، گردش‌کار، فرم‌ها و lifecycle

عملیات موجود پکیج با `ActionHandler::execute(ActionCommand, BoardContext): ActionResult` اجرا می‌شوند. Eloquent adapter جدید این قرارداد را دور نمی‌زند.

**چرخه انتقال پیشنهادی:**

1. Drag در UI → `MoveRequest` با board/card/from/to/anchors/expectedVersion/operationId.
2. بررسی board source allowlist + row visibility.
3. بررسی read-only, WIP, transition matrix, domain policies.
4. اگر فرم confirmation لازم است، قبل از تغییر DB فرم نمایش بده و ورودی را اعتبارسنجی کن.
5. در transaction با lock/optimistic version record را تغییر بده.
6. rank را در همان transaction ذخیره کن؛ nextVersion و affected IDs ایجاد کن.
7. پس از commit رویداد audit/realtime را منتشر کن.
8. `ActionResult` استاندارد برگردان؛ در conflict، UI را با داده معتبر مجدداً بارگذاری کن.

**پایان باز و رفتار شکست:** cancel → هیچ تغییر DB؛ invalid form → حفظ ورودی؛ unauthorized → عدم افشای رکورد؛ unknown outcome → بررسی `operationStatus` پیش از retry؛ duplicate operation-id → عدم انجام دوباره.

### فرم اختصاصی هر مدل

Task فقط عنوان/سررسید لازم دارد؛ Ticket برای انتقال به «بسته» دلیل می‌خواهد؛ Order برای «تکمیل‌شده» باید پرداخت معتبر داشته باشد. بنابراین فرم و handler را از تعریف مدل بگیر، نه از یک فرم ثابت برای تمام منابع.

## 14. فیلتر و جست‌وجو

تنظیمات فیلتر از تعریف هر مدل استخراج شوند؛ در بردی با یک منبع، فیلتر انتخاب منبع مخفی باشد. برای برد چندمنبعی فیلتر منبع فقط از allowlist پر شود.

مثال فیلتر برد Task:

- `priority = high`
- `status in ['pending','doing']`
- `dueFrom`, `dueTo`
- `assignee = 12`
- `search = 'قرارداد'`

باید در SQL به query scoped افزوده شوند و با تغییر فیلتر cursor قبلی invalidate شود. هر فیلتر، type/allowed-operator/field mapping دقیق داشته باشد؛ `metadata` بدون اجازه مدل، SQL JSON عمومی و نامحدود نشود.

## 15. بارگذاری بهینه — اصل حیاتی عملکرد

### سناریو

۱۰ ستون و ۱۰۰ هزار تسک؛ هر ستون `25` کارت در بارگذاری نخست. هر ستون pagination مستقل دارد. `totalCount` تعداد واقعی است؛ `loadedCount` تعداد آیتم‌های حاضر در کلاینت.

### نیازمندی‌های عملی

- بدون `Task::all()`, `->get()` روی تمام رکوردها یا فیلتر PHP.
- `(stage, scope, rank, id)` یا معادل آن برای query SQL با index مناسب.
- `where scope → where allowed source → where stage → filters → ordering → limit`.
- `cursor` بر اساس `(sort_value, id)` تا کارت تکراری یا جاافتاده نشود.
- هر `load more` فقط stage مربوطه را درخواست کند.
- `listCards` باید با همان visibility/filters و pagination کار کند.
- شمارش‌ها با aggregate مستقل از page و بدون بارگذاری همه کارت‌ها.
- `with()` محدود برای assignee/tag و جلوگیری از N+1.
- cache اختیاری: key شامل board, scope, actor, allowlisted sources, queryHash, version؛ TTL کوتاه و invalidation بعد تغییرات.

### شاخص‌های قابل گزارش

- تعداد queries برای بارگذاری نخست برد ۱۰ ستونی.
- Peak memory و زمان درخواست با dataset 10k/100k.
- تعداد records hydrated از DB (باید محدود به page-size باشد).
- شمارش دقیق/تقریبی؛ در صورت approximate UI نباید عدد را قطعی نشان دهد.
- latency warm/cache و cold query.

**مهم:** در CI و محیط اجرای واقعی اندازه‌گیری شود؛ عدد performance را از روی حدس نباید PASS اعلام کرد.

### ایندکس‌های پیشنهادی (فقط میزبان)

برای Task با ستون‌های نمونه:

- `INDEX(user_id, status, sort_rank, id)` برای نمایش و مرتب‌سازی scoped.
- `INDEX(user_id, due_at, id)` اگر فیلتر/مرتب‌سازی سررسید رایج است.
- برای search متن از full-text مناسب موتور DB یا search backend استفاده شود؛ LIKE بدون index را معیار کارایی نهایی فرض نکن.

این‌ها **پیشنهاد مشروط** هستند؛ ساختار نهایی به DB و workload واقعی بستگی دارد. پکیج نباید Migration چنین ایندکس‌هایی را به همه پروژه‌ها تحمیل کند.

## 16. مرتب‌سازی مقاوم برای بردهای بزرگ

هدف: جابه‌جایی یک کارت غالباً مستلزم تغییر همه کارت‌های همان ستون نباشد.

راه پیشنهادی `rank` قابل مرتب‌سازی است. می‌توان از روش fractional/LexoRank یا sparse numeric استفاده کرد؛ گزینه انتخاب‌شده و نحوه rebalance در docs فنی توضیح داده شود.

- هنگام move، `beforeCardId` و `afterCardId` معتبر و در stage مقصد باشند.
- رتبه جدید بین همسایه‌ها ساخته شود؛ اگر فضا نبود، rebalance محدود و transaction-safe.
- `expectedVersion` از overwrite تغییر هم‌زمان جلوگیری کند.
- برای sort بر مبنای due/title، drag-reorder می‌تواند غیرفعال شود؛ UI باید علت را نشان دهد.
- اگر مدل rank ندارد، sort پیش‌فرض مثلاً `createdAt` قابل استفاده است، اما manual reorder بدون storage adapter «فعال» اعلام نشود.
- در برد multi-model، cross-source rank فقط با adapter مشترک یا ذخیره مرتبه جداگانه مجاز؛ در غیر این صورت disabled + پیام واضح.

## 17. معیارهای ستون و WIP

علاوه بر `totalCount/loadedCount`، میزبان بتواند `sum`, `avg`, `min`, `max` یا معیار سفارشی با `ColumnMetricsProvider` بدهد.

مثال CRM:

| ستون | تعداد | مجموع ارزش معاملات | WIP |
|---|---:|---:|---:|
| جدید | 50 | 500 میلیون | نامحدود |
| مذاکره | 18 | 320 میلیون | 20 |
| قرارداد | 9 | 210 میلیون | 10 |

مجموع‌ها فقط از SQL و در scope مجاز. اگر `loaded=25` ولی `total=50`، sum فقط روی 25 کارت **اشتباه** است. هنگام دو انتقال هم‌زمان برای آخرین slot WIP سخت باید فقط یکی ثبت شود.

## 18. Swimlane یا گروه‌بندی ردیفی [جدید]

برد معمولی ستون‌های وضعیت دارد؛ Swimlane یک بعد دوم به شکل ردیف اضافه می‌کند.

مثال: ستون‌ها `pending/doing/done` و ردیف‌ها `Team A`, `Team B`.

- مقدار گروه از تعریف مدل یا BoardFactory تعیین شود.
- group filter مثل بقیه در SQL اعمال شود.
- cross-lane drag ممکن است owner/team را تغییر دهد؛ فقط اگر Handler و permission آن را مجاز کنند.
- دسترسی به گروه نباید از عنوان گروه یا countها اطلاعات خصوصی لو دهد.
- افزایش بی‌حد groupها ممنوع؛ حد، pagination/lazy load و empty state لازم است.

## 19. نمایش کارت‌ها و اطلاعات دامنه‌ای

`CardData` فعلی badge، assignee، attachment، metadata، actions و permissions دارد. قرارداد جدید از این قابلیت‌ها استفاده کند؛ DTOهای موازی نساز.

Card می‌تواند نشان دهد: عنوان، متن، مسئول، عکس، اولویت، برچسب، موعد، تعداد کامنت، پیشرفت چک‌لیست، وضعیت وابستگی، عملیات اختصاصی. ذخیره کامنت/فایل/تیکت/چک‌لیست در میزبان است.

هر کارت از طریق `cardView()` موجود می‌تواند View اختصاصی داشته باشد؛ همان `ViewResolver` موجود را گسترش بده، نه یک موتور نمایش دیگر. HTML باید escape و URL sanitize شود.

## 20. سه تجربه نمایش

- **Board:** ستون‌ها و کارت‌ها، drag/reorder، counts، metrics و Swimlane.
- **List:** جست‌وجو و فیلتر با همان قرارداد داده، bulk و صفحه‌بندی.
- **Compact board [جدید]:** ویجت محدود برای داشبورد، بدون لود کل برد و بدون مجبورکردن UI به تقلید از Full Board.

ظاهر: theme `light/dark/system`، RTL/LTR، density، column width، focus و keyboard navigation. چند برد در یک صفحه باید `instanceId` مجزا داشته باشند. Asset فقط یک بار بارگذاری شود.

### مثال کانفیگ برد فارسی

```php
->settings([
    'views' => [
        'default' => 'board',
        'allowed' => ['board','list'],
        'column_width' => '19rem',
        'density' => 'comfortable',
    ],
    'appearance' => ['direction' => 'rtl', 'theme' => 'system'],
    'pagination' => ['page_size' => 25, 'max_page_size' => 100],
    'features' => ['search' => true, 'bulk' => true, 'realtime' => false],
])
```

`pagination.max_page_size` باید با سقف config global سازگار باشد؛ کلیدهای اضافه‌شده توسط Agent باید در `KanbanSettings::fromArray()` پشتیبانی شوند.

## 21. Realtime، Cache و Conflict

در کد فعلی Realtime اختیاری است و `default=false` دارد. برای فعال‌کردن:

1. Laravel Broadcasting و Driver (مثل Reverb) را در میزبان آماده کن.
2. مجوز private channel متناسب با scope/board را تأیید کن.
3. `realtime.enabled=true` در تنظیم برد و config لازم شود.
4. رویداد domain پس از commit منتشر شود تا کارت هنوز ذخیره‌نشده اعلام نشود.
5. `operationId/newVersion` برای dedupe و stale-result handling به کار رود.

**تست مهم:** دو کاربر در دو scope با boardKey مشابه؛ رویداد هیچ‌یک نباید برای دیگری نمایش داده شود یا count حساس را افشا کند.

Cache برای metrics/counts و query window اختیاری است؛ بدون invalidation معتبر، خاموش بماند. حافظه state کاربر هرگز در singleton registry نگهداری نشود.

## 22. اتصال همزمان Task به Kanban و Calendar

یک مدل Task می‌تواند هر دو Trait را داشته باشد:

```php
final class Task extends Model
{
    use \Karnoweb\Kanban\Concerns\HasKanbanCards;
    use \Karnoweb\Calendar\Concerns\HasCalendarEvents; // مسیر دقیق فقط در پکیج Calendar نهایی تأیید شود

    // kanbanDefinition() + calendarDefinition() هرکدام برای UI خود
}
```

**مهم:** مسیر کلاس Calendar در کد این ZIP موجود نیست و فقط مثال مفهومی از پکیج دیگر است؛ قبل از اجرا namespace واقعی آن را بررسی کن. هر دو پکیج از یک جدول Task استفاده کنند، ولی هیچ package اجباری به دیگری وابسته نباشد.

## 23. مثال‌های ملموس چندماژولی

### 23.1 تسک شخصی

- `Task` با Trait و `visibleQuery` روی user.
- برد `tasks.board` فقط `tasks`.
- ستون pending/doing/done؛ WIP در doing.
- create inline، move، dueDate، complete action.

### 23.2 CRM

- مدل `Deal`، source `deals`، فیلد stage=`sales_stage`, title=`customer_name`, metric=`amount`.
- visibility بر اساس تیم فروش و مجوز مسئول.
- pending→qualified→proposal→won/lost؛ انتقال won نیازمند confirmation.
- `ColumnMetricsProvider` مجموع مبلغ معاملات هر مرحله را SQL-side برگرداند.

### 23.3 تیکتینگ

- source `tickets`، عنوان `subject`, stage=`state`, dueAt=`sla_deadline`.
- view detail link، badge اولویت و SLA.
- تغییر حالت `closed` نیازمند note و Policy.
- فیلترهای خودکار state, priority, assignee.

### 23.4 کلاس آموزشی

- `EducationalClass`, source `classes`, stage=`approval_state`.
- برد `classes.board` فقط کلاس‌ها را نشان دهد، حتی اگر Task هم Trait دارد.
- حرکت «تأیید» باید ظرفیت/مدرک موردنیاز را از سرویس دامنه بررسی کند.
- تاریخ کلاس در Calendar نیز قابل نمایش باشد، بدون تغییر مستقیم Kanban.

### 23.5 سفارش

- `Order` source `orders`, stage=`order_status`.
- «پرداخت‌شده» یا «تحویل‌شده» نباید با drag ساده و دورزدن سرویس سفارش انجام شود.
- ActionHandler میزبان نتیجه را به‌صورت `ActionResult` بازگرداند؛ UI فقط نتیجه را نمایش دهد.

## 24. مصرف پیشرفته بدون Trait [روش موجود]

در پروژه‌هایی که مدل Eloquent ندارند یا منابع API است، از همین قراردادهای واقعی موجود استفاده کن:

```php
interface DataProvider
{
    public function board(BoardContext $context): BoardData;
    public function stages(BoardContext $context, BoardQuery $query): array;
    public function stageCards(BoardContext $context, string $stageId, BoardQuery $query): PageResult;
    public function listCards(BoardContext $context, BoardQuery $query): PageResult;
    public function counts(BoardContext $context, BoardQuery $query): CountResult;
    public function card(BoardContext $context, string $cardId): ?CardData;
    public function archives(BoardContext $context, BoardQuery $query): PageResult;
    public function capabilities(BoardContext $context): array;
    public function operationStatus(BoardContext $context, string $operationId): ?ActionResult;
}
```

در `BoardFactory`:

```php
return \Karnoweb\Kanban\Facades\Kanban::init()
    ->key('external.board')
    ->board(new \Karnoweb\Kanban\Data\BoardData('external.board', 'برد خارجی', $context->scopeKey))
    ->provider(\App\Kanban\ExternalProvider::class)
    ->handler(\App\Kanban\ExternalHandler::class)
    ->build();
```

`ExternalProvider` باید تمام قراردادها را پیاده کند. `ExternalHandler` باید `execute()` را به نتیجه typed تبدیل کند. Handler بدون پشتیبانی write می‌تواند از `UnsupportedActionHandler` استفاده کند تا برد read-only بماند.

## 25. ساختار دیتابیس و Migration اختیاری

**پکیج هیچ جدول اجباری ندارد.** اما برای نمونه Task با قابلیت‌های `manualOrder`, optimistic locking و pagination ممکن است این‌ها در جدول میزبان مفید باشند:

| فیلد پیشنهادی | نوع متداول | ضرورت |
|---|---|---|
| `id` | شناسه مدل | لازم در مدل میزبان |
| `status` | enum/string | برای stage، قابل نگاشت به نام دیگر |
| `sort_rank` | string/decimal | فقط اگر مرتب‌سازی دستی فعال است |
| `version` | integer/string | فقط برای optimistic locking مقاوم |
| `user_id` | bigint/uuid | فقط در پروژه‌هایی که مالکیت از این مسیر است |
| `due_at` | datetime | اختیاری |

اگر میزبان schema متفاوتی دارد، با `kanbanDefinition()` نگاشت کند. شرط منفی مهم: schema قبلی نباید به‌اجبار migrate شود و rollback plan لازم است.

## 26. عیب‌یابی

| علامت | علت‌های محتمل | راه تشخیص و اقدام |
|---|---|---|
| برد خالی | مدل ثبت نشده، source allowlist خالی، visibility scope رد کرده | registry، board definition و scoped count را بررسی کن |
| همه کارت‌های کاربران نشان داده شدند | `visibleQuery` اعمال نشده | خطای امنیتی بحرانی؛ deploy متوقف و تست row isolation اضافه کن |
| دکمه‌ها هستند ولی کار نمی‌کنند | ActionHandler یا feature guard/permission | نتیجه ActionResult و log سرور را بررسی کن |
| کارت جابه‌جا می‌شود ولی بازمی‌گردد | انتقال deny، WIP، stale version یا storage rank | messageCode و OperationId را در UI/log بررسی کن |
| کارت‌ها در صفحات تکرار می‌شوند | cursor/ordering ناپایدار | `(rank,id)` و signed cursor را تست کن |
| شمارش ستون اشتباه است | count براساس loaded cards | CountResult SQL-side و ACL scope را اصلاح کن |
| در دو برد رویدادها قاطی شدند | instanceId/Echo/DOM isolation | browser multi-board test اجرا کن |
| استایل‌ها نمی‌آیند | asset publish یا path مشکل دارد | network 200 روی `/vendor/kanban` و بارگذاری JS/CSS را چک کن |
| حافظه RAM بالا | Model::all/get بدون حد یا N+1 | query log، hydrating count و profiler را بررسی کن |
| یک مدل جدید ناخواسته در برد ظاهر شد | sources پیش‌فرض auto-all | نقض قرارداد؛ `sources([])` باید zero data باشد |

## 27. تست و اجرای اعتبارسنجی

دستورهای زیر **بعد از نصب dependencyها** باید در مخزن پکیج قابل اجرا باشند:

```bash
composer install
composer test
# یا:
./vendor/bin/phpunit --no-coverage

npm ci
npx playwright test
```

برای تست Playwright ممکن است web server/Testbench/Reverb نیاز باشد. با تنظیمات واقعی `playwright.config.mjs` و مستندات محیط پیش برو؛ PASS را بدون اجرای واقعی گزارش نکن.

حداقل coverage:

- تست Unit: mapping DTO، enum، registry و settings parser.
- Feature: visibility query، pagination، secure cursor، CRUD/move/bulk/WIP.
- Scenario: ۵۰ سناریوی تعریف‌شده در پرامپت و جفت MD فارسی.
- Browser: Touch/keyboard/RTL/theme/multi-board/drag/list/compact.
- Security: unauthorized reads/counts/actions، tampered id/cursor، cross tenant.
- Performance: 10k/100k و گزارش query/latency/memory.
- Integration: Eloquent + SQLite و در صورت امکان MySQL/PostgreSQL، Reverb two-user.

## 28. سازگاری با استفاده‌کنندگان قبلی و مسیر ارتقا

**اصل:** `Kanban::init`, `BoardFactory`, `BoardRegistry`, `DataProvider`, `ActionHandler`, `BoardContext`, `ActionCommand`, `ActionResult`, `<livewire:kanban-board ...>` تغییر مخرب نکنند.

- Factory و provider قدیمی بدون افزودن Trait همچنان کار کند.
- اگر Trait اضافه شد ولی `sources()` به برد اضافه نشده، رفتار قبلی برد تغییر نکند و منابع جدید ناخواسته نمایش داده نشوند.
- migration package ممنوع؛ نسخه جدید config با merge و مقدار پیش‌فرض safe کار کند.
- تغییر فیلدهای rank/version در اپ میزبان اختیاری، مستند و برگشت‌پذیر باشد.
- `composer.json`، PHP، Laravel، Livewire و asset pipeline در CI کنترل شوند.
- تغییر نام/namespace باید deprecated shim و upgrade note داشته باشد.
- نمونه‌های راهنما باید با tests/examples واقعی در مخزن اجرا شوند.

## 29. چک‌لیست نهایی انتشار

- [ ] Trait + یک متد `kanbanDefinition()` برای Task نمونه در برنامه واقعی کار می‌کند.
- [ ] Task/Ticket/Class همگی قابل ثبت هستند و هر برد فقط source مجاز خود را لود می‌کند.
- [ ] ثبت مدل جدید بدون دسترسی برد سبب اضافه‌شدن کارت به هیچ برد قدیمی نمی‌شود.
- [ ] query visibility در SQL قبل از pagination/count اعمال می‌شود.
- [ ] cursor امن و وابسته به query/scope است؛ 100k کارت بدون کلّی‌خوانی قابل استفاده‌اند.
- [ ] order/rank و WIP زیر concurrency درست‌اند.
- [ ] Enum transitions، custom form، domain actions و audit afterCommit کار می‌کنند.
- [ ] Column metrics، swimlanes، کارت‌های غنی، Board/List/compact کار می‌کنند.
- [ ] اتصال اختیاری به Calendar نیازی به dependency مستقیم ندارد.
- [ ] RTL/LTR، Dark/Light، touch/keyboard و چند برد در یک صفحه تست شده‌اند.
- [ ] تمام ۵۰ سناریو، تست‌های قبلی و browser suite با اعداد واقعی پاس شده‌اند.
- [ ] همه تنظیمات جدید و قدیمی با مثال عملی پوشش دارند و هیچ API «پیشنهادی» بدون پیاده‌سازی در README باقی نمانده است.
- [ ] گزارش امنیت و کارایی و محدودیت‌های محیط به همراه CHANGELOG/UPGRADE آماده است.

---

**نقطه شروع برای توسعه‌دهنده:** اول `ScopeResolver` را به‌صورت معتبر bind کن؛ سپس مدل را با Trait و `kanbanDefinition()` آماده کن؛ بعد source را ثبت و برای BoardFactory با `sources()` انتخاب کن؛ نهایتاً برد را با `<livewire:kanban-board board-key="tasks.board" />` نمایش بده. برای تست و استقرار سازمانی، معیارهای مرحله ۵ و چک‌لیست آخر سند الزامی‌اند.

# برد شخصی با مدل Eloquent

## هدف و پیش‌نیازها

بردی بسازیم که هر کاربر فقط کارت‌های خودش را ببیند و تغییر دهد. کدهای `App` و جدول این راهنما متعلق به **پروژهٔ میزبان** هستند. پیش‌نیاز: Laravel 13، PHP 8.3 یا 8.4، Livewire 4، نصب [فایل‌های ظاهری](../02-installation-assets.md)، پایگاه دادهٔ آماده و ورود کاربر در میزبان. شناسهٔ کاربر در این مثال عددی است؛ برای UUID نوع `user_id` را مطابق کلید میزبان انتخاب کنید.

تمام دستورها از ریشهٔ پروژهٔ میزبان اجرا می‌شوند. این راهنما جدول مستقل `kanban_tasks` ایجاد می‌کند؛ برای جدول موجود از migration زیر استفاده نکنید و نگاشت فیلدها را مطابق [تعریف مدل](../21-trait-eloquent-guide.md) انجام دهید.

## ۱. جدول

فایل `database/migrations/2026_01_01_000001_create_kanban_tasks_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kanban_tasks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title', 300);
            $table->string('status')->default('todo');
            $table->decimal('sort_rank', 20, 6)->nullable();
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'sort_rank', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_tasks');
    }
};
```

```bash
php artisan migrate
```

جدول `kanban_tasks` باید ایجاد شود. این دستور فقط migration میزبان را اجرا می‌کند؛ پکیج جدول کاربران، نظرها یا فایل‌ها ایجاد نمی‌کند.

## ۲. مدل

فایل `app/Models/KanbanTask.php`:

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Karnoweb\Kanban\Concerns\HasKanbanCards;
use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Models\KanbanModelDefinition;

final class KanbanTask extends Model
{
    use HasKanbanCards;

    protected $guarded = ['id', 'user_id', 'version'];

    public static function kanbanDefinition(): KanbanModelDefinition
    {
        return KanbanModelDefinition::make('tasks', self::class)
            ->fields(['stage' => 'status', 'rank' => 'sort_rank', 'version' => 'version',
                'createdAt' => 'created_at', 'archivedAt' => 'archived_at'])
            ->stages(['todo' => 'To do', 'doing' => ['title' => 'Doing', 'wipLimit' => 3], 'done' => 'Done'])
            ->visibleQuery(fn (Builder $q, BoardContext $c) => $q->where('user_id', $c->actorId))
            ->authorize(fn (string $ability, ?Model $record, BoardContext $c): bool =>
                $record === null ? $ability === 'create' :
                ((string) $record->user_id === $c->actorId &&
                    in_array($ability, ['view', 'update', 'move', 'reorder', 'archive', 'restore'], true)))
            ->transitions(['todo' => ['doing'], 'doing' => ['todo', 'done'], 'done' => ['doing']])
            ->sorts(['manualOrder', 'title', 'createdAt'])
            ->searchable(['title'])
            ->rules(['title' => ['required', 'string', 'max:300']])
            ->hook('before', function (Model $record, ActionCommand $command, BoardContext $c): void {
                if (! $record->exists) {
                    $record->user_id = $c->actorId;
                }
            });
    }
}
```

مجوز ایجاد جداست چون هنگام ایجاد هنوز رکورد وجود ندارد. مالکیت در hook سمت سرور تعیین می‌شود؛ فرم اجازهٔ انتخاب مالک ندارد. ستون نسخه برای تشخیص ویرایش هم‌زمان و ستون رتبه برای جابه‌جایی دستی است. انتقال `todo` به `done` در این تعریف مجاز نیست.

## ۳. محدوده و هویت

فایل `app/Kanban/AppScopeResolver.php`:

```php
<?php
namespace App\Kanban;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Karnoweb\Kanban\Contracts\ScopeResolver;
use Karnoweb\Kanban\Data\BoardContext;

final class AppScopeResolver implements ScopeResolver
{
    public function resolve(string $boardKey, ?string $instanceId = null): BoardContext
    {
        abort_unless($boardKey === 'tasks.board', 403);
        $actor = Auth::user();
        abort_unless($actor !== null, 403);
        $id = (string) $actor->getAuthIdentifier();

        return new BoardContext($boardKey, $id, $id, $instanceId ?? (string) Str::uuid());
    }
}
```

محدوده در این مثال همان شناسهٔ کاربر است. برای سازمان مشترک، عضویت سازمان باید از دادهٔ قابل اعتماد سمت سرور بررسی شود؛ مقدار ارسالی مرورگر کافی نیست.

## ۴. تعریف برد

فایل `app/Kanban/TaskBoardFactory.php`:

```php
<?php
namespace App\Kanban;

use Karnoweb\Kanban\Contracts\BoardFactory;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardData;
use Karnoweb\Kanban\Data\BoardDefinition;
use Karnoweb\Kanban\Support\BoardBuilder;

final class TaskBoardFactory implements BoardFactory
{
    public function make(BoardContext $context): BoardDefinition
    {
        return (new BoardBuilder)
            ->key('tasks.board')
            ->board(new BoardData('tasks.board', 'My tasks', $context->scopeKey))
            ->sources(['tasks'])
            ->settings([
                'features' => ['create_stage' => false, 'update_stage' => false,
                    'archive_stage' => false, 'reorder_stages' => false, 'delete_card' => false],
                'interaction' => ['transition_policy' => 'allow'],
                'views' => ['detail_surface' => 'drawer', 'column_width' => '21rem'],
            ])
            ->build();
    }
}
```

مجوز انتقال در دو سطح بررسی می‌شود: سیاست برد و فهرست انتقال‌های مدل. `allow` در برد، مجوز مدل را دور نمی‌زند. ستون Doing حداکثر سه کارت فعال می‌پذیرد. مراحل مدل ثابت‌اند؛ عملیات ساخت/ویرایش ستون در adapter مدل پشتیبانی نمی‌شود.

## ۵. ثبت در برنامه

در `app/Providers/AppServiceProvider.php` متدهای زیر را با ثبت‌های موجود ادغام کنید؛ یک منبع یا برد را دوبار ثبت نکنید:

```php
<?php
namespace App\Providers;

use App\Kanban\AppScopeResolver;
use App\Kanban\TaskBoardFactory;
use App\Models\KanbanTask;
use Illuminate\Support\ServiceProvider;
use Karnoweb\Kanban\Contracts\ScopeResolver;
use Karnoweb\Kanban\Models\KanbanModelRegistry;
use Karnoweb\Kanban\Support\BoardRegistry;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ScopeResolver::class, AppScopeResolver::class);
    }

    public function boot(BoardRegistry $boards, KanbanModelRegistry $models): void
    {
        $models->register('tasks', KanbanTask::class);
        $boards->register('tasks.board', TaskBoardFactory::class);
    }
}
```

این Provider در Laravel تازه‌نصب‌شده از قبل در `bootstrap/providers.php` ثبت است. اگر پروژه آن را حذف کرده، `App\Providers\AppServiceProvider::class` را به همان آرایه اضافه کنید.

## ۶. صفحه و مسیر

فایل `resources/views/tasks.blade.php`، با روش CSS مستقل:

```blade
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My tasks</title>
    <link rel="stylesheet" href="{{ asset('vendor/kanban/kanban.css') }}">
    @livewireStyles
</head>
<body>
    <main style="height: calc(100dvh - 2rem); margin: 1rem">
        <livewire:kanban-board board-key="tasks.board" />
    </main>
    @livewireScripts
    <script src="{{ asset('vendor/kanban/kanban.js') }}" defer></script>
</body>
</html>
```

برای پروژهٔ دارای DaisyUI از روش import نصب استفاده کنید و stylesheet مستقل را از این صفحه بردارید.

این خط را به `routes/web.php` اضافه کنید:

```php
\Illuminate\Support\Facades\Route::view('/tasks', 'tasks')->middleware('auth');
```

میزبان باید مسیر `login` و ورود کاربر را از قبل فراهم کرده باشد. پکیج سامانهٔ ورود نصب نمی‌کند.

## ۷. ذخیرهٔ نتیجهٔ عملیات بین درخواست‌ها

نوشتن مدل به cache مشترک با پشتیبانی قفل نیاز دارد. برای اجرای محلی تک‌سرور، اگر cache database پیش‌فرض میزبان آماده است از همان استفاده کنید؛ برای چند worker از Redis مشترک استفاده کنید. نمونهٔ `.env` برای Redis نصب‌شده:

```dotenv
CACHE_STORE=redis
```

```bash
php artisan config:clear
php artisan serve
```

store حافظه‌ای `array` بیرون از تست برای نوشتن رد می‌شود. جدول cache در صورت انتخاب database مسئولیت میزبان است. نتیجه و وضعیت درحال‌انجام عملیات در cache نگه‌داری می‌شوند؛ حذف cache تضمین جلوگیری از اجرای تکراری را از بین می‌برد. قرارداد پایدار اختصاصی: [عملیات و handler](../06-handler-actions.md).

## ۸. بررسی نتیجه

با یک کاربر وارد شوید و `/tasks` را باز کنید. ابتدا سه ستون خالی دیده می‌شود. یک کارت بسازید، نامش را ویرایش کنید و از To do به Doing ببرید؛ بازخوانی صفحه باید تغییر را حفظ کند. کارت را آرشیو و از بخش آرشیو بازگردانید.

آزمون شکست: انتقال مستقیم To do به Done باید رد شود. چهارمین کارت در Doing باید با محدودیت ظرفیت رد شود. با کاربر دوم، کارت‌های کاربر اول نباید دیده شوند. در دو تب، پس از تغییر نسخه در تب اول، عملیات با نسخهٔ قدیمی در تب دوم باید conflict بدهد، نه بازنویسی خاموش.

برای مشاهدهٔ دادهٔ ذخیره‌شده، در `php artisan tinker` اجرا کنید:

```php
\App\Models\KanbanTask::query()->get(['id', 'user_id', 'title', 'status', 'version']);
```

این query ابزار بررسی محلی مدیر است و جایگزین query محدودشدهٔ رابط کاربری نیست.

## خطاهای مرتبط

| نشانه | علت و اقدام |
| --- | --- |
| برد ناشناخته | ثبت `tasks.board` و تطابق key در Factory و resolver را بررسی کنید. |
| کارت‌ها خالی‌اند | `user_id` داده با actor فعلی برابر نیست یا `visibleQuery()` تنظیم نشده است. |
| `shared_cache_required` | store `array` را با store مشترک قفل‌دار جایگزین و config cache را پاک کنید. |
| `transition_denied` | مقصد باید هم در مدل و هم در سیاست برد مجاز باشد. |
| `unknown_outcome` | ممکن است commit انجام شده باشد؛ وضعیت همان operationId را بررسی کنید، عملیات جدید نسازید. |
| کارت بدون ظاهر | انتشار assets و پاسخ ۲۰۰ فایل‌های CSS و JavaScript را در مرورگر بررسی کنید. |

رفتار adapter با تست‌های مجموعهٔ Feature و Security مخزن پوشش داده می‌شود؛ دستورهای اجرا در [راهنمای تست](../../README_Test.md) است. این راهنما ایجاد سامانهٔ ورود، نصب Redis یا سیاست سازمانی اختصاصی میزبان را انجام نمی‌دهد.

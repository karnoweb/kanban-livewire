# اتصال مدل با یک تعریف مرکزی

مدل باید Eloquent (مدل پایگاه داده لاراول) باشد و `HasKanbanCards` را داشته باشد. نمونه‌های اجرایی در `workbench/app/Models/KanbanTask.php`، `KanbanTicket.php` و `KanbanEducationalClass.php` هستند.

```php
use Illuminate\Database\Eloquent\Builder;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Models\KanbanModelDefinition;

public static function kanbanDefinition(): KanbanModelDefinition
{
    return KanbanModelDefinition::make('tasks', static::class)
        ->fields(['stage' => 'status'])
        ->stages(['todo' => 'انجام‌نشده', 'done' => 'انجام‌شده'])
        ->visibleQuery(fn (Builder $q, BoardContext $c) => $q->where('user_id', $c->actorId))
        ->authorize(fn ($ability, $record, BoardContext $c) => $record !== null && (string) $record->user_id === $c->actorId)
        ->transitions(['todo' => ['done']]);
}
```

این مثال فقط ردیف‌های کاربر جاری را می‌خواند. ایجاد به‌صورت پیش‌فرض ممنوع است؛ برای ایجاد، مجوز `create` و hook فاز `before` جهت تعیین مالکیت لازم است؛ نمونه Task اجرایی این دو را دارد. هیچ ستون مالکیت، رتبه یا نسخه به میزبان تحمیل نمی‌شود.

در boot برنامه:

```php
app(\Karnoweb\Kanban\Models\KanbanModelRegistry::class)->register('tasks', \App\Models\Task::class);
```

در Factory موجود، `->sources(['tasks'])` را قبل از `build()` بگذارید. اگر provider دستی ندهید، تأمین‌کننده و handler مدل انتخاب می‌شوند. `->sources([])` داده صفر می‌دهد. افزودن Trait به‌تنهایی هیچ بردی را تغییر نمی‌دهد.

`ScopeResolver` همچنان لازم است و باید هویت معتبر سرور را بسازد. تعریف مدل و Closureها فقط در اجرای سرور ساخته می‌شوند؛ رجیستری فقط نام کلاس نگه می‌دارد. برای metadata، badge و assignee از `present()` استفاده کنید. رابطه‌ها را با `with()` اعلام کنید؛ بارگذاری تنبل رابطه‌ها هنگام ساخت کارت ممنوع است.

فرم و کارت سفارشی با `formView('app.task-form')` و `cardView('app.task-card')` قابل تعیین‌اند. داده فرم در `cardForm` و خطاها در `cardErrors` است؛ ثبت از `submitCardForm` عبور می‌کند. قواعد با `rules()` و فیلدهای استاندارد نگاشت‌شده کنترل می‌شوند؛ فیلدهای دامنه را در hook/handler میزبان ذخیره کنید.

برای اجرای نمونه‌ها:

```sh
php vendor/bin/testbench migrate --force
php vendor/bin/testbench db:seed --class="Workbench\Database\Seeders\KanbanDemoSeeder" --force
php vendor/bin/testbench serve --host=127.0.0.1 --port=8000
```

پس از ورود `/login-as/alice`، صفحات `/models/tasks`، `/models/tickets` و `/models/classes` را باز کنید. migration این نمونه فقط متعلق به workbench است، نه نصب پکیج در میزبان.

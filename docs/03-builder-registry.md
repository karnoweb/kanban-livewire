# Factory، Builder و ثبت برد

`BoardFactory::make(BoardContext $context): BoardDefinition` در هر درخواست از سرور اجرا می‌شود. Closure یا نمونهٔ Builder را در state عمومی Livewire ذخیره نکنید. نمونهٔ کامل چهار فایل میزبان در [اتصال مدل](guides/host-task-board.md) است.

## انتخاب مسیر داده

| تعریف Builder | Provider پیش‌فرض | handler پیش‌فرض |
| --- | --- | --- |
| stages/cards بدون sources | InlineDataProvider | UnsupportedActionHandler؛ فقط‌خواندنی |
| sources صریح، بدون provider دستی | EloquentKanbanDataProvider | EloquentKanbanActionHandler |
| provider دستی | کلاس تعیین‌شده | UnsupportedActionHandler، مگر handler صریح بدهید |
| sources([]) | adapter مدل با منبع صفر | هیچ مدل ثبت‌شده‌ای ضمنی انتخاب نمی‌شود |

فراخوانی `settings()` یک تنظیم کامل مبتنی بر defaults می‌سازد؛ چند فراخوانی آن با هم جمع نمی‌شوند. نام کلاس handler باید `ActionHandler` و provider باید `DataProvider` را پیاده کند. `onMove()` و `onClick()` با نام کلاس به handler متصل می‌شوند؛ Closure رد می‌شود.

در `AppServiceProvider::boot()`:

```php
public function boot(
    KarnowebKanbanSupportBoardRegistry $boards,
): void {
    $boards->register('tasks.board', AppKanbanTaskBoardFactory::class);
}
```

کلید تکراری یا Factory بدون قرارداد، `InvalidBoardDefinition` می‌دهد. resolve کلید ثبت‌نشده یا context متعلق به برد دیگر، `UnknownBoard` می‌دهد. مسیر فایل و کد TaskBoardFactory در راهنمای اتصال آمده است.

## استفادهٔ سرور بدون رابط کاربری

در سرویس میزبان پس از احراز هویت:

```php
$context = app(KarnowebKanbanContractsScopeResolver::class)->resolve('tasks.board');
$definition = app(KarnowebKanbanSupportBoardRegistry::class)
    ->resolve('tasks.board', $context);
$provider = app($definition->providerClass);
```

برای adapter مدل این ساخت Provider کافی است. Provider inline باید با `board`, `stages`, `cards` تعریف ساخته شود؛ نمونهٔ [Provider](05-data-provider.md) را ببینید. خود Registry داده یا عملیات را اجرا نمی‌کند.

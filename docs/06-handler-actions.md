# نوشتن، handler و نتیجهٔ عملیات

## انتخاب مسیر

برای جدول ساده از adapter مدل [راهنمای اتصال](guides/host-task-board.md) استفاده کنید. برای عملیات دامنه مثل تأیید سفارش یا تغییر مرحله با اثر مالی، کلاس میزبان باید `ActionHandler::execute(ActionCommand $command, BoardContext $context): ActionResult` را پیاده کند و با `handler()` ثبت شود. پکیج سرویس سفارش یا CRM ایجاد نمی‌کند.

## اجرای مستقیم adapter مدل

این کد در متد سرویس احرازشدهٔ میزبان، پس از تکمیل راهنمای اتصال قابل اجراست:

```php
$context = app(KarnowebKanbanContractsScopeResolver::class)->resolve('tasks.board');
$definition = app(KarnowebKanbanSupportBoardRegistry::class)->resolve('tasks.board', $context);
$operationId = (string) IlluminateSupportStr::uuid();
$command = new KarnowebKanbanDataActionCommand(
    operationId: $operationId,
    type: KarnowebKanbanEnumsActionType::CreateCard,
    payload: ['source' => 'tasks', 'stageId' => 'todo', 'title' => 'Review contract'],
);
$result = app($definition->handlerClass)->execute($command, $context);
```

`success` باید شناسهٔ دارای source مثل `tasks:17` و نسخهٔ تازه بدهد. در retry همان command و operationId را نگه دارید؛ UUID تازه برای عملیات نامعلوم نسازید. تغییر payload با همان شناسه، conflict است.

## نتیجه و خطا

| status | اقدام مصرف‌کننده |
| --- | --- |
| success | affectedIds و newVersion را اعمال/داده را تازه کنید |
| validation_error | fieldErrors را کنار فیلدها نمایش دهید؛ draft را نگه دارید |
| forbidden / not_found | دسترسی یا رکورد معتبر نیست؛ عملیات را تکرار کور نکنید |
| conflict | رکورد و نسخه را دوباره بخوانید و تصمیم کاربر را بگیرید |
| wip_limit | مقصد پر است؛ وضعیت قبلی کارت حفظ می‌شود |
| domain_rejected | سرویس دامنه رد کرده؛ messageCode را بررسی کنید |
| rate_limited / retryable_error | پس از رفع محدودیت با شناسهٔ همان عملیات ادامه دهید |
| unknown_outcome | ممکن است ثبت انجام شده باشد؛ operationStatus همان شناسه را بخوانید |
| unsupported | قرارداد/قابلیت برای این منبع وجود ندارد |

همهٔ مقادیر دقیق در [Enumها](api/enums.md) و سازندهٔ ActionResult در [مرجع داده](api/data.md) آمده‌اند. safeMessage باید متن امن باشد؛ exception یا دادهٔ حساس را به کاربر نفرستید.

## الزامات adapter مدل

- cache مشترک با قفل لازم است؛ store array بیرون تست رد می‌شود.
- handler باید مالک commit باشد؛ اجرای آن در تراکنش بیرونی با `outer_transaction_not_supported` رد می‌شود.
- نسخهٔ مورد انتظار برای نوشتن روی مدل versionدار کنترل می‌شود. از version دادهٔ تازهٔ Provider استفاده کنید.
- نگاشت archivedAt برای آرشیو/بازگردانی لازم است؛ CRUD ستون مدل پشتیبانی نمی‌شود.
- وضعیت pending و نتیجهٔ عملیات پیش‌فرض در cache بدون انقضای خودکار ذخیره می‌شود. نگه‌داری و پاک‌سازی آن باید با زمان امن retry میزبان سازگار باشد. برای ثبت دائمی قابل ممیزی، `Contracts\ModelOperationStore` را با ذخیرهٔ اختصاصی جایگزین کنید.

handler اختصاصی باید مجوز، محدوده، نسخه، WIP و جلوگیری از اجرای تکراری را در مرز تراکنش خودش انجام دهد؛ UI به‌تنهایی ظرفیت یا سیاست دامنه را تضمین نمی‌کند. رویداد و اثر خارجی فقط پس از commit؛ [نمونهٔ اتصال رویداد](15-events-observability.md).

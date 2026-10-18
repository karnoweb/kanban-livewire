# خواندن داده با Provider اختصاصی

## هدف

برای API خارجی یا پایگاه داده‌ای که adapter مدل کافی نیست، `KarnowebKanbanContractsDataProvider` را پیاده کنید. [قرارداد کامل و امضاها](api/contracts.md) و [نوع دادهٔ خروجی](api/data.md) مرجع هستند. برای Eloquent معمولی نیازی به پیاده‌سازی این قرارداد نیست؛ [اتصال مدل](guides/host-task-board.md) آن را آماده می‌کند.

## اجرای query سرور

بعد از ثبت برد راهنمای اتصال، در `php artisan tinker` با هویت معتبر یا داخل سرویس احرازشده اجرا کنید. نمونهٔ زیر متعلق به متد سرویس میزبان است:

```php
$context = app(KarnowebKanbanContractsScopeResolver::class)->resolve('tasks.board');
$definition = app(KarnowebKanbanSupportBoardRegistry::class)->resolve('tasks.board', $context);
$provider = $definition->providerClass === KarnowebKanbanProvidersInlineDataProvider::class
    ? new KarnowebKanbanProvidersInlineDataProvider(
        $definition->board, $definition->inlineStages, $definition->inlineCards)
    : app($definition->providerClass);
$query = new KarnowebKanbanDataBoardQuery(view: 'list', limit: 25, sort: 'title');
$page = $provider->listCards($context, $query);
$next = $page->hasMore
    ? $provider->listCards($context, $query->withCursor($page->nextCursor))
    : null;
```

خروجی `PageResult` است؛ items فقط CardDataهای مجاز، حداکثر اندازهٔ صفحه است. `$next=null` یعنی صفحهٔ بعد وجود ندارد. شمارش را از `counts()` بخوانید، نه `count($page->items)`.

## مسئولیت پیاده‌ساز

| متد | خروجی و شرط |
| --- | --- |
| board/stages | هویت برد و ستون‌های قابل مشاهده در محدودهٔ context |
| stageCards/listCards | اعمال مجوز، search، filters، sort و cursor قبل از page limit |
| counts | کل دادهٔ مطابق query؛ countExact و null برای شمارش غیرقطعی |
| card | رکورد تازهٔ مجاز یا null؛ رکورد خارج از محدوده را افشا نکنید |
| archives | صفحهٔ آرشیو همان محدوده؛ نه تمام آرشیو سیستم |
| capabilities | نام قابلیت به bool؛ نمایش قابلیت مجوز نوشتن نیست |
| operationStatus | نتیجهٔ واقعی همان operationId یا null اگر ناشناخته |

cursor اختصاصی باید به برد، محدوده، query و کاربر وابسته و در برابر دستکاری محافظت‌شده باشد. برای نتیجهٔ ناشناختهٔ نوشتن از همان شناسه وضعیت بپرسید. نمونهٔ adapter خارجی بدون قرارداد API میزبان نمی‌تواند مستقل اجرا شود؛ این بخش قرارداد اجرایی است، نه یک HTTP API فرضی.

ثبت Provider در زنجیرهٔ Factory: `->provider(\App\Kanban\RemoteDataProvider::class)`؛ کلاس نام‌برده متعلق به میزبان است و باید تمام متدهای مرجع را پیاده کند. بدون handler صریح، نوشتن پشتیبانی نمی‌شود.

بررسی شکست: query با محدودهٔ کاربر دیگر نباید رکوردی برگرداند؛ cursor برد دیگر باید رد شود و مجموع نباید به تعداد صفحه کاهش یابد. دستورهای تست adapter و امنیت در [راهنمای تست](../README_Test.md) است.

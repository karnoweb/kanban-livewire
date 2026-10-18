# داده‌های نمایشی و تنظیمات برد

DTO (شیء انتقال داده) جای مدل Eloquent نیست؛ دادهٔ مجاز و محدود برای نمایش است. ساخت نمونهٔ کامل در [برد فقط‌خواندنی](guides/readonly-board.md) و تمام سازنده‌ها، نوع فیلد و پیش‌فرض‌ها در [مرجع داده](api/data.md) آمده است.

| داده | کاربرد | شرط مهم |
| --- | --- | --- |
| BoardContext | هویت درخواست | از resolver سمت سرور؛ هر چهار شناسه اجباری |
| BoardData | هویت و عنوان برد | scopeKey متعلق به context معتبر |
| StageData | ستون و ظرفیت | wipLimit منفی مجاز نیست؛ رنگ hex است |
| CardData | کارت | شناسه، برد، ستون، عنوان اجباری؛ متعلقات نوع‌دار |
| AttachmentData | فایل | نام/MIME/URL لازم؛ تصویر با MIME تصویر |
| PageResult | صفحهٔ کارت | hasMore به cursor نیاز دارد |
| CountResult | شمارش کل | مستقل از کارت‌های صفحهٔ جاری |
| ActionResult | نتیجهٔ نوشتن | unknown_outcome موفقیت نیست |

`CardData.comments` و `checklist` برای دادهٔ محدودند؛ تاریخچهٔ طولانی باید از `CardDetailsProvider` صفحه‌بندی شود. HTML در متن داده اجرا نمی‌شود. لینک فایل باید از مسیر دانلود مجاز میزبان بیاید؛ نگه‌داری فایل مسئولیت پکیج نیست.

تنظیمات با `KanbanSettings::fromArray()` یا `BoardBuilder::settings()` ساخته می‌شوند. [مرجع تنظیمات](CONFIGURATION.md) تفاوت کلیدهای سراسری و برد را مشخص می‌کند؛ `model.eloquent` مسیر صحیح آرایهٔ ورودی نیست. مقدار نامعتبر می‌تواند `InvalidArgumentException` یا `ValueError` بدهد.

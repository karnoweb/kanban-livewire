> به‌روزرسانی ظاهر: CSS منبع اکنون به DaisyUI 5 وابسته است. میزبان دارای DaisyUI باید آن را در CSS سراسری import کند و فایل مستقل dist را اضافه نکند. قرارداد پوسته، متغیرها و تمام انتخابگرها: [راهنمای سفارشی‌سازی](11-views-customization.md).

# ارتقا و بازگشت

امضاهای قبلی Builder، Factory، DataProvider، ActionHandler و مؤلفه `kanban-board` حفظ شده‌اند. بردی که `sources()` ندارد، همان provider/handler قبلی را می‌گیرد. برای ارتقای یک برد، ابتدا فقط همان برد را به منابع صریح متصل کنید؛ بردهای دیگر تغییر نمی‌کنند.

```php
// قبلی: بدون Trait همچنان معتبر است.
->provider(\App\Kanban\ExternalProvider::class)
->handler(\App\Kanban\ExternalHandler::class)

// مسیر مدل: provider/handler دستی را حذف و منبع ثبت‌شده را انتخاب کنید.
->sources(['tasks'])
```

هیچ migration از هسته منتشر نمی‌شود. مدل میزبان فقط فیلد عنوان/شناسه/وضعیت قابل نگاشت می‌خواهد. ستون rank برای ترتیب دستی، version عددی برای کنترل هم‌زمانی و archivedAt برای بایگانی عمومی، اختیاری‌اند. بدون این ستون‌ها، قابلیت مربوطه فعال اعلام نمی‌شود. جدول `demo_model_cards` فقط در workbench است.

```sh
php artisan vendor:publish --tag=kanban-config
php artisan vendor:publish --tag=kanban-assets --force
```

تنظیمات منتشرشده قدیمی را دستی با پیش‌فرض‌های جدید ادغام کنید؛ تنظیمات میزبان را با force بازنویسی نکنید. فایل‌های دارایی را یک بار وارد کنید؛ Livewire/Alpine/Echo دوباره نصب نشوند.

مسیر بازگشت: Factory قبلی و provider/handler قبلی را نگه دارید؛ در صورت نیاز همان Factory را برگردانید. ستون‌های افزوده‌شده میزبان را تا پایان بازگشت مصرف‌کنندگان حذف نکنید. برای نمونه workbench، migration جدید متد down دارد. نسخه یا tag انتشار در این تغییر ساخته نشده است.

پیش‌نیاز: PHP ^8.3، Laravel ^13، Livewire ^4. اندازه‌گیری فعلی با PHP 8.4/SQLite/Windows و Edge است. بررسی PHP 8.3/8.4 در CI پیکربندی شده؛ موفقیت اجرای CI روی سرویس خارجی از اجرای محلی نتیجه‌گیری نمی‌شود.

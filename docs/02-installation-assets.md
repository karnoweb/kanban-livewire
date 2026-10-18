# نصب و فایل‌های ظاهری

## پیش‌نیاز

Composer metadata نسخهٔ فعلی: PHP `^8.3`، Laravel (`illuminate/support`) `^13.0`، Livewire `^4.0` و `morilog/jalali:^3.5`. نصب jalali با نصب پکیج انجام می‌شود. آزمون خودکار مخزن PHP 8.3 و 8.4 را هدف می‌گیرد؛ نسخه‌های جدیدترِ مجاز Composer الزاماً در این محیط اجرا نشده‌اند.

## نصب در ریشهٔ میزبان

```bash
composer require karnoweb/laravel-livewire-kanban
php artisan vendor:publish --tag=kanban-assets
```

در `composer.json` پکیج، کشف خودکار `KarnowebKanbanKanbanServiceProvider` و alias با نام `Kanban` ثبت شده است. اگر میزبان auto-discovery را غیرفعال کرده، این کلاس را در آرایهٔ `bootstrap/providers.php` اضافه کنید. Provider جدول یا route دامنه نصب نمی‌کند.

انتشار اختیاری:

```bash
php artisan vendor:publish --tag=kanban-config
php artisan vendor:publish --tag=kanban-views
```

خروجی به‌ترتیب `config/kanban.php` و `resources/views/vendor/kanban/` است. viewها را تنها برای override منتشر کنید؛ انتشار نسخهٔ جدید با `--force` فایل مقصد را بازنویسی می‌کند.

## انتخاب CSS

### میزبان Tailwind 4 و DaisyUI 5

در `resources/css/app.css` پس از import و تنظیم‌های موجود، اضافه کنید:

```css
@import "../../vendor/karnoweb/laravel-livewire-kanban/resources/css/kanban.css";
```

DaisyUI باید با prefix خالی باشد. سپس build میزبان را اجرا کنید:

```bash
npm run build
```

در layout اصلی، فایل CSS میزبان از مسیر Vite فعلی و JavaScript پکیج فقط یک بار بارگذاری شود:

```blade
<script src="{{ asset('vendor/kanban/kanban.js') }}" defer></script>
```

### میزبان بدون DaisyUI

در `<head>` layout و پس از آن در انتهای body:

```blade
<link rel="stylesheet" href="{{ asset('vendor/kanban/kanban.css') }}">
<script src="{{ asset('vendor/kanban/kanban.js') }}" defer></script>
```

stylesheet مستقل Tailwind، DaisyUI و reset سراسری دارد؛ آن را هم‌زمان با روش import بارگذاری نکنید. Livewire و Alpine را دوباره راه‌اندازی نکنید؛ در layout دارای Livewire موجود directiveهای آن را تکرار نکنید. نمونهٔ layout مستقل در [شروع کامل](guides/host-task-board.md) است.

## تأیید و رفع خطا

```bash
composer show karnoweb/laravel-livewire-kanban
php artisan optimize:clear
```

نام و نسخهٔ نصب‌شده باید نشان داده شود و `public/vendor/kanban/kanban.css` و `kanban.js` موجود باشند. بررسی نهایی با ثبت resolver و اولین برد در [شروع سریع](01-quickstart.md) انجام می‌شود؛ نصب پکیج به‌تنهایی برد نمونه ایجاد نمی‌کند.

| خطا | اقدام |
| --- | --- |
| component ناشناخته | کشف Provider و نصب Livewire 4 را بررسی کنید. |
| خطای نسخهٔ Composer | نسخهٔ واقعی PHP و Laravel را با محدودیت‌های بالا تطبیق دهید؛ از ignore-platform-reqs برای نصب واقعی استفاده نکنید. |
| ۴۰۴ فایل ظاهری | tag assets را منتشر و public root وب‌سرور را بررسی کنید. |
| شکل نامناسب دکمه‌ها | نسخه و prefix DaisyUI یا انتخاب stylesheet مستقل را بررسی کنید. |

# Karnoweb Laravel Livewire Kanban

رابط Board و List برای Laravel 13 و Livewire 4. پکیج جدول، migration یا مدل کسب‌وکار ندارد. داده و تغییر را میزبان با `DataProvider` و `ActionHandler` انجام می‌دهد.

## نصب

```bash
composer require karnoweb/laravel-livewire-kanban
```

دارایی آماده:

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

راهنمای کامل در `docs/` است. سناریوهای پذیرش `KB-001` تا `KB-044` در `tests/Scenarios` هستند.

مجوز این مخزن تا اعلام مالک `proprietary` است و مجوز متن‌باز محسوب نمی‌شود.

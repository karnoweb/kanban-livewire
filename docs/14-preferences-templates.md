# ترجیحات کاربر و قالب

## هدف

تم، جهت، density و نمای board/list را per user+scope+bord ذخیره کنید.

## `PreferenceStore`

**قرارداد:** `Karnoweb\Kanban\Contracts\PreferenceStore`

| متد | نقش |
| --- | --- |
| `get` / `set` / `forget` | کلیدهای preference |

**پیش‌فرض پکیج:** `SessionPreferenceStore` — کلید شامل user، hash محدوده، board.

اگر session در دسترس نباشد، `NullPreferenceStore` استفاده می‌شود و **برد کار می‌کند** (فقط preference ذخیره نمی‌شود).

## Override در میزبان

```php
// AppServiceProvider::register
$this->app->singleton(
    \Karnoweb\Kanban\Contracts\PreferenceStore::class,
    \App\Kanban\DatabasePreferenceStore::class,
);
```

## تنظیم پیش‌فرض در factory

```php
'appearance' => [
    'theme' => 'system',
    'direction' => 'ltr',
    'dates' => ['calendar' => 'auto'],
],
```

کاربر با `theme_switch` / `direction_switch` (اگر feature فعال) override می‌کند — `PreferenceManager` در پکیج.

## قالب‌های دامنه (workbench)

سناریوهای CRM، Ticket، Task، Order در `workbench/app/Kanban/*BoardFactory.php` هستند — **قانون دامنه در handler میزبان**، نه در هسته.

راهنمای پیاده‌سازی: [examples/README.md](examples/README.md).

## بررسی

تغییر theme → reload صفحه → همان theme (با session store).

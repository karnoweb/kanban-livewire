# KB-036 — رویداد خارج از ترتیب

- دامنه: CRM / Task
- فاز پیاده‌سازی: مطابق نقشه پنج‌فازه
- پیوند تست: `tests/Scenarios/KB036OutOfOrderEventTest.php`
- پیش‌نیاز: Laravel 13، Livewire 4، Factory حافظه‌ای، Scope سروری
- کاربران و Tenant: User A روی Tenant A؛ سناریوهای دوکاربره User B را هم می‌سازند
- وضعیت اولیه: داده عددی داخل متد `kb036()` در `tests/Scenarios/ScenarioSteps.php`
- مراحل اجرا: تست PHPUnit همان متد را روی کامپوننت `kanban-board` اجرا می‌کند
- نتیجه UI: مطابق assertionهای همان متد
- نتیجه Server: Handler فقط وقتی فراخوانی می‌شود که سناریو تغییر تجاری تأییدشده بخواهد
- نتیجه Event: فقط سناریوهای Realtime رویداد `RealtimeEventData` می‌سازند
- نتیجه تست واقعی: PHPUnit 12.5.39 در 2026-10-15 با `vendor/bin/phpunit --no-coverage`؛ OK (113 tests, 321 assertions). این سناریو داخل همان اجرا سبز بود.
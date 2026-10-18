# تست

## هدف

پکیج و پیاده‌سازی میزبان را با PHPUnit و (در مخزن پکیج) Playwright تست کنید.

## PHPUnit — مخزن پکیج

**مسیر:** ریشه `kanban-livewire`

```bash
composer test
```

معادل:

```bash
vendor/bin/phpunit
```

**اجرای بخشی:**

```bash
composer test -- --filter=BuilderContractTest
composer test -- --filter=ExamplePagesTest
```

**خروجی مورد انتظار:** `OK (... tests, ... assertions)`.

## سناریوهای مستند (`tests/Scenarios/`)

- پذیرش enterprise: `KNB101` … `KNB150` (فایل‌های `KNB*.md` + تست PHP مرتبط).
- گزارش JSON (بعد از اسکریپت CI): `.phpunit.cache/enterprise-scenarios.json`

**نه** `KB001` — نام‌گذاری فعلی `KNB*`.

## Fixture حافظه‌ای

`tests/Fixtures/InMemoryBoardFactory.php`, `MemoryStore` — بدون migration پکیج.

## Workbench

```bash
# طبق README_Test.md — DB جدا WORKBENCH_DB
composer test -- --filter=Workbench
```

صفحات نمونه: `tests/Workbench/ExamplePagesTest.php` (`/crm`, `/tasks`, …).

## Playwright (مخزن پکیج)

```bash
npm ci
npm run e2e
```

**الزام:** دیتابیس disposable — هرگز DB production میزبان.

جزئیات: [TEST-SCENARIOS.md](TEST-SCENARIOS.md), [../README_Test.md](../README_Test.md).

## تست اپ میزبان

Feature test Livewire:

```php
Livewire::test(\Karnoweb\Kanban\Livewire\Board::class, ['boardKey' => 'hello.board'])
    ->assertSee('اولین کارت');
```

Provider/Handler را با Testbench یا SQLite جدا تست کنید.

## دیتابیس portable (opt-in)

`EnterprisePortableDatabaseTest` — نیاز `KANBAN_TEST_DB_DRIVER` و `KANBAN_TEST_DB_DATABASE`.

## کارایی

`tests/Performance/EnterpriseQueryPerformanceTest.php` — گزارش `.phpunit.cache/enterprise-benchmark.json` (ماشین‌وابسته).

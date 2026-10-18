# کارایی

## هدف

صفحه‌بندی و query را طوری بنویسید که بار اول صدها کارت را hydrate نکند.

## بودجه پیش‌فرض UI

| تنظیم | مقدار |
| --- | --- |
| `page_size` | 25 |
| `max_page_size` | 100 |

**رفتار:** 10 ستون × `page_size` 25 → حداکثر **250** `CardData` در بار اول board (هر ستون جدا).

شمارش کل (`counts`) جدا از تعداد بارگذاری‌شده است.

## Inline vs Eloquent

| Provider | توصیه |
| --- | --- |
| `InlineDataProvider` | فقط ده‌ها کارت |
| `EloquentKanbanDataProvider` | هزاران ردیف — cursor امضاشده |

راهنما: [23-eloquent-performance.md](23-eloquent-performance.md).

## تست بودجه در مخزن

```bash
composer test -- --filter=EnterpriseQueryPerformanceTest
```

خروجی نمونه در `.phpunit.cache/enterprise-benchmark.json` — **مقایسه بین ماشین‌ها بدون context بی‌معنی است**.

## مرورگر

p95 واقعی در این مخزن بدون Playwright به‌صورت رسمی ثبت نشده؛ E2E در `e2e/` است.

## anti-pattern

- بارگذاری همه کارت‌ها در `stageCards` بدون `limit`
- N+1 روی assignee/badge — eager load در میزبان
- بالا بردن `page_size` به‌جای cursor

# فیلتر، جستجو، عملیات گروهی

## هدف

فیلتر allowlist، جستجو و bulk را در provider و handler پوشش دهید.

## فعال‌سازی

```php
'features' => [
    'search' => true,
    'filters' => true,
    'bulk' => true,
],
```

فیلدهای مجاز (`config/kanban.php`):

| نوع | کلیدها |
| --- | --- |
| sort | `manualOrder`, `dueAt`, `createdAt`, `title`, `priority` |
| filter | `assignee`, `priority`, `stage`, `dueFrom`, `dueTo`, `createdFrom`, `createdTo`, `badge`, `metadata` |

## `FilterOptionsProvider`

```php
final class TaskProvider extends WorkbenchReader implements \Karnoweb\Kanban\Contracts\FilterOptionsProvider
{
    public function filterOptions(BoardContext $context): array
    {
        return [
            'assignee' => ['u-1' => 'Alice', 'u-2' => 'Bob'],
            'badge' => ['SLA' => 'SLA'],
        ];
    }
}
```

مقادیر فیلتر در `BoardQuery::$filters` به provider می‌رسد — **شما** `where` می‌زنید (`WorkbenchReader::base`).

## جستجو

- `BoardQuery::$search` — حداکثر طول: `config('kanban.limits.search_length')` (200).
- پاسخ دیررس: اگر `queryHash` با درخواست فعلی mismatch باشد، نتیجه دور ریخته می‌شود.

## Bulk

**نوع:** `ExecuteBulkAction`

```php
'ids' => '101,102,103', // شناسه کارت، نه index DOM
'mode' => 'partial',    // یا all-or-nothing
```

| `mode` | رفتار expected در workbench |
| --- | --- |
| `partial` | موفق‌ها اعمال، بقیه `itemResults` |
| `all-or-nothing` | یک `NOT_FOUND` → همه rollback |

سقف تعداد id: `settings.security.bulkIds` / `config limits.bulk_ids` (100) — `bulk_size_invalid`.

**اتمی:** تراکنش DB در handler میزبان (`WorkbenchWriter::bulk`).

## بررسی

1. فیلتر assignee — فقط کارت‌های همان شخص.
2. انتخاب ۳ کارت + bulk archive — `kanban:bulk-completed`.
3. `bulk` false — checkboxها نیست.

## خطاهای محتمل

| علامت | علت |
| --- | --- |
| فیلتر خالی | `filterOptions` implement نشده |
| bulk روی ردیف اشتباه | id کارت اشتباه |
| `bulk_size_invalid` | بیش از 100 id |

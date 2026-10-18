# ساخت، ویرایش، بایگانی

## هدف

عملیات CRUD کارت/ستون را با feature flags و handler پیاده کنید.

## Feature flags (settings)

| عمل | کلید `features` | `ActionType` |
| --- | --- | --- |
| ساخت کارت | `create_card` | `CreateCard` |
| ویرایش | `update_card` | `UpdateCard` |
| بایگانی کارت | `archive_card` | `ArchiveCard` |
| بازگردانی | `restore_card` | `RestoreCard` |
| حذف دائمی | `delete_card` (پیش‌فرض **false**) | `DeleteCard` |
| ساخت ستون | `create_stage` | `CreateStage` |
| … | `update_stage`, `archive_stage`, `reorder_stages` | متناظر |

UI: دکمه «+» روی ستون فقط اگر `create_card` و ستون `readOnly` نباشد (`resources/views/components/stage-header.blade.php`).

## ساخت کارت

**Trigger:** انتهای ستون یا از نمای list.

**Keyboard در فرم:** Enter = submit، Escape = cancel (رفتار Livewire form).

**Handler payload (`CreateCard`):**

```php
[
    'stageId' => 'todo',
    'title' => 'عنوان',
    'description' => null,
    'priority' => null,
    'dueAt' => null,
]
```

اعتبارسنجی میزبان: عنوان خالی → `VALIDATION_ERROR` + `fieldErrors['title']`.

**WIP:** قبل از insert، ظرفیت ستون مقصد (`WorkbenchWriter::createCard`).

## بایگانی

- بایگانی **حذف سخت نیست** — `archived` true.
- `RestoreCard` می‌تواند `stageId` مقصد بدهد؛ WIP همان restore اعمال می‌شود.
- `DeleteCard` فقط وقتی `delete_card` true و کارت **قبلاً archived** — `WorkbenchWriter::deleteCard`.

## بایگانی ستون

`ArchiveStage` اگر کارت فعال در ستون باشد → `DOMAIN_REJECTED` / `stage_not_empty` (workbench).

## جزئیات کارت

باز کردن modal/drawer → `provider->card()` **تازه** (نه cache stale Livewire).

## غیرفعال کردن CRM create

```php
'features' => ['create_card' => false, 'create_stage' => false],
```

+ handler:

```php
if (in_array($command->type, [ActionType::CreateCard, ActionType::CreateStage], true)) {
    return new ActionResult(ActionStatus::VALIDATION_ERROR, messageCode: 'validation_failed');
}
```

## بررسی

| اقدام | انتظار |
| --- | --- |
| `create_card` false | بدون دکمه + `feature_disabled` اگر API زده شود |
| Archive | کارت از ستون حذف، در archives |
| Delete روی کارت فعال | `NOT_FOUND` یا policy |

## مراجع

- [06-handler-actions.md](06-handler-actions.md)
- [10-filters-bulk.md](10-filters-bulk.md) — bulk archive

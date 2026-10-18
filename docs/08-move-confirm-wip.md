# انتقال، تأیید، WIP

## هدف

ماتریس انتقال و فرم تأیید را در settings تعریف کنید و WIP را در handler داخل تراکنش enforce کنید.

## پیش‌نیاز

- [04-dtos-settings.md](04-dtos-settings.md) — `interaction`
- [06-handler-actions.md](06-handler-actions.md) — `MoveCard`

## سیاست انتقال (UI)

```php
'interaction' => [
    'transition_policy' => 'deny', // پیش‌فرض سراسری
    'transitions' => [
        'open' => ['answering'],
        'answering' => ['waiting', 'closed'],
    ],
],
```

| `transition_policy` | رفتار drag |
| --- | --- |
| `deny` | فقط `from => [to, …]` |
| `allow` | هر ستون (مگر `readOnly` / handler) |

انتقال غیرمجاز: رویداد `kanban:move-rejected`، کد `transition_denied`.

**سرور:** همان ماتریس را در handler تکرار کنید (CRM در `WorkbenchWriter::move`).

## تأیید قبل از move

### حالت سراسری

```php
'interaction' => ['confirmation' => 'none'], // none | simple | form (ConfirmationMode)
```

### قوانین per-edge (`confirmation_rules`)

کلید: `fromStageId>toStageId`

```php
'confirmation_rules' => [
    'negotiation>closed' => [
        'mode' => 'form',
        'fields' => [
            'note' => ['required' => true],
        ],
    ],
],
```

| رفتار | توضیح |
| --- | --- |
| Cancel در UI | **هیچ** `ActionCommand` به handler نمی‌رود |
| خطای validation فرم | فیلدها تا submit بعدی روی UI می‌مانند |
| `formData` در move | `$command->payload['formData']['note']` |

نمونه کامل: [examples/crm.md](examples/crm.md).

## WIP

روی `StageData`:

```php
new StageData(
    id: 'doing',
    boardId: $key,
    title: 'Doing',
    order: 1,
    wipLimit: 3,
    wipMode: WipMode::Strict,
);
```

| `wipMode` | معنی |
| --- | --- |
| `Off` | بدون سقف |
| `Strict` | رد در handler با `ActionStatus::WIP_LIMIT` |

UI فقط وضعیت را نشان می‌دهد؛ **شمارش و رد** در handler (مثلاً `WorkbenchWriter::wipFull`).

## `expectedVersion`

اگر کارت `version` دارد، Livewire `expectedVersion` می‌فرستد. ناسازگاری → `CONFLICT` / `kanban:conflict`.

## مرحله عملی: CRM

1. Factory: `transitions` + `confirmation_rules` (بالا).
2. Handler: validate note + linear path (`new→progress→negotiation→closed`).
3. تست:

```bash
composer test -- --filter=test_disallowed_transition
```

(در workbench: `ExamplePagesTest`.)

## خطاهای محتمل

| علامت | علت |
| --- | --- |
| همیشه transition_denied | `deny` بدون entry در `transitions` |
| فرم note نمی‌آید | کلید `from>to` اشتباه |
| WIP رد نمی‌شود | فقط UI؛ handler WIP چک نمی‌کند |
| move OK بدون note | handler اعتبارسنجی ندارد |

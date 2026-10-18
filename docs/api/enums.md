# مقادیر شمارشی

Namespace: `Karnoweb\Kanban\Enums`. در تنظیمات آرایه‌ای از مقدار سمت راست استفاده کنید؛ در سازندهٔ نوع‌دار از case استفاده کنید. مقدار ناشناخته در `from()` خطای `ValueError` می‌دهد. `ActionCommand::allowlisted()` برای نوع عملیات ناشناخته `InvalidArgumentException` می‌دهد.

## ActionStatus

`Karnoweb\Kanban\Enums\ActionStatus` — [منبع](../../src/Enums/ActionStatus.php)

| Case | مقدار |
| --- | --- |
| `SUCCESS` | `success` |
| `VALIDATION_ERROR` | `validation_error` |
| `FORBIDDEN` | `forbidden` |
| `NOT_FOUND` | `not_found` |
| `CONFLICT` | `conflict` |
| `WIP_LIMIT` | `wip_limit` |
| `DOMAIN_REJECTED` | `domain_rejected` |
| `RATE_LIMITED` | `rate_limited` |
| `RETRYABLE_ERROR` | `retryable_error` |
| `UNKNOWN_OUTCOME` | `unknown_outcome` |
| `UNSUPPORTED` | `unsupported` |

## ActionType

`Karnoweb\Kanban\Enums\ActionType` — [منبع](../../src/Enums/ActionType.php)

| Case | مقدار |
| --- | --- |
| `MoveCard` | `moveCard` |
| `ReorderCard` | `reorderCard` |
| `CreateCard` | `createCard` |
| `UpdateCard` | `updateCard` |
| `ArchiveCard` | `archiveCard` |
| `DeleteCard` | `deleteCard` |
| `RestoreCard` | `restoreCard` |
| `CreateStage` | `createStage` |
| `UpdateStage` | `updateStage` |
| `ReorderStage` | `reorderStage` |
| `ArchiveStage` | `archiveStage` |
| `ExecuteCardAction` | `executeCardAction` |
| `ExecuteBulkAction` | `executeBulkAction` |

## BadgeTone

`Karnoweb\Kanban\Enums\BadgeTone` — [منبع](../../src/Enums/BadgeTone.php)

| Case | مقدار |
| --- | --- |
| `Neutral` | `neutral` |
| `Info` | `info` |
| `Success` | `success` |
| `Warning` | `warning` |
| `Danger` | `danger` |

## BulkMode

`Karnoweb\Kanban\Enums\BulkMode` — [منبع](../../src/Enums/BulkMode.php)

| Case | مقدار |
| --- | --- |
| `Partial` | `partial` |
| `AllOrNothing` | `all-or-nothing` |

## ConfirmationMode

`Karnoweb\Kanban\Enums\ConfirmationMode` — [منبع](../../src/Enums/ConfirmationMode.php)

| Case | مقدار |
| --- | --- |
| `None` | `none` |
| `Generic` | `generic` |
| `Custom` | `custom` |
| `Form` | `form` |

## ViewMode

`Karnoweb\Kanban\Enums\ViewMode` — [منبع](../../src/Enums/ViewMode.php)

| Case | مقدار |
| --- | --- |
| `Board` | `board` |
| `List` | `list` |
| `Compact` | `compact` |

## WipMode

`Karnoweb\Kanban\Enums\WipMode` — [منبع](../../src/Enums/WipMode.php)

| Case | مقدار |
| --- | --- |
| `Off` | `off` |
| `Warning` | `warning` |
| `Strict` | `strict` |

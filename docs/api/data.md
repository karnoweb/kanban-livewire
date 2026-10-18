# مرجع داده‌های عمومی

تمام کلاس‌های این صفحه در `Karnoweb\Kanban\Data` هستند. سازندهٔ هر نوع، نام و نوع تمام ورودی‌ها و پیش‌فرض‌ها را نشان می‌دهد؛ بدون پیش‌فرض یعنی ورودی اجباری. سازنده‌های پکیج نام‌دار هستند و نمونه‌سازی با named arguments از وابستگی به ترتیب جلوگیری می‌کند.

- `BoardContext`: هویت معتبر سمت سرور؛ از `ScopeResolver` بسازید.
- `BoardDefinition`: خروجی `BoardBuilder::build()`؛ معمولاً مستقیم نسازید.
- `CardData`، `StageData` و `BoardData`: دادهٔ نمایشی؛ HTML خام ندهید.
- `BoardQuery`: جست‌وجو، فیلتر، ترتیب و cursor؛ برای ورودی خارجی از `make()` استفاده کنید.
- `PageResult`: فقط صفحهٔ محدود از `CardData`؛ `hasMore=true` به cursor غیرخالی نیاز دارد.
- `CountResult` و `StageCount`: شمارش کل دادهٔ مطابق query؛ تعداد loaded را جای total نگذارید.
- `ActionCommand`، `ActionResult` و `BulkItemResult`: درخواست نوشتن و نتیجهٔ صریح؛ [handler](../06-handler-actions.md).
- `CardDetailSectionData`: `items` آرایهٔ متن با label اجباری و فیلدهای اختیاری `id/value/description/status/url/thumbnailUrl` است. پیش‌فرض `canView` ممنوع است.

شناسهٔ خالی، متن اجباری خالی، URL ناامن، دادهٔ حجیم، نوع نامعتبر آیتم یا تنظیم نامعتبر می‌تواند `InvalidArgumentException` بدهد. محدودیت‌های دقیق در [تنظیمات](../CONFIGURATION.md) است. `CardDetailAction::operationId` با وجود مقدار اولیهٔ رشتهٔ خالی باید هنگام استفاده شناسهٔ معتبر داشته باشد. تنظیمات را معمولاً به‌صورت آرایه به Builder بدهید؛ سازندهٔ مستقیم به اشیای گروه‌های مربوط نیاز دارد.

نمونهٔ کامل ساخت کارت و متعلقات در [برد فقط‌خواندنی](../guides/readonly-board.md) آمده است. متدهای `toArray()` فقط تبدیل داده انجام می‌دهند؛ اجازهٔ ذخیره یا دسترسی اضافه نمی‌دهند.

## ActionCommand

`Karnoweb\Kanban\Data\ActionCommand` — [منبع](../../src/Data/ActionCommand.php)

```php
public function __construct(string $operationId, Karnoweb\Kanban\Enums\ActionType $type, array $payload, ?string $expectedVersion = null);
public static function allowlisted(string $operationId, string $type, array $payload, ?string $expectedVersion = null): self;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `operationId` | `string` | بله | `—` |
| `type` | `Karnoweb\Kanban\Enums\ActionType` | بله | `—` |
| `payload` | `array` | بله | `—` |
| `expectedVersion` | `?string` | خیر | `null` |

## ActionResult

`Karnoweb\Kanban\Data\ActionResult` — [منبع](../../src/Data/ActionResult.php)

```php
public function __construct(Karnoweb\Kanban\Enums\ActionStatus $status, ?string $messageCode = null, array $fieldErrors = [], array $affectedIds = [], array $refreshHints = [], ?string $newVersion = null, ?string $safeMessage = null, ?Karnoweb\Kanban\Data\CardData $updatedCard = null, ?Karnoweb\Kanban\Data\StageData $updatedStage = null, array $itemResults = [], int $successCount = 0, int $failureCount = 0, bool $noop = false);
public static function success(array $affectedIds = [], array $refreshHints = [], ?string $newVersion = null): self;
public static function unsupported(): self;
public static function unknown(string $operationId): self;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `status` | `Karnoweb\Kanban\Enums\ActionStatus` | بله | `—` |
| `messageCode` | `?string` | خیر | `null` |
| `fieldErrors` | `array` | خیر | `[]` |
| `affectedIds` | `array` | خیر | `[]` |
| `refreshHints` | `array` | خیر | `[]` |
| `newVersion` | `?string` | خیر | `null` |
| `safeMessage` | `?string` | خیر | `null` |
| `updatedCard` | `?Karnoweb\Kanban\Data\CardData` | خیر | `null` |
| `updatedStage` | `?Karnoweb\Kanban\Data\StageData` | خیر | `null` |
| `itemResults` | `array` | خیر | `[]` |
| `successCount` | `int` | خیر | `0` |
| `failureCount` | `int` | خیر | `0` |
| `noop` | `bool` | خیر | `false` |

## AppearanceSettings

`Karnoweb\Kanban\Data\AppearanceSettings` — [منبع](../../src/Data/AppearanceSettings.php)

```php
public function __construct(string $theme = 'system', string $direction = 'ltr', Karnoweb\Kanban\Data\DateSettings $dates = new Karnoweb\Kanban\Data\DateSettings);
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `theme` | `string` | خیر | `'system'` |
| `direction` | `string` | خیر | `'ltr'` |
| `dates` | `Karnoweb\Kanban\Data\DateSettings` | خیر | `new Karnoweb\Kanban\Data\DateSettings` |

## AssigneeData

`Karnoweb\Kanban\Data\AssigneeData` — [منبع](../../src/Data/AssigneeData.php)

```php
public function __construct(string $id, string $name, ?string $avatarUrl = null, ?string $label = null);
public function toArray(): array;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `id` | `string` | بله | `—` |
| `name` | `string` | بله | `—` |
| `avatarUrl` | `?string` | خیر | `null` |
| `label` | `?string` | خیر | `null` |

## AttachmentData

`Karnoweb\Kanban\Data\AttachmentData` — [منبع](../../src/Data/AttachmentData.php)

```php
public function __construct(string $id, string $name, string $mime, ?int $size, string $url, ?string $thumbnailUrl = null);
public function toArray(): array;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `id` | `string` | بله | `—` |
| `name` | `string` | بله | `—` |
| `mime` | `string` | بله | `—` |
| `size` | `?int` | بله | `—` |
| `url` | `string` | بله | `—` |
| `thumbnailUrl` | `?string` | خیر | `null` |

## BoardContext

`Karnoweb\Kanban\Data\BoardContext` — [منبع](../../src/Data/BoardContext.php)

```php
public function __construct(string $boardKey, string $scopeKey, string $actorId, string $instanceId);
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `boardKey` | `string` | بله | `—` |
| `scopeKey` | `string` | بله | `—` |
| `actorId` | `string` | بله | `—` |
| `instanceId` | `string` | بله | `—` |

## BoardData

`Karnoweb\Kanban\Data\BoardData` — [منبع](../../src/Data/BoardData.php)

```php
public function __construct(string $id, string $title, string $scopeKey, ?string $description = null, ?string $version = null, array $permissions = []);
public function toArray(): array;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `id` | `string` | بله | `—` |
| `title` | `string` | بله | `—` |
| `scopeKey` | `string` | بله | `—` |
| `description` | `?string` | خیر | `null` |
| `version` | `?string` | خیر | `null` |
| `permissions` | `array` | خیر | `[]` |

## BoardDefinition

`Karnoweb\Kanban\Data\BoardDefinition` — [منبع](../../src/Data/BoardDefinition.php)

```php
public function __construct(string $key, Karnoweb\Kanban\Data\BoardData $board, string $providerClass, string $handlerClass, Karnoweb\Kanban\Data\KanbanSettings $settings, array $views = [], array $inlineStages = [], array $inlineCards = [], Karnoweb\Kanban\Data\CardFormSchema $cardForm = new Karnoweb\Kanban\Data\CardFormSchema, Karnoweb\Kanban\Data\StageFormSchema $stageForm = new Karnoweb\Kanban\Data\StageFormSchema, ?array $sources = null, array $stageMap = [], ?Karnoweb\Kanban\Data\SwimlaneDefinition $swimlane = null, ?string $detailsProviderClass = null, array $detailSectionViews = []);
public function isReadOnly(): bool;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `key` | `string` | بله | `—` |
| `board` | `Karnoweb\Kanban\Data\BoardData` | بله | `—` |
| `providerClass` | `string` | بله | `—` |
| `handlerClass` | `string` | بله | `—` |
| `settings` | `Karnoweb\Kanban\Data\KanbanSettings` | بله | `—` |
| `views` | `array` | خیر | `[]` |
| `inlineStages` | `array` | خیر | `[]` |
| `inlineCards` | `array` | خیر | `[]` |
| `cardForm` | `Karnoweb\Kanban\Data\CardFormSchema` | خیر | `new Karnoweb\Kanban\Data\CardFormSchema` |
| `stageForm` | `Karnoweb\Kanban\Data\StageFormSchema` | خیر | `new Karnoweb\Kanban\Data\StageFormSchema` |
| `sources` | `?array` | خیر | `null` |
| `stageMap` | `array` | خیر | `[]` |
| `swimlane` | `?Karnoweb\Kanban\Data\SwimlaneDefinition` | خیر | `null` |
| `detailsProviderClass` | `?string` | خیر | `null` |
| `detailSectionViews` | `array` | خیر | `[]` |

## BoardQuery

`Karnoweb\Kanban\Data\BoardQuery` — [منبع](../../src/Data/BoardQuery.php)

```php
public function __construct(string $view = 'board', ?string $stageId = null, ?string $cursor = null, int $limit = 25, ?string $search = null, array $filters = [], string $sort = 'manualOrder', string $direction = 'asc', ?string $queryHash = null);
public function hash(): string;
public function withCursor(?string $cursor, ?string $stageId = null): self;
public static function make(string $view, ?string $search, array $filters, string $sort, string $direction, ?string $cursor, int $limit, ?string $stageId = null): self;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `view` | `string` | خیر | `'board'` |
| `stageId` | `?string` | خیر | `null` |
| `cursor` | `?string` | خیر | `null` |
| `limit` | `int` | خیر | `25` |
| `search` | `?string` | خیر | `null` |
| `filters` | `array` | خیر | `[]` |
| `sort` | `string` | خیر | `'manualOrder'` |
| `direction` | `string` | خیر | `'asc'` |
| `queryHash` | `?string` | خیر | `null` |

## BulkItemResult

`Karnoweb\Kanban\Data\BulkItemResult` — [منبع](../../src/Data/BulkItemResult.php)

```php
public function __construct(string $id, Karnoweb\Kanban\Enums\ActionStatus $status, ?string $messageCode = null);
public function toArray(): array;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `id` | `string` | بله | `—` |
| `status` | `Karnoweb\Kanban\Enums\ActionStatus` | بله | `—` |
| `messageCode` | `?string` | خیر | `null` |

## BulkRequest

`Karnoweb\Kanban\Data\BulkRequest` — [منبع](../../src/Data/BulkRequest.php)

```php
public function __construct(string $operationId, array $ids, Karnoweb\Kanban\Enums\BulkMode $mode, string $action, array $expectedVersions = [], array $payload = []);
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `operationId` | `string` | بله | `—` |
| `ids` | `array` | بله | `—` |
| `mode` | `Karnoweb\Kanban\Enums\BulkMode` | بله | `—` |
| `action` | `string` | بله | `—` |
| `expectedVersions` | `array` | خیر | `[]` |
| `payload` | `array` | خیر | `[]` |

## CardActionData

`Karnoweb\Kanban\Data\CardActionData` — [منبع](../../src/Data/CardActionData.php)

```php
public function __construct(string $id, string $label, bool $enabled = true, Karnoweb\Kanban\Enums\ConfirmationMode $confirmation = Karnoweb\Kanban\Enums\ConfirmationMode::None, ?string $icon = null);
public function toArray(): array;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `id` | `string` | بله | `—` |
| `label` | `string` | بله | `—` |
| `enabled` | `bool` | خیر | `true` |
| `confirmation` | `Karnoweb\Kanban\Enums\ConfirmationMode` | خیر | `Karnoweb\Kanban\Enums\ConfirmationMode::None` |
| `icon` | `?string` | خیر | `null` |

## CardBadgeData

`Karnoweb\Kanban\Data\CardBadgeData` — [منبع](../../src/Data/CardBadgeData.php)

```php
public function __construct(string $text, Karnoweb\Kanban\Enums\BadgeTone $tone = Karnoweb\Kanban\Enums\BadgeTone::Neutral, ?string $description = null);
public function toArray(): array;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `text` | `string` | بله | `—` |
| `tone` | `Karnoweb\Kanban\Enums\BadgeTone` | خیر | `Karnoweb\Kanban\Enums\BadgeTone::Neutral` |
| `description` | `?string` | خیر | `null` |

## CardData

`Karnoweb\Kanban\Data\CardData` — [منبع](../../src/Data/CardData.php)

```php
public function __construct(string $id, string $boardId, string $stageId, string $title, ?string $description = null, ?string $priority = null, array $badges = [], array $assignees = [], array $attachments = [], ?string $createdAt = null, ?string $dueAt = null, array $metadata = [], array $actions = [], array $permissions = [], ?string $version = null, ?string $manualOrder = null, bool $archived = false, array $checklist = [], array $comments = []);
public function toArray(): array;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `id` | `string` | بله | `—` |
| `boardId` | `string` | بله | `—` |
| `stageId` | `string` | بله | `—` |
| `title` | `string` | بله | `—` |
| `description` | `?string` | خیر | `null` |
| `priority` | `?string` | خیر | `null` |
| `badges` | `array` | خیر | `[]` |
| `assignees` | `array` | خیر | `[]` |
| `attachments` | `array` | خیر | `[]` |
| `createdAt` | `?string` | خیر | `null` |
| `dueAt` | `?string` | خیر | `null` |
| `metadata` | `array` | خیر | `[]` |
| `actions` | `array` | خیر | `[]` |
| `permissions` | `array` | خیر | `[]` |
| `version` | `?string` | خیر | `null` |
| `manualOrder` | `?string` | خیر | `null` |
| `archived` | `bool` | خیر | `false` |
| `checklist` | `array` | خیر | `[]` |
| `comments` | `array` | خیر | `[]` |

## CardDetailAction

`Karnoweb\Kanban\Data\CardDetailAction` — [منبع](../../src/Data/CardDetailAction.php)

```php
public function __construct(string $section, string $action, array $payload = [], string $operationId = '', ?string $expectedVersion = null);
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `section` | `string` | بله | `—` |
| `action` | `string` | بله | `—` |
| `payload` | `array` | خیر | `[]` |
| `operationId` | `string` | خیر | `''` |
| `expectedVersion` | `?string` | خیر | `null` |

## CardDetailSectionData

`Karnoweb\Kanban\Data\CardDetailSectionData` — [منبع](../../src/Data/CardDetailSectionData.php)

```php
public function __construct(string $key, string $title, int $order = 0, string $state = 'unloaded', ?string $summary = null, array $items = [], ?string $nextCursor = null, array $capabilities = [], array $actions = []);
public function canView(): bool;
public function toArray(): array;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `key` | `string` | بله | `—` |
| `title` | `string` | بله | `—` |
| `order` | `int` | خیر | `0` |
| `state` | `string` | خیر | `'unloaded'` |
| `summary` | `?string` | خیر | `null` |
| `items` | `array` | خیر | `[]` |
| `nextCursor` | `?string` | خیر | `null` |
| `capabilities` | `array` | خیر | `[]` |
| `actions` | `array` | خیر | `[]` |

## CardFormSchema

`Karnoweb\Kanban\Data\CardFormSchema` — [منبع](../../src/Data/CardFormSchema.php)

```php
public function __construct(array $fields = ['title', 'description', 'priority', 'dueAt', 'stageId'], ?string $createUrl = null, ?string $editUrl = null);
public function allows(string $field): bool;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `fields` | `array` | خیر | `['title', 'description', 'priority', 'dueAt', 'stageId']` |
| `createUrl` | `?string` | خیر | `null` |
| `editUrl` | `?string` | خیر | `null` |

## ChecklistItemData

`Karnoweb\Kanban\Data\ChecklistItemData` — [منبع](../../src/Data/ChecklistItemData.php)

```php
public function __construct(string $id, string $title, bool $done = false);
public function toArray(): array;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `id` | `string` | بله | `—` |
| `title` | `string` | بله | `—` |
| `done` | `bool` | خیر | `false` |

## CommentData

`Karnoweb\Kanban\Data\CommentData` — [منبع](../../src/Data/CommentData.php)

```php
public function __construct(string $id, string $body, ?string $author = null, ?string $createdAt = null);
public function toArray(): array;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `id` | `string` | بله | `—` |
| `body` | `string` | بله | `—` |
| `author` | `?string` | خیر | `null` |
| `createdAt` | `?string` | خیر | `null` |

## ConfirmationSchema

`Karnoweb\Kanban\Data\ConfirmationSchema` — [منبع](../../src/Data/ConfirmationSchema.php)

```php
public function __construct(string $key, array $fields);
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `key` | `string` | بله | `—` |
| `fields` | `array` | بله | `—` |

## CountResult

`Karnoweb\Kanban\Data\CountResult` — [منبع](../../src/Data/CountResult.php)

```php
public function __construct(array $stages, ?int $boardTotal = null, bool $exact = true);
public function forStage(string $stageId): ?Karnoweb\Kanban\Data\StageCount;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `stages` | `array` | بله | `—` |
| `boardTotal` | `?int` | خیر | `null` |
| `exact` | `bool` | خیر | `true` |

## DateSettings

`Karnoweb\Kanban\Data\DateSettings` — [منبع](../../src/Data/DateSettings.php)

```php
public function __construct(string $calendar = 'auto', ?string $locale = null, ?string $timezone = null, bool $persianDigits = true, string $dateFormat = 'Y-m-d', string $dateTimeFormat = 'Y-m-d H:i', string $jalaliDateFormat = 'Y/m/d', string $jalaliDateTimeFormat = 'Y/m/d H:i');
public static function fromArray(array $values = []): self;
public function toArray(): array;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `calendar` | `string` | خیر | `'auto'` |
| `locale` | `?string` | خیر | `null` |
| `timezone` | `?string` | خیر | `null` |
| `persianDigits` | `bool` | خیر | `true` |
| `dateFormat` | `string` | خیر | `'Y-m-d'` |
| `dateTimeFormat` | `string` | خیر | `'Y-m-d H:i'` |
| `jalaliDateFormat` | `string` | خیر | `'Y/m/d'` |
| `jalaliDateTimeFormat` | `string` | خیر | `'Y/m/d H:i'` |

## DisplaySettings

`Karnoweb\Kanban\Data\DisplaySettings` — [منبع](../../src/Data/DisplaySettings.php)

```php
public function __construct(Karnoweb\Kanban\Enums\ViewMode $defaultView = Karnoweb\Kanban\Enums\ViewMode::Board, array $allowedViews = ['board', 'list'], string $columnWidth = '21rem', string $density = 'comfortable', string $detailSurface = 'modal', int $drawerWidth = 560);
public function allows(Karnoweb\Kanban\Enums\ViewMode $view): bool;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `defaultView` | `Karnoweb\Kanban\Enums\ViewMode` | خیر | `Karnoweb\Kanban\Enums\ViewMode::Board` |
| `allowedViews` | `array` | خیر | `['board', 'list']` |
| `columnWidth` | `string` | خیر | `'21rem'` |
| `density` | `string` | خیر | `'comfortable'` |
| `detailSurface` | `string` | خیر | `'modal'` |
| `drawerWidth` | `int` | خیر | `560` |

## EnterpriseSettings

`Karnoweb\Kanban\Data\EnterpriseSettings` — [منبع](../../src/Data/EnterpriseSettings.php)

```php
public function __construct(array $models = ['auto_discover' => false, 'allow_unlisted_sources' => false, 'default_board_sources' => []], array $eloquent = ['enabled' => true, 'query_mode' => 'db_first', 'default_page_size' => 25, 'cursor' => ['signed' => true, 'bind_to_scope' => true, 'ttl' => 3600], 'count_mode' => 'exact', 'eager_load' => 'declared_only', 'allow_raw_sort' => false, 'allow_raw_filter' => false], array $ordering = ['strategy' => 'rank', 'rank_column' => null, 'rebalance' => 'local', 'mixed_sources_require_adapter' => true], array $metrics = ['enabled' => true, 'cache_ttl' => 0], array $swimlanes = ['enabled' => true, 'max_groups' => 30], array $cache = ['enabled' => false, 'ttl' => 30]);
public static function fromArray(array $settings): self;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `models` | `array` | خیر | `['auto_discover' => false, 'allow_unlisted_sources' => false, 'default_board_sources' => []]` |
| `eloquent` | `array` | خیر | `['enabled' => true, 'query_mode' => 'db_first', 'default_page_size' => 25, 'cursor' => ['signed' => true, 'bind_to_scope' => true, 'ttl' => 3600], 'count_mode' => 'exact', 'eager_load' => 'declared_only', 'allow_raw_sort' => false, 'allow_raw_filter' => false]` |
| `ordering` | `array` | خیر | `['strategy' => 'rank', 'rank_column' => null, 'rebalance' => 'local', 'mixed_sources_require_adapter' => true]` |
| `metrics` | `array` | خیر | `['enabled' => true, 'cache_ttl' => 0]` |
| `swimlanes` | `array` | خیر | `['enabled' => true, 'max_groups' => 30]` |
| `cache` | `array` | خیر | `['enabled' => false, 'ttl' => 30]` |

## FeatureSettings

`Karnoweb\Kanban\Data\FeatureSettings` — [منبع](../../src/Data/FeatureSettings.php)

```php
public function __construct(bool $dragDrop = true, bool $reorderCards = true, bool $reorderStages = true, bool $createCard = true, bool $updateCard = true, bool $createStage = true, bool $updateStage = true, bool $archiveCard = true, bool $archiveStage = true, bool $restoreCard = true, bool $search = true, bool $filters = true, bool $bulk = true, bool $wip = true, bool $details = true, bool $virtualization = true, bool $realtime = false, bool $customActions = true, bool $toolbar = true, bool $viewSwitch = true, bool $themeSwitch = true, bool $directionSwitch = true, bool $densitySwitch = true, bool $deleteCard = false);
public function writesEnabled(): bool;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `dragDrop` | `bool` | خیر | `true` |
| `reorderCards` | `bool` | خیر | `true` |
| `reorderStages` | `bool` | خیر | `true` |
| `createCard` | `bool` | خیر | `true` |
| `updateCard` | `bool` | خیر | `true` |
| `createStage` | `bool` | خیر | `true` |
| `updateStage` | `bool` | خیر | `true` |
| `archiveCard` | `bool` | خیر | `true` |
| `archiveStage` | `bool` | خیر | `true` |
| `restoreCard` | `bool` | خیر | `true` |
| `search` | `bool` | خیر | `true` |
| `filters` | `bool` | خیر | `true` |
| `bulk` | `bool` | خیر | `true` |
| `wip` | `bool` | خیر | `true` |
| `details` | `bool` | خیر | `true` |
| `virtualization` | `bool` | خیر | `true` |
| `realtime` | `bool` | خیر | `false` |
| `customActions` | `bool` | خیر | `true` |
| `toolbar` | `bool` | خیر | `true` |
| `viewSwitch` | `bool` | خیر | `true` |
| `themeSwitch` | `bool` | خیر | `true` |
| `directionSwitch` | `bool` | خیر | `true` |
| `densitySwitch` | `bool` | خیر | `true` |
| `deleteCard` | `bool` | خیر | `false` |

## InteractionSettings

`Karnoweb\Kanban\Data\InteractionSettings` — [منبع](../../src/Data/InteractionSettings.php)

```php
public function __construct(bool $animations = true, bool $reducedMotion = false, bool $keyboardAlternative = true, Karnoweb\Kanban\Enums\ConfirmationMode $confirmation = Karnoweb\Kanban\Enums\ConfirmationMode::None, string $transitionPolicy = 'deny', array $transitions = [], array $confirmationRules = []);
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `animations` | `bool` | خیر | `true` |
| `reducedMotion` | `bool` | خیر | `false` |
| `keyboardAlternative` | `bool` | خیر | `true` |
| `confirmation` | `Karnoweb\Kanban\Enums\ConfirmationMode` | خیر | `Karnoweb\Kanban\Enums\ConfirmationMode::None` |
| `transitionPolicy` | `string` | خیر | `'deny'` |
| `transitions` | `array` | خیر | `[]` |
| `confirmationRules` | `array` | خیر | `[]` |

## KanbanSettings

`Karnoweb\Kanban\Data\KanbanSettings` — [منبع](../../src/Data/KanbanSettings.php)

```php
public function __construct(Karnoweb\Kanban\Data\DisplaySettings $views, Karnoweb\Kanban\Data\FeatureSettings $features, Karnoweb\Kanban\Data\PaginationSettings $pagination, Karnoweb\Kanban\Data\AppearanceSettings $appearance, Karnoweb\Kanban\Realtime\RealtimeSettings $realtime, Karnoweb\Kanban\Data\InteractionSettings $interaction, Karnoweb\Kanban\Data\SecuritySettings $security, Karnoweb\Kanban\Data\EnterpriseSettings $model = new Karnoweb\Kanban\Data\EnterpriseSettings);
public static function defaults(): self;
public static function fromArray(array $settings): self;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `views` | `Karnoweb\Kanban\Data\DisplaySettings` | بله | `—` |
| `features` | `Karnoweb\Kanban\Data\FeatureSettings` | بله | `—` |
| `pagination` | `Karnoweb\Kanban\Data\PaginationSettings` | بله | `—` |
| `appearance` | `Karnoweb\Kanban\Data\AppearanceSettings` | بله | `—` |
| `realtime` | `Karnoweb\Kanban\Realtime\RealtimeSettings` | بله | `—` |
| `interaction` | `Karnoweb\Kanban\Data\InteractionSettings` | بله | `—` |
| `security` | `Karnoweb\Kanban\Data\SecuritySettings` | بله | `—` |
| `model` | `Karnoweb\Kanban\Data\EnterpriseSettings` | خیر | `new Karnoweb\Kanban\Data\EnterpriseSettings` |

## MoveRequest

`Karnoweb\Kanban\Data\MoveRequest` — [منبع](../../src/Data/MoveRequest.php)

```php
public function __construct(string $operationId, string $boardId, string $cardId, string $fromStageId, string $toStageId, ?string $beforeCardId = null, ?string $afterCardId = null, ?string $placement = null, ?string $expectedVersion = null, array $formData = []);
public function toCommand(): Karnoweb\Kanban\Data\ActionCommand;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `operationId` | `string` | بله | `—` |
| `boardId` | `string` | بله | `—` |
| `cardId` | `string` | بله | `—` |
| `fromStageId` | `string` | بله | `—` |
| `toStageId` | `string` | بله | `—` |
| `beforeCardId` | `?string` | خیر | `null` |
| `afterCardId` | `?string` | خیر | `null` |
| `placement` | `?string` | خیر | `null` |
| `expectedVersion` | `?string` | خیر | `null` |
| `formData` | `array` | خیر | `[]` |

## PageResult

`Karnoweb\Kanban\Data\PageResult` — [منبع](../../src/Data/PageResult.php)

```php
public function __construct(array $items, ?string $nextCursor, bool $hasMore, ?int $totalCount = null, bool $countExact = true, ?string $queryHash = null);
public function isExact(): bool;
public function loadedCount(): int;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `items` | `array` | بله | `—` |
| `nextCursor` | `?string` | بله | `—` |
| `hasMore` | `bool` | بله | `—` |
| `totalCount` | `?int` | خیر | `null` |
| `countExact` | `bool` | خیر | `true` |
| `queryHash` | `?string` | خیر | `null` |

## PaginationSettings

`Karnoweb\Kanban\Data\PaginationSettings` — [منبع](../../src/Data/PaginationSettings.php)

```php
public function __construct(int $pageSize = 25, int $maxPageSize = 100, bool $virtualization = true, int $virtualizationThreshold = 40, int $overscan = 4);
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `pageSize` | `int` | خیر | `25` |
| `maxPageSize` | `int` | خیر | `100` |
| `virtualization` | `bool` | خیر | `true` |
| `virtualizationThreshold` | `int` | خیر | `40` |
| `overscan` | `int` | خیر | `4` |

## RealtimeEventData

`Karnoweb\Kanban\Data\RealtimeEventData` — [منبع](../../src/Data/RealtimeEventData.php)

```php
public function __construct(int $schemaVersion, string $eventId, ?string $operationId, string $eventType, string $scopeHash, string $boardId, ?string $entityId, ?string $entityVersion, string $occurredAt);
public static function fromArray(array $payload): self;
public function toArray(): array;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `schemaVersion` | `int` | بله | `—` |
| `eventId` | `string` | بله | `—` |
| `operationId` | `?string` | بله | `—` |
| `eventType` | `string` | بله | `—` |
| `scopeHash` | `string` | بله | `—` |
| `boardId` | `string` | بله | `—` |
| `entityId` | `?string` | بله | `—` |
| `entityVersion` | `?string` | بله | `—` |
| `occurredAt` | `string` | بله | `—` |

## SecuritySettings

`Karnoweb\Kanban\Data\SecuritySettings` — [منبع](../../src/Data/SecuritySettings.php)

```php
public function __construct(int $metadataBytes = 8192, int $searchLength = 200, int $bulkIds = 100, ?int $rateLimitPerMinute = null, array $allowedUrlSchemes = ['http', 'https']);
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `metadataBytes` | `int` | خیر | `8192` |
| `searchLength` | `int` | خیر | `200` |
| `bulkIds` | `int` | خیر | `100` |
| `rateLimitPerMinute` | `?int` | خیر | `null` |
| `allowedUrlSchemes` | `array` | خیر | `['http', 'https']` |

## StageCount

`Karnoweb\Kanban\Data\StageCount` — [منبع](../../src/Data/StageCount.php)

```php
public function __construct(string $stageId, ?int $totalCount, bool $exact);
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `stageId` | `string` | بله | `—` |
| `totalCount` | `?int` | بله | `—` |
| `exact` | `bool` | بله | `—` |

## StageData

`Karnoweb\Kanban\Data\StageData` — [منبع](../../src/Data/StageData.php)

```php
public function __construct(string $id, string $boardId, string $title, int $order, bool $visible = true, bool $readOnly = false, bool $disabled = false, ?int $wipLimit = null, Karnoweb\Kanban\Enums\WipMode $wipMode = Karnoweb\Kanban\Enums\WipMode::Off, ?int $totalCount = null, ?int $loadedCount = null, bool $countExact = true, ?string $color = null, ?string $description = null, bool $archived = false);
public function toArray(): array;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `id` | `string` | بله | `—` |
| `boardId` | `string` | بله | `—` |
| `title` | `string` | بله | `—` |
| `order` | `int` | بله | `—` |
| `visible` | `bool` | خیر | `true` |
| `readOnly` | `bool` | خیر | `false` |
| `disabled` | `bool` | خیر | `false` |
| `wipLimit` | `?int` | خیر | `null` |
| `wipMode` | `Karnoweb\Kanban\Enums\WipMode` | خیر | `Karnoweb\Kanban\Enums\WipMode::Off` |
| `totalCount` | `?int` | خیر | `null` |
| `loadedCount` | `?int` | خیر | `null` |
| `countExact` | `bool` | خیر | `true` |
| `color` | `?string` | خیر | `null` |
| `description` | `?string` | خیر | `null` |
| `archived` | `bool` | خیر | `false` |

## StageFormSchema

`Karnoweb\Kanban\Data\StageFormSchema` — [منبع](../../src/Data/StageFormSchema.php)

```php
public function __construct(array $fields = ['title', 'color', 'readOnly', 'wipLimit']);
public function allows(string $field): bool;
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `fields` | `array` | خیر | `['title', 'color', 'readOnly', 'wipLimit']` |

## SwimlaneDefinition

`Karnoweb\Kanban\Data\SwimlaneDefinition` — [منبع](../../src/Data/SwimlaneDefinition.php)

```php
public function __construct(string $field = 'group', int $maxGroups = 30, bool $allowMove = false);
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `field` | `string` | خیر | `'group'` |
| `maxGroups` | `int` | خیر | `30` |
| `allowMove` | `bool` | خیر | `false` |

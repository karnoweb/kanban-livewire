# قراردادهای توسعه‌پذیری

این قراردادها متعلق به `Karnoweb\Kanban\Contracts` هستند. کلاس پیاده‌ساز را با Container (سامانهٔ ساخت و اتصال وابستگی‌ها) میزبان قابل ساخت کنید؛ Factory و Provider را با نام کلاس ثبت کنید، نه شیء یا Closure.

| قرارداد | زمان استفاده و مسئولیت |
| --- | --- |
| BoardFactory | ساخت تعریف تازه در هر درخواست؛ ثبت با `BoardRegistry::register()` |
| ScopeResolver | تعیین هویت و محدودهٔ معتبر؛ اتصال با `bind()` در Provider میزبان |
| DataProvider | خواندن داده از API یا منبع اختصاصی؛ ثبت با `BoardBuilder::provider()` |
| ActionHandler | اجرای نوشتن در سرویس دامنه؛ ثبت با `handler()`؛ تراکنش، مجوز و جلوگیری از تکرار مسئولیت میزبان است |
| CardDetailsProvider | بخش‌های جزئیات با مجوز و صفحه‌بندی مستقل؛ ثبت با `detailsProvider()` |
| FilterOptionsProvider | گزینه‌های قابل نمایش فیلتر، مستقل از کارت‌های بارگذاری‌شده |
| ColumnMetricsProvider / SwimlaneProvider | آمار و گروه‌های محدود در سمت منبع |
| ModelOperationStore | ذخیرهٔ پایدار fingerprint، pending و نتیجهٔ عملیات؛ اتصال Container جایگزین پیش‌فرض cache |
| RankStrategy | رتبه‌بندی اختصاصی به‌ویژه بین منابع؛ داخل تراکنش و قفل نوشتن فراخوانی می‌شود |
| PreferenceStore | ذخیرهٔ ترجیح؛ پیش‌فرض session؛ اتصال Container |
| AuditSink / Monitor | ثبت ممیزی و اندازه‌گیری؛ پیش‌فرض بدون ثبت؛ اتصال Container |

پارامتر بدون مقدار پیش‌فرض اجباری است؛ `?Type` یعنی مقدار null مجاز است و به‌تنهایی اختیاری بودن را تعیین نمی‌کند. آرایه‌ها و DTOها (اشیای انتقال داده) در [مرجع داده](data.md) آمده‌اند. برای نمونهٔ عملی به [Provider](../05-data-provider.md)، [handler](../06-handler-actions.md) و [جزئیات](../DRAWER.md) مراجعه کنید.

خطای مجوز را با نتیجهٔ `forbidden`، رکورد غایب/غیرقابل‌مشاهده را با `not_found` و ورودی نامعتبر را با `validation_error` برگردانید. قراردادهای خواندن باید خودشان محدوده را اعمال کنند؛ پکیج query میزبان را خودکار امن نمی‌کند.

## ActionHandler

`Karnoweb\Kanban\Contracts\ActionHandler` — [منبع](../../src/Contracts/ActionHandler.php)

```php
public function execute(Karnoweb\Kanban\Data\ActionCommand $command, Karnoweb\Kanban\Data\BoardContext $context): Karnoweb\Kanban\Data\ActionResult;
```

## AuditSink

`Karnoweb\Kanban\Contracts\AuditSink` — [منبع](../../src/Contracts/AuditSink.php)

```php
public function record(string $operationId, string $action, string $status, array $redacted = []): void;
```

## BoardFactory

`Karnoweb\Kanban\Contracts\BoardFactory` — [منبع](../../src/Contracts/BoardFactory.php)

```php
public function make(Karnoweb\Kanban\Data\BoardContext $context): Karnoweb\Kanban\Data\BoardDefinition;
```

## CardDetailsProvider

`Karnoweb\Kanban\Contracts\CardDetailsProvider` — [منبع](../../src/Contracts/CardDetailsProvider.php)

```php
public function sections(Karnoweb\Kanban\Data\BoardContext $context, Karnoweb\Kanban\Data\CardData $card): array;
public function loadSection(Karnoweb\Kanban\Data\BoardContext $context, Karnoweb\Kanban\Data\CardData $card, string $key, ?string $cursor, int $limit): Karnoweb\Kanban\Data\CardDetailSectionData;
public function performAction(Karnoweb\Kanban\Data\BoardContext $context, Karnoweb\Kanban\Data\CardData $card, Karnoweb\Kanban\Data\CardDetailAction $action): Karnoweb\Kanban\Data\ActionResult;
```

## ColumnMetricsProvider

`Karnoweb\Kanban\Contracts\ColumnMetricsProvider` — [منبع](../../src/Contracts/ColumnMetricsProvider.php)

```php
public function metrics(Karnoweb\Kanban\Data\BoardContext $context, string $stageId, Karnoweb\Kanban\Data\BoardQuery $query): array;
```

## DataProvider

`Karnoweb\Kanban\Contracts\DataProvider` — [منبع](../../src/Contracts/DataProvider.php)

```php
public function board(Karnoweb\Kanban\Data\BoardContext $context): Karnoweb\Kanban\Data\BoardData;
public function stages(Karnoweb\Kanban\Data\BoardContext $context, Karnoweb\Kanban\Data\BoardQuery $query): array;
public function stageCards(Karnoweb\Kanban\Data\BoardContext $context, string $stageId, Karnoweb\Kanban\Data\BoardQuery $query): Karnoweb\Kanban\Data\PageResult;
public function listCards(Karnoweb\Kanban\Data\BoardContext $context, Karnoweb\Kanban\Data\BoardQuery $query): Karnoweb\Kanban\Data\PageResult;
public function counts(Karnoweb\Kanban\Data\BoardContext $context, Karnoweb\Kanban\Data\BoardQuery $query): Karnoweb\Kanban\Data\CountResult;
public function card(Karnoweb\Kanban\Data\BoardContext $context, string $cardId): ?Karnoweb\Kanban\Data\CardData;
public function archives(Karnoweb\Kanban\Data\BoardContext $context, Karnoweb\Kanban\Data\BoardQuery $query): Karnoweb\Kanban\Data\PageResult;
public function capabilities(Karnoweb\Kanban\Data\BoardContext $context): array;
public function operationStatus(Karnoweb\Kanban\Data\BoardContext $context, string $operationId): ?Karnoweb\Kanban\Data\ActionResult;
```

## FilterOptionsProvider

`Karnoweb\Kanban\Contracts\FilterOptionsProvider` — [منبع](../../src/Contracts/FilterOptionsProvider.php)

```php
public function filterOptions(Karnoweb\Kanban\Data\BoardContext $context): array;
```

## ModelOperationStore

`Karnoweb\Kanban\Contracts\ModelOperationStore` — [منبع](../../src/Contracts/ModelOperationStore.php)

```php
public function result(Karnoweb\Kanban\Data\BoardContext $context, string $operationId): ?Karnoweb\Kanban\Data\ActionResult;
public function remembered(Karnoweb\Kanban\Data\BoardContext $context, string $operationId): ?array;
public function pending(Karnoweb\Kanban\Data\BoardContext $context, string $operationId, string $fingerprint): void;
public function finish(Karnoweb\Kanban\Data\BoardContext $context, string $operationId, string $fingerprint, Karnoweb\Kanban\Data\ActionResult $result): void;
```

## Monitor

`Karnoweb\Kanban\Contracts\Monitor` — [منبع](../../src/Contracts/Monitor.php)

```php
public function record(string $operation, float $durationMs, string $status, array $context = []): void;
```

## PreferenceStore

`Karnoweb\Kanban\Contracts\PreferenceStore` — [منبع](../../src/Contracts/PreferenceStore.php)

```php
public function get(string $key): mixed;
public function set(string $key, mixed $value): void;
public function forget(string $key): void;
```

## RankStrategy

`Karnoweb\Kanban\Contracts\RankStrategy` — [منبع](../../src/Contracts/RankStrategy.php)

```php
public function supports(Karnoweb\Kanban\Data\BoardDefinition $board, array $definitions): bool;
public function assign(Illuminate\Database\Eloquent\Model $record, Karnoweb\Kanban\Models\KanbanModelDefinition $definition, Karnoweb\Kanban\Data\BoardDefinition $board, Karnoweb\Kanban\Data\BoardContext $context, Karnoweb\Kanban\Data\ActionCommand $command, string $stage): array;
```

## ScopeResolver

`Karnoweb\Kanban\Contracts\ScopeResolver` — [منبع](../../src/Contracts/ScopeResolver.php)

```php
public function resolve(string $boardKey, ?string $instanceId = null): Karnoweb\Kanban\Data\BoardContext;
```

## SwimlaneProvider

`Karnoweb\Kanban\Contracts\SwimlaneProvider` — [منبع](../../src/Contracts/SwimlaneProvider.php)

```php
public function groups(Karnoweb\Kanban\Data\BoardContext $context, Karnoweb\Kanban\Data\BoardQuery $query): array;
```

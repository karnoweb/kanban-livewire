# Realtime

## هدف

پس از commit در handler، رویداد broadcast کنید تا سایر کلاینت‌ها برد را به‌روز کنند.

## پیش‌نیاز

- Laravel Broadcasting (مثلاً Reverb) در **میزبان**
- Echo در layout میزبان
- `ScopeResolver` و authorization کانال

## تنظیم برد

```php
'features' => ['realtime' => true],
'realtime' => ['enabled' => true],
```

هر دو باید برای publish فعال باشند (workbench listener روی `KanbanModelChanged` همین را چک می‌کند).

## کانال

`Karnoweb\Kanban\Realtime\ChannelNameFactory`:

```text
private-kanban.{scopeHash}.{boardKeyEncoded}
```

ثبت در میزبان (`workbench/routes/web.php`):

```php
use Illuminate\Support\Facades\Broadcast;
use Karnoweb\Kanban\Realtime\ChannelAuthorizer;

Broadcast::routes(['middleware' => ['web']]);

Broadcast::channel('kanban.{scopeHash}.{board}', static fn ($user, string $scopeHash, string $board): bool =>
    app(ChannelAuthorizer::class)->allows($scopeHash, $board)
);
```

## انتشار بعد از commit

**الگوی workbench** — `KanbanBoardServiceProvider`:

```php
\Event::listen(\Karnoweb\Kanban\Events\KanbanModelChanged::class, function ($change): void {
    $context = $change->context;
    $definition = app(\Karnoweb\Kanban\Support\BoardRegistry::class)->resolve($context->boardKey, $context);
    if ($definition->settings->realtime->enabled || $definition->settings->features->realtime) {
        app(\Karnoweb\Kanban\Realtime\RealtimePublisher::class)->publish(
            $context,
            'CardUpdated',
            $change->cardId,
            $change->version,
            $change->operationId,
        );
    }
});
```

Handler DB (مثلاً `WorkbenchWriter`) بعد از `save()`:

```php
$this->publisher()?->publish($context, 'CardMoved', $cardId, $version, $command->operationId);
```

`RealtimePublisher` از `BroadcastEventFactory` استفاده می‌کند — **بعد از** commit تراکنش صدا بزنید.

## Bridge سمت کلاینت

`RealtimeBridge` رویداد تکراری / نسخه قدیمی را حذف می‌کند؛ شکاف نسخه → reload برد.

Echo را میزبان initialize می‌کند؛ `kanban.js` دوباره Livewire/Alpine start نمی‌کند.

## بررسی

1. دو مرورگر، همان tenant — move در یکی → refresh در دیگری.
2. tenant دیگر — کانال authorize نشود.

## خطاهای محتمل

| علامت | علت |
| --- | --- |
| بدون اثر | `realtime.enabled` false یا feature false |
| 403 کانال | `ChannelAuthorizer` / scopeHash |
| دوبار apply | dedupe در Bridge خراب — `dedupe_limit` |

مرجع: `workbench/app/Providers/KanbanBoardServiceProvider.php`, `README_Test.md` (پورت Reverb E2E).

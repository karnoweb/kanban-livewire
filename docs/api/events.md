# رویدادهای عمومی

Namespace: `Karnoweb\Kanban\Events`. رویدادهای دامنهٔ پکیج، listener یا ذخیرهٔ audit اختصاصی ایجاد نمی‌کنند. برای زمان اجرا و نمونهٔ اتصال از [رویدادها و پایش](../15-events-observability.md) استفاده کنید. رویداد سرور را مستقیماً با تمام اطلاعاتش به مرورگر نفرستید؛ [Realtime](../12-realtime.md) قرارداد دادهٔ حداقلی دارد.

## KanbanModelChanged

`Karnoweb\Kanban\Events\KanbanModelChanged` — [منبع](../../src/Events/KanbanModelChanged.php)

```php
public function __construct(Karnoweb\Kanban\Data\BoardContext $context, string $source, string $cardId, string $operationId, string $action, ?string $version);
```

| فیلد | نوع | الزامی | پیش‌فرض |
| --- | --- | --- | --- |
| `context` | `Karnoweb\Kanban\Data\BoardContext` | بله | `—` |
| `source` | `string` | بله | `—` |
| `cardId` | `string` | بله | `—` |
| `operationId` | `string` | بله | `—` |
| `action` | `string` | بله | `—` |
| `version` | `?string` | بله | `—` |

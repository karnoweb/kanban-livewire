# رفع اشکال

## برد خالی / دسترسی

| علامت | علت | راه‌حل |
| --- | --- | --- |
| پیام دسترسی / `accessDenied` | کاربر لاگین نیست یا resolver exception | `ScopeResolver` + middleware `auth` |
| `ScopeResolver is not configured` | bind نشده | [01-quickstart.md](01-quickstart.md) مرحله ۳ |
| `Unknown board key` | registry | `BoardRegistry::register` |

## انتقال

| علامت | علت | راه‌حل |
| --- | --- | --- |
| همیشه `transition_denied` | `default_transition_policy` = `deny` | `transitions` یا `allow` — [08-move-confirm-wip.md](08-move-confirm-wip.md) |
| UI مجاز، سرور رد | handler سخت‌گیرتر | هم‌خوان کردن قوانین |
| `conflict` | version | refresh کارت، دوباره move |

## CSS / JS

| علامت | راه‌حل |
| --- | --- |
| بدون استایل | [02-installation-assets.md](02-installation-assets.md) |
| 404 `kanban.js` | `vendor:publish --tag=kanban-assets` |

## Realtime

| علامت | راه‌حل |
| --- | --- |
| بدون به‌روزرسانی | `realtime.enabled` + `features.realtime` — [12-realtime.md](12-realtime.md) |
| کانال reject | `Broadcast::channel` + `ChannelAuthorizer` |

## WIP / bulk

| علامت | راه‌حل |
| --- | --- |
| عبور از سقف WIP | handler باید `WIP_LIMIT` برگرداند |
| `bulk_size_invalid` | ≤ `limits.bulk_ids` |

## داده

| علامت | راه‌حل |
| --- | --- |
| tenant اشتباه | `scope_key` در provider/handler — [13-multitenancy-security.md](13-multitenancy-security.md) |
| فیلتر بی‌اثر | implement در `baseQuery` — [10-filters-bulk.md](10-filters-bulk.md) |

## دیباگ سریع

```bash
php artisan tinker
```

```php
$ctx = app(\Karnoweb\Kanban\Support\ContextFactory::class)->make('YOUR_BOARD_KEY');
app(\Karnoweb\Kanban\Support\BoardRegistry::class)->resolve('YOUR_BOARD_KEY', $ctx);
```

اگر اینجا exception است، factory/settings مشکل دارد قبل از Livewire.

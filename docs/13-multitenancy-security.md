# چندمستأجری و امنیت

## هدف

`scopeKey` را از هویت معتبر میزبان بگیرید؛ هرگز از query string کلاینت برای scope استفاده نکنید.

## `ScopeResolver`

```php
// app/Kanban/AppScopeResolver.php
return new BoardContext(
    boardKey: $boardKey,
    scopeKey: (string) $user->organization_id,
    actorId: (string) $user->getAuthIdentifier(),
    instanceId: $instanceId ?? 'default',
);
```

## `ContextFactory` — آرگومان سوم نادیده

```php
public function make(string $boardKey, ?string $instanceId = null, ?string $clientScopeKey = null): BoardContext
{
    unset($clientScopeKey);
    return $this->resolver->resolve($boardKey, $instanceId);
}
```

کلاینت **نمی‌تواند** scope را عوض کند.

## Provider

همه queryها:

```php
->where('scope_key', $context->scopeKey)
```

کارت خارج از scope → `card()` = `null` / move → `NOT_FOUND` — **عنوان leak نمی‌شود**.

## Blade

خروجی عنوان کارت در viewهای پکیج escape شده است؛ metadata خام کاربر را بدون sanitize در Blade نچسبانید.

## محدودیت‌های ورودی (`config/kanban.php` → `limits` / `security`)

| کلید | پیش‌فرض | اثر |
| --- | --- | --- |
| `metadata_bytes` | 8192 | اندازه metadata سریال‌شده |
| `search_length` | 200 | جستجو |
| `bulk_ids` | 100 | bulk |
| `title_length` | 300 | عنوان کارت |
| `allowed_url_schemes` | http, https | URL در attachment/metadata |

## URL در کارت

فقط schemeهای allowlist یا مسیر relative — `UrlSanitizer` در builder برای `cardPages`.

## بررسی

`/login-as/alice` vs `carol` (workbench) — داده tenant جدا.

## خطاهای محتمل

| علامت | علت |
| --- | --- |
| `ScopeResolver is not configured` | bind نشده |
| داده مخلوط | scope در handler فراموش شده |

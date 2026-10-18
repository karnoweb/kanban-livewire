# تنظیمات سراسری (`config/kanban.php`)

## هدف

پیش‌فرض‌های همه بردها را override کنید؛ هر برد می‌تواند در `BoardBuilder::settings()` بازنویسی کند.

## مرحله ۱: انتشار

```bash
php artisan vendor:publish --tag=kanban-config
```

**فایل:** `config/kanban.php` (کپی از `vendor/.../config/kanban.php`).

بدون publish، `KanbanServiceProvider` همان پیش‌فرض vendor را merge می‌کند.

## مرحله ۲: کلیدهای پرکاربرد

| کلید | پیش‌فرض | اثر |
| --- | --- | --- |
| `page_size` | 25 | اندازه صفحه ستون/list |
| `max_page_size` | 100 | سقف `BoardQuery::limit` |
| `default_transition_policy` | `deny` | اگر برد `interaction` ندهد |
| `default_view` | `board` | |
| `allowed_views` | `board`, `list` | |
| `features.*` | [فایل config](https://github.com/karnoweb/kanban-livewire/blob/main/config/kanban.php) | flags سراسری |
| `dates.*` | `calendar: auto` | [JALALI-DATES.md](JALALI-DATES.md) |
| `realtime.enabled` | false | |
| `telemetry.enabled` | false | |
| `models.auto_discover` | false | ثبت صریح source لازم |
| `models.allow_unlisted_sources` | false | |

تنظیم per-board: [04-dtos-settings.md](04-dtos-settings.md).

## `limits` / `security`

| کلید | پیش‌فرض |
| --- | --- |
| `search_length` | 200 |
| `bulk_ids` | 100 |
| `metadata_bytes` | 8192 |
| `title_length` | 300 |
| `detail_sections` | 16 |
| `detail_section_page_size` | 25 |
| `detail_section_bytes` | 32768 |

## `eloquent` (adapter)

| کلید | نقش |
| --- | --- |
| `query_mode` | `db_first` |
| `cursor.signed` | true |
| `cursor.bind_to_scope` | true |
| `count_mode` | `exact` |

visibility و `authorize` هنوز روی تعریف مدل میزبان است — [21-trait-eloquent-guide.md](21-trait-eloquent-guide.md).

## `sources([])` در builder

`->sources([])` یعنی **هیچ** منبع مدلی — نه «همه مدل‌ها».

## اعتبارسنجی

`BoardDefinitionValidator` کلاس نامعتبر، view ناامن و ستون ناشناخته را رد می‌کند.

## خطاهای محتمل

| علامت | علت |
| --- | --- |
| تغییر config بی‌اثر | override در factory |
| `InvalidBoardDefinition` | views خالی یا source نامعتبر |

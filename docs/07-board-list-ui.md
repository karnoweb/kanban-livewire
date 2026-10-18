# نمای Board و List

## هدف

دو نمای `board` و `list` را از طریق `views` در settings پیکربندی کنید و رفتار صفحه‌بندی/اسکرول را درک کنید.

## تنظیم در Factory

```php
'settings' => [
    'views' => [
        'default' => 'board',
        'allowed' => ['board', 'list'],
        'column_width' => '21rem',
        'density' => 'comfortable', // یا compact
        'detail_surface' => 'modal', // یا drawer — [DRAWER.md](DRAWER.md)
    ],
    'features' => [
        'view_switch' => true,
        'virtualization' => true,
    ],
],
```

| کلید | اثر |
| --- | --- |
| `allowed` | اگر فقط `['list']` — سوئیچ board مخفی |
| `default` | نمای اولیه پس از بارگذاری |
| `column_width` | عرض ستون در board |
| `density` | فشردگی کارت‌ها |

**قانون:** `allowed` نمی‌تواند خالی باشد — حداقل `board` یا `list`.

## رفتار Board

- هر ستون (`kb-stage-scroll`) **اسکرول و load-more مستقل** دارد.
- `stageCards` با `BoardQuery` و cursor همان ستون صدا زده می‌شود.
- تغییر نما (`view_switch`) دستی است — عرض viewport به‌تنهایی نما را عوض نمی‌کند.

## رفتار List

- `listCards` — یک ستون مجازی از همه stageها (با فیلتر stage در `filters.stage`).
- مناسب عملیات انبوه و sort سراسری.
- همان `page_size` از settings.

## Virtualization

`features.virtualization` + `pagination.virtualization`:

- فقط کارت‌های **پنجره visible** در DOM رندر می‌شوند.
- داده صفحه‌نشده را خودکار دانلود **نمی‌کند** — هنوز باید load-more/cursor از provider بیاید.

## Layout میزبان (بدون اسکرول کل صفحه)

```css
.host-kanban-shell {
    block-size: calc(100dvh - 64px);
    display: flex;
    flex-direction: column;
    min-block-size: 0;
    overflow: hidden;
}
```

جزئیات: [11-views-customization.md](11-views-customization.md).

## Blade

```blade
<div class="host-kanban-shell">
    <livewire:kanban-board board-key="tasks.board" />
</div>
```

## بررسی

1. `view_switch` — toggle بین board/list.
2. ستون با بیش از `page_size` کارت — دکمه load-more همان ستون.
3. `density` compact — attribute `data-density="compact"` روی `.kb`.

## خطاهای محتمل

| علامت | علت |
| --- | --- |
| سوئیچ نما نیست | `view_switch` false یا یک view در `allowed` |
| اسکرول کل صفحه | ظرف parent ارتفاع/`min-height:0` ندارد |
| list خالی | `listCards` در provider برای فیلتر فعلی |

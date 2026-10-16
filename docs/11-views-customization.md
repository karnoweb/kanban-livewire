# سفارشی‌سازی ظاهر، سلکتورهای CSS و جایگزینی View

این راهنما توضیح می‌دهد چطور در اپلیکیشن میزبان ظاهر کامپوننت `<livewire:kanban-board />` را بدون تغییر کد هسته پکیج سفارشی کنید: از طریق متغیرهای CSS (Design Tokens)، سلکتورهای کلاس (`kb-*`)، سلکتورهای پایدار قطعه (`[data-kb-part="..."]`)، یا جایگزینی کامل فایل‌های Blade.

---

## ۱. لایه‌بندی بدون اسکرول کل صفحه (Viewport-Locked Layout)

ریشه کامپوننت (`.kb`) با `flex: 1 1 0%`، `min-block-size: 0` و `overflow: hidden` طراحی شده است تا ارتفاع باقی‌مانده ظرف پدر را پر کند و **فقط داخل هر ستون** (`.kb-stage-scroll`) یا **بدنه نمای لیست** (`.kb-list-scroll`) اسکرول بخورد.

برای اینکه کل صفحه مرورگر اسکرول نخورد، ظرف میزبان که `<livewire:kanban-board />` داخل آن قرار می‌گیرد باید ارتفاع مشخص و `display: flex; flex-direction: column; min-block-size: 0; overflow: hidden` داشته باشد:

```css
.host-kanban-shell {
    block-size: calc(100dvh - 64px); /* ارتفاع صفحه منهای هدر اپلیکیشن شما */
    display: flex;
    flex-direction: column;
    min-block-size: 0;
    overflow: hidden;
}
```

---

## ۲. اتصال به DaisyUI و متغیرهای میزبان

نسخه بررسی‌شده: **DaisyUI 5.7.50 / Tailwind CSS 4.3.3**. بسته از تنظیمات پیش‌فرض و پیشوند خالی DaisyUI استفاده می‌کند. نسخه ۴ پشتیبانی نمی‌شود.

### میزبان دارای DaisyUI (روش پیشنهادی)

در فایل سراسری `resources/css/app.css` میزبان، importها را ابتدای فایل قرار دهید. مسیر زیر نسبت به همان فایل است:

```css
@import "tailwindcss";
@import "../../vendor/karnoweb/laravel-livewire-kanban/resources/css/kanban.css";
@plugin "daisyui";
```

اگر Tailwind و plugin از قبل وجود دارند، فقط import دوم را اضافه کنید. فایل بسته خودش `@source "../views"` دارد تا کلاس‌های نماهای Composer vendor در ساخت CSS میزبان شناسایی شوند. فقط JavaScript بسته را جداگانه بارگذاری کنید؛ فایل آماده `resources/dist/kanban.css` را هم‌زمان اضافه نکنید، زیرا شامل Tailwind و DaisyUI مستقل است.

### کنترل از طریق توکن‌ها (متغیرهای طراحی) میزبان

تمام رنگ‌ها از متغیرهای عمومی DaisyUI می‌آیند: `--color-base-100/200/300`، `--color-base-content`، `--color-primary` و `--color-primary-content`، رنگ‌های `error/success/warning/info` و متغیر `content` متناظر. رنگ پس‌زمینه و متن دکمه اصلی به‌صورت جفت کنترل می‌شوند. شعاع‌ها از `--radius-box` و `--radius-selector` می‌آیند؛ دکمه و ورودی از اندازه، حاشیه، عمق و حالت‌های خود DaisyUI استفاده می‌کنند.

نمونه در همان CSS سراسری، برای پوسته دلخواه میزبان:

```css
[data-theme="brand"] {
    --color-primary: oklch(45% 0.2 270);
    --color-primary-content: oklch(99% 0 0);
    --radius-box: .75rem;
    --radius-field: .5rem;
    --radius-selector: .375rem;
}
/* <html data-theme="brand">؛ بقیه رنگ‌های پوسته را در میزبان تعریف کنید. */
```

برای ساخت یک پوسته کامل از `@plugin "daisyui/theme"` میزبان استفاده کنید. بسته هیچ پوسته اختصاصی ثبت نمی‌کند. رنگ ستون که از داده می‌آید همچنان مستقل است؛ میزبان باید تضاد آن را با پوسته‌های خودش بررسی کند.

متغیرهای `--kb-*` پل سازگاری و کنترل چیدمان هستند؛ برای تغییر برند، متغیرهای DaisyUI را تغییر دهید. رنگ‌های `--kb-fg/card/column/accent/...` روی `.kb` از DaisyUI محاسبه می‌شوند. برای بازنویسی مستقیم آن‌ها، انتخابگر `.kb` را در CSS میزبان پس از import تعریف کنید. متغیر روی والد به‌تنهایی، تعریف محلی `.kb` را بازنویسی نمی‌کند.

```css
.kb {
    --kb-card-pad: 1rem;
    --kb-card-gap: .625rem;
}
[data-kb-part="card"] { border-radius: var(--radius-box); }
```

عرض ستون از تنظیم `views.column_width` می‌آید و روی ریشه به‌صورت درون‌خطی قرار می‌گیرد؛ همان تنظیم را تغییر دهید. از متغیرهای داخلی و مستندنشده اجزای DaisyUI استفاده نکنید.

### نصب مستقل

میزبان بدون DaisyUI می‌تواند فقط `vendor/kanban/kanban.css` منتشرشده و JavaScript بسته را بارگذاری کند. این فایل ساخته‌شده شامل Tailwind و DaisyUI پیش‌فرض است و بازنشانی عمومی CSS دارد. آن را روی میزبان دارای DaisyUI بارگذاری نکنید. در مخزن، `npm ci && npm run build` هر دو دارایی را می‌سازد؛ `resources/css/standalone.css` فقط ورودی ساخت مستقل است.

## ۳. حالت‌های ریشه و قرارداد انتخابگرها

| انتخابگر | معنی |
| --- | --- |
| `.kb[data-kb-theme="system"]` | ارث‌بری پوسته و متغیرهای میزبان؛ `data-theme` محلی ندارد. پیش‌فرض DaisyUI خودش از تنظیم سیستم پیروی می‌کند. |
| `.kb[data-theme="light"]`, `.kb--theme-light` | انتخاب صریح روشن، با نیاز به وجود پوسته light در میزبان |
| `.kb[data-theme="dark"]`, `.kb--theme-dark` | انتخاب صریح تیره، با نیاز به وجود پوسته dark در میزبان |
| `.kb[dir="rtl"]`, `.kb[dir="ltr"]` | جهت نوشتار |
| `.kb[data-density="comfortable"]`, `.kb[data-density="compact"]` | تراکم |
| `.kb[data-view="board"]`, `.kb[data-view="list"]`, `.kb[data-view="compact"]` | نوع نمایش |
| `.kb[data-reduced-motion="true"]` | حذف حرکت |

تنظیم `appearance.theme` را برای ارث‌بری میزبان روی `system` نگه دارید؛ تنظیم صریح `light` یا `dark` بر پوسته میزبان مقدم است. `setTheme('system')` انتخاب محلی را پاک می‌کند تا پوسته میزبان، از جمله پوسته سفارشی، به ارث برسد. اگر میزبان فقط پوسته سفارشی دارد، `features.theme_switch` را خاموش کنید. `setDirection()` و `setDensity()` همچنان فعال‌اند. نام حالت `system` برای سازگاری تنظیمات قبلی حفظ شده؛ دیگر از `.kb[data-theme="system"]` استفاده نکنید.

کلاس‌های `btn`، `input`، `select`، `checkbox`، `badge` و `card` اجزای عمومی DaisyUI هستند. کلاس‌های `kb-*` و `data-kb-part` برای ساختار، حالت و رفتار بسته باقی می‌مانند؛ آن‌ها را از نمای سفارشی حذف نکنید. پنجره‌های بسته به‌دلیل چرخه Livewire از سطح سفارشی با رفتار گفت‌وگو استفاده می‌کنند؛ کلاس `modal` جایگزین مستقیم آن‌ها نیست.

فیلتر و مرتب‌سازی در `[data-kb-part="filter-options"]` و ظاهر در `[data-kb-part="appearance-options"]` قرار دارند. برای دسترسی، ابتدا `summary` را باز کنید؛ وضعیت با عنصر بومی `details` مدیریت می‌شود.

## ۴. جدول جامع سلکتورهای CSS و `data-kb-part`

هر بخش مهم علاوه بر کلاس BEM، دارای `data-kb-part` است تا در صورت استفاده از Tailwind یا CSS Modules بدون وابستگی به ساختار تودرتو قابل انتخاب باشد.

### نوار ابزار و فیلترها

| بخش | کلاس CSS | سلکتور `data-kb-part` |
| --- | --- | --- |
| نوار ابزار اصلی | `.kb-toolbar` | `[data-kb-part="toolbar"]` |
| گروه شروع/پایان نوار | `.kb-toolbar-group--start`, `.kb-toolbar-group--end` | `[data-kb-part="toolbar-start"]`, `[data-kb-part="toolbar-end"]` |
| جعبه جست‌وجو | `.kb-search`, `.kb-search-input` | `[data-kb-part="search"]`, `[data-kb-part="search-input"]` |
| سوییچ Board / List | `.kb-switch--view` | `[data-kb-part="view-switch"]` |
| سوییچ Dark / Light / Auto | `.kb-switch--theme` | `[data-kb-part="theme-switch"]` |
| سوییچ LTR / RTL | `.kb-switch--dir` | `[data-kb-part="dir-switch"]` |
| سوییچ تراکم | `.kb-switch--density` | `[data-kb-part="density-switch"]` |
| پنل فیلتر و مرتب‌سازی | `.kb-filters`, `.kb-chip` | `[data-kb-part="filters"]`, `[data-kb-part="filter-priority"]`, `[data-kb-part="sort-group"]` |
| نوار عملیات گروهی | `.kb-bulk`, `.kb-bulk-count` | `[data-kb-part="bulk-bar"]`, `[data-kb-part="bulk-count"]` |
| بنر خطا / عدم مجوز انتقال | `.kb-notice`, `.kb-notice--forbidden` | `[data-kb-part="notice"]`, `[data-kb-part="notice-text"]` |

### برد و ستون‌ها (Stages)

| بخش | کلاس CSS | سلکتور `data-kb-part` / Attribute |
| --- | --- | --- |
| ظرف افقی ستون‌ها | `.kb-board` | `[data-kb-part="board"]` |
| ستون (Stage) | `.kb-stage` | `[data-kb-part="stage"][data-stage="<id>"]` |
| ستون فقط‌خواندنی | `.kb-stage--readonly` | `.kb-stage[data-readonly="true"]` |
| ستون با ظرفیت WIP پر | `.kb-stage--wip-exceeded` | `.kb-stage[data-wip-mode="strict"]` |
| هدر ستون و عنوان | `.kb-stage-header`, `.kb-stage-title` | `[data-kb-part="stage-header"]`, `[data-kb-part="stage-title"]` |
| نشان تعداد و WIP | `.kb-count`, `.kb-wip` | `[data-kb-part="stage-count"]`, `[data-kb-part="stage-wip"]` |
| ناحیه اسکرول کارت‌های ستون | `.kb-stage-scroll` | `[data-kb-part="stage-body"]` |
| دکمه افزودن کارت زیر ستون | `.kb-add` | `[data-kb-part="stage-add-card"]` |

### کارت (Card) و نمای لیست (List Row)

| بخش | کلاس CSS | سلکتور `data-kb-part` |
| --- | --- | --- |
| کارت کانبان | `.kb-card`, `.kb-card--priority-{high\|medium\|low}`, `.kb-card--selected`, `.kb-card--blocked` | `[data-kb-part="card"][data-card="<id>"]` |
| برچسب‌ها (Badges) | `.kb-badges`, `.kb-badge`, `.kb-badge-{tone}` | `[data-kb-part="card-badges"]`, `[data-kb-part="card-badge"]` |
| هدر کارت، کد و عنوان | `.kb-card-head`, `.kb-card-code`, `.kb-card-title` | `[data-kb-part="card-header"]`, `[data-kb-part="card-code"]`, `[data-kb-part="card-title"]` |
| توضیح کوتاه کارت | `.kb-card-text` | `[data-kb-part="card-description"]` |
| نوار پیشرفت کارت | `.kb-card-progress`, `.kb-card-progress-bar` | `[data-kb-part="card-progress"]` |
| پیوست‌ها | `.kb-attachments`, `.kb-attachment` | `[data-kb-part="card-attachments"]`, `[data-kb-part="card-attachment"]` |
| فوتر، آمار و موعد | `.kb-card-footer`, `.kb-card-stats`, `.kb-stat--due`, `.kb-stat--checklist`, `.kb-stat--comments`, `.kb-stat--points` | `[data-kb-part="card-footer"]`, `[data-kb-part="card-stats"]`, `[data-kb-part="card-due"]` |
| مسئولین (Assignees) | `.kb-assignees`, `.kb-assignee`, `.kb-assignee-avatar`, `.kb-assignee-initials` | `[data-kb-part="card-assignees"]`, `[data-kb-part="card-assignee"]` |
| منوی عملیات کارت | `.kb-menu`, `.kb-card-menu`, `.kb-menu-item` | `[data-kb-part="card-menu"]`, `[data-kb-part="card-menu-list"]` |
| ردیف نمای لیست | `.kb-row`, `.kb-row-main`, `.kb-row-meta`, `.kb-stage-pill` | `[data-kb-part="list-row"]`, `[data-kb-part="list-row-main"]` |

---

## ۵. کلاس‌های حالت Drag & Drop و انیمیشن‌ها

هنگام کشیدن کارت یا رد شدن انتقال بین ستون‌ها، کلاس‌ها و attributeهای زیر اعمال می‌شوند:

| سلکتور | زمان اعمال |
| --- | --- |
| `.kb-card.kb-dragging` | کارتی که در حال کشیده شدن است |
| `.kb-stage--source` | ستون مبدأ کارت در حال کشیده شدن |
| `.kb-stage[data-drop-state="allowed"]` | ستون‌هایی که طبق `TransitionPolicy` و ظرفیت `WIP` مقصد مجاز هستند |
| `.kb-stage[data-drop-state="blocked"]` | ستون‌هایی که انتقال به آن‌ها مجاز نیست |
| `.kb-stage.kb-drop-target.kb-drop-allowed` | ستون مجاز زیر نشانگر ماوس هنگام Drag |
| `.kb-stage.kb-drop-target.kb-drop-blocked` | ستون غیرمجاز زیر نشانگر ماوس (نمایش کادر قرمز و پیام عدم مجوز) |
| `.kb-drop-indicator` (`[data-kb-part="drop-indicator"]`) | خط راهنمای محل دقیق قرارگیری کارت بین کارت‌های یک ستون |
| `.kb-card--settled` | انیمیشن نشستن کارت پس از تأیید انتقال (`kanban:move-confirmed`) |
| `.kb-card--rejected`, `.kb-stage--shake` | انیمیشن لرزش کارت و ستون پس از رد شدن انتقال (`kanban:move-rejected`) |

---

## ۶. جایگزینی Viewهای Blade

`cardView`، `columnView` و `listView` فقط نام view مجاز (حروف، نقطه، دو نقطه) می‌پذیرند. مسیر `..` رد می‌شود. View سفارشی باید متن را escape کند. کلاس‌های CSS با پیشوند `kb-` هستند.

## ۷. فهرست کامل انتخابگرهای ثابت

این جدول از نماهای فعلی استخراج شده است. مقادیر پویا مانند `filter-priority-{{ $prio }}` در جدول‌های بالا با الگو آمده‌اند.

| `data-kb-part` | فایل نما |
| --- | --- |
| `appearance-options` | `toolbar.blade.php` |
| `archive-browser` | `archive-browser.blade.php` |
| `archive-item` | `archive-browser.blade.php` |
| `board` | `board.blade.php` |
| `bulk-archive-atomic` | `bulk-toolbar.blade.php` |
| `bulk-archive-partial` | `bulk-toolbar.blade.php` |
| `bulk-bar` | `bulk-toolbar.blade.php` |
| `bulk-clear` | `bulk-toolbar.blade.php` |
| `bulk-count` | `bulk-toolbar.blade.php` |
| `bulk-summary` | `bulk-toolbar.blade.php` |
| `card` | `card.blade.php` |
| `card-action-archive` | `card-actions.blade.php` |
| `card-action-custom` | `card-actions.blade.php` |
| `card-action-details` | `card-actions.blade.php` |
| `card-action-edit` | `card-actions.blade.php` |
| `card-action-move` | `card-actions.blade.php` |
| `card-assignee` | `card-assignees.blade.php` |
| `card-assignees` | `card-assignees.blade.php` |
| `card-attachment` | `card-attachments.blade.php` |
| `card-attachments` | `card-attachments.blade.php` |
| `card-badge` | `card-badges.blade.php` |
| `card-badges` | `card-badges.blade.php` |
| `card-checklist` | `card.blade.php`, `list-row.blade.php` |
| `card-code` | `card-details-modal.blade.php`, `card.blade.php`, `list-row.blade.php` |
| `card-comments` | `card.blade.php`, `list-row.blade.php` |
| `card-description` | `card.blade.php` |
| `card-drag-handle` | `card.blade.php` |
| `card-due` | `card.blade.php`, `list-row.blade.php` |
| `card-files-count` | `card.blade.php` |
| `card-footer` | `card.blade.php` |
| `card-form` | `card-form.blade.php` |
| `card-form-actions` | `card-form.blade.php` |
| `card-form-stage` | `card-form.blade.php` |
| `card-form-title` | `card-form.blade.php` |
| `card-header` | `card.blade.php` |
| `card-heading` | `card.blade.php` |
| `card-menu` | `card-actions.blade.php` |
| `card-menu-list` | `card-actions.blade.php` |
| `card-menu-trigger` | `card-actions.blade.php` |
| `card-points` | `card.blade.php`, `list-row.blade.php` |
| `card-priority` | `card.blade.php` |
| `card-progress` | `card.blade.php`, `list-row.blade.php` |
| `card-select` | `card.blade.php` |
| `card-stats` | `card.blade.php` |
| `card-title` | `card.blade.php` |
| `clear-filters` | `filter-panel.blade.php` |
| `confirm-dialog` | `move-confirmation.blade.php` |
| `density-comfortable` | `toolbar.blade.php` |
| `density-compact` | `toolbar.blade.php` |
| `density-switch` | `toolbar.blade.php` |
| `details-close` | `card-details-drawer.blade.php`, `card-details-modal.blade.php` |
| `details-description` | `card-details-drawer.blade.php`, `card-details-modal.blade.php` |
| `details-drawer` | `card-details-drawer.blade.php` |
| `details-footer` | `card-details-modal.blade.php` |
| `details-header` | `card-details-drawer.blade.php`, `card-details-modal.blade.php` |
| `details-link` | `card-details-modal.blade.php` |
| `details-modal` | `card-details-modal.blade.php` |
| `details-title` | `card-details-drawer.blade.php`, `card-details-modal.blade.php` |
| `dir-ltr` | `toolbar.blade.php` |
| `dir-rtl` | `toolbar.blade.php` |
| `dir-switch` | `toolbar.blade.php` |
| `filter-group` | `filter-panel.blade.php` |
| `filter-options` | `toolbar.blade.php` |
| `filter-priority` | `filter-panel.blade.php` |
| `filter-priority-all` | `filter-panel.blade.php` |
| `filter-source` | `filter-panel.blade.php` |
| `filter-stage` | `filter-panel.blade.php` |
| `filters` | `filter-panel.blade.php` |
| `filters-label` | `filter-panel.blade.php` |
| `inline-create` | `card-create-inline.blade.php` |
| `inline-create-actions` | `card-create-inline.blade.php` |
| `list` | `list.blade.php` |
| `list-body` | `list.blade.php` |
| `list-count` | `list.blade.php` |
| `list-load-more` | `list.blade.php` |
| `list-row` | `list-row.blade.php` |
| `list-row-description` | `list-row.blade.php` |
| `list-row-header` | `list-row.blade.php` |
| `list-row-main` | `list-row.blade.php` |
| `list-row-meta` | `list-row.blade.php` |
| `list-row-priority` | `list-row.blade.php` |
| `list-row-select` | `list-row.blade.php` |
| `list-row-stage` | `list-row.blade.php` |
| `list-row-stats` | `list-row.blade.php` |
| `list-row-sub` | `list-row.blade.php` |
| `list-row-title` | `list-row.blade.php` |
| `live-region` | `board.blade.php` |
| `load-more` | `load-more.blade.php` |
| `notice` | `notice-banner.blade.php` |
| `notice-dismiss` | `notice-banner.blade.php` |
| `notice-text` | `notice-banner.blade.php` |
| `reorder-reason` | `toolbar.blade.php` |
| `root` | `board.blade.php` |
| `search` | `search-box.blade.php` |
| `search-input` | `search-box.blade.php` |
| `sort-group` | `filter-panel.blade.php` |
| `stage` | `stage.blade.php` |
| `stage-accent` | `stage-header.blade.php` |
| `stage-actions` | `stage-actions.blade.php` |
| `stage-add-card` | `stage.blade.php` |
| `stage-archive` | `stage-actions.blade.php` |
| `stage-body` | `stage.blade.php` |
| `stage-collapse` | `stage-header.blade.php` |
| `stage-count` | `count-badge.blade.php` |
| `stage-edit` | `stage-actions.blade.php` |
| `stage-form` | `stage-form.blade.php` |
| `stage-form-actions` | `stage-form.blade.php` |
| `stage-form-color` | `stage-form.blade.php` |
| `stage-form-title` | `stage-form.blade.php` |
| `stage-header` | `stage-header.blade.php` |
| `stage-meta` | `stage-header.blade.php` |
| `stage-metric` | `stage-header.blade.php` |
| `stage-readonly` | `stage-header.blade.php` |
| `stage-title` | `stage-header.blade.php` |
| `stage-wip` | `stage-header.blade.php` |
| `theme-dark` | `toolbar.blade.php` |
| `theme-light` | `toolbar.blade.php` |
| `theme-switch` | `toolbar.blade.php` |
| `theme-system` | `toolbar.blade.php` |
| `toolbar` | `toolbar.blade.php` |
| `toolbar-archive` | `toolbar.blade.php` |
| `toolbar-create-card` | `toolbar.blade.php` |
| `toolbar-create-stage` | `toolbar.blade.php` |
| `toolbar-end` | `toolbar.blade.php` |
| `toolbar-start` | `toolbar.blade.php` |
| `view-board` | `toolbar.blade.php` |
| `view-list` | `toolbar.blade.php` |
| `view-switch` | `toolbar.blade.php` |
| `virtual-list` | `virtual-list.blade.php` |
| `window-next` | `virtual-list.blade.php` |
| `window-previous` | `virtual-list.blade.php` |

منابع: [نصب DaisyUI](https://daisyui.com/docs/install/)، [رنگ‌های معنایی](https://daisyui.com/docs/colors/)، [پوسته‌ها](https://daisyui.com/docs/themes/).

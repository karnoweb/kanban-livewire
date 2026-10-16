# آمار، گروه‌بندی و ویجت

```php
// تعریف مدل
->fields(['group' => 'team', 'priority' => 'priority'])
->metrics(['value' => ['aggregate' => 'sum', 'column' => 'amount']])
->moveGroups(fn ($record, string $group, $context): bool => in_array($group, ['Engineering', 'Support'], true))
```

```php
// Factory برد
->swimlanes(new \Karnoweb\Kanban\Data\SwimlaneDefinition(field: 'group', maxGroups: 30, allowMove: true))
```

آمار sum/avg/min/max در پایگاه داده و با همان محدوده خواندن اجرا می‌شود؛ آمار ۵۰ کارت با صفحه ۲۵تایی، روی همه ۵۰ کارت محاسبه می‌شود. قرارداد مستقل `ColumnMetricsProvider` آمار سفارشی میزبان را پشتیبانی می‌کند. در برد مختلط، نام آمار با نام منبع پیشوند می‌گیرد.

گروه‌ها فقط از ردیف‌های قابل مشاهده استخراج می‌شوند. نمایش فعلی ساده و تنبل است: انتخاب گروه از نوار ابزار، همه ستون‌ها را با فیلتر SQL همان گروه بارگذاری می‌کند؛ همه ردیف‌های گروه‌ها هم‌زمان دانلود نمی‌شوند. حداکثر گروه‌ها محدود است و `groups()` پرچم `hasMore` دارد. مقدار `__ungrouped__` برای null رزرو است.

در ستون مشترک چند مدل، کمترین ظرفیت سخت اعلام‌شده به همه منابع نگاشت‌شده اعمال می‌شود؛ منبع فاقد محدودیت اختصاصی نمی‌تواند ظرفیت ستون مشترک را دور بزند.

```php
$page = $provider->stageCards($context, 'todo', new \Karnoweb\Kanban\Data\BoardQuery(filters: ['group' => 'Engineering']));
```

تغییر گروه از متد `moveCardToGroup()` می‌گذرد و علاوه بر `allowMove`، مجوز `moveGroup` و guard مدل را می‌خواهد. نداشتن guard، رد قطعی است؛ میزبانی که جابه‌جایی بین تیم‌ها مجوز خاص دارد آن را در guard اعمال می‌کند.

برای ویجت، نما را در Factory فعال کنید:

```php
->settings(['views' => ['default' => 'compact', 'allowed' => ['board', 'list', 'compact']],
    'pagination' => ['page_size' => 5], 'features' => ['toolbar' => false]])
```

```blade
<livewire:kanban-compact board-key="models.compact" />
```

هر سه نما از همان مؤلفه و Provider استفاده می‌کنند. ستون‌ها قابلیت بستن/بازکردن با ذخیره ترجیح دارند. پنجره نمایش ستون با فعال‌بودن virtualization به حداقل ۵۰ یا دو برابر pageSize محدود است؛ کنترل قبلی/بعدی، همه کارت‌های قبلاً بارگذاری‌شده را قابل دسترسی نگه می‌دارد. «بارگذاری بیشتر» مستقل از این پنجره است. کارت‌های بارگذاری‌شده در وضعیت مؤلفه باقی می‌مانند؛ برای جلوگیری از رشد حافظه در استفاده بسیار طولانی، صفحه را دوباره بارگذاری کنید.

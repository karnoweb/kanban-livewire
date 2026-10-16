# گزارش توسعه سازمانی

مبنا: قرارداد نسخه ۲٫۱ در dev-helper و کد پوشه کاری. بررسی از ۲۰۲۶-۱۰-۱۶ آغاز شد؛ اجرای نهایی در ۲۰۲۶-۱۰-۱۷ انجام شد. تغییرات قبلی کاربر در رابط و نمونه‌ها حفظ شده‌اند.

## نتیجه نهایی اجرای محلی

| بررسی | نتیجه واقعی |
| --- | --- |
| مجموعه کامل PHP | PASS — ۲۲۱ تست، ۷۹۴ بررسی، صفر خطا/رد/skip |
| مرورگر Edge، Reverb واقعی، چند کاربر، لمس و موبایل | PASS — ۱۲ تست |
| MariaDB 11.8.2 | PASS — ۳ تست، ۹ بررسی |
| PostgreSQL 18.4 | PASS — ۳ تست، ۹ بررسی |
| ماتریس سناریوهای KNB101..150 | PASS — ۵۰ از ۵۰، استخراج از گزارش اجرای واقعی |
| Composer و صحت فاصله‌های diff | PASS |
| بررسی نحو PHP | PASS — ۲۶۶ فایل |

مقایسه با شروع کار: ۱۴۶ تست پایه به ۲۲۱ تست رسید؛ ۷۵ تست افزوده شد و تست‌های قبلی باقی ماندند. آزمون‌های پایگاه داده خارجی جدا از ۲۲۱ تست پیش‌فرض اجرا شدند. بعضی سناریوهای پذیرش از بخش‌های متفاوت یک تست چندحالته استفاده می‌کنند؛ ردیابی هر سناریو و سند فارسی آن مستقل است.

گزارش فایل‌های افزوده/اصلاح‌شده با **مسیر کامل و فاز و دلیل** در `ENTERPRISE-CHANGED-FILES.json` است؛ این فهرست، تغییرات قبلی و نامرتبط کاربر را به‌عنوان کار این توسعه گزارش نمی‌کند. گزارش قابل پردازش سناریوها در `ENTERPRISE-SCENARIO-RESULTS.json` و موتورهای پایگاه داده در `ENTERPRISE-DATABASE-RESULTS.json` است.

بنچمارک نهایی با اجرای هم‌زمان مرورگر روی همین ماشین: برای ۱۰۰هزار ردیف و ۱۰ ستون، ۲۱ query، ۲۶۰ رکورد ساخته‌شده، ۲۵۰ کارت و حدود ۳۵۶ میلی‌ثانیه سمت سرور. این زمان تحت بار هم‌زمان محلی است و معیار تضمین تولید نیست. تأخیر به‌روزرسانی زنده در ۲۰ نمونه، صدک ۹۵ برابر ۲۶۰ میلی‌ثانیه بود.

## فاز ۱ — قرارداد و اتصال مدل

مسیرهای افزوده‌شده:

- `D:/my-packages/kanban-livewire/src/Concerns/HasKanbanCards.php`: الزام یک تعریف مرکزی مدل.
- `D:/my-packages/kanban-livewire/src/Models/KanbanModelDefinition.php`: نگاشت، مجوز، فیلتر، مراحل، نمایش و hookهای سرور.
- `D:/my-packages/kanban-livewire/src/Models/KanbanModelRegistry.php`: ثبت صریح کلاس‌ها بدون ذخیره داده کاربر.
- `D:/my-packages/kanban-livewire/src/Models/CardIdentity.php`: شناسه منبع/رکورد با رمزگذاری استاندارد کاراکترهای جداکننده.
- `D:/my-packages/kanban-livewire/src/Models/BoardSources.php`: بازسازی تعریف منابع مجاز همان برد.

مسیرهای اصلاح‌شده: `D:/my-packages/kanban-livewire/src/Support/BoardBuilder.php`، `src/Support/BoardDefinitionValidator.php`، `src/Data/BoardDefinition.php` و `src/KanbanServiceProvider.php` برای sources/stageMap و انتخاب adapter اختیاری؛ `src/Data/KanbanSettings.php` و `src/Data/SecuritySettings.php` برای تنظیمات امنیتی و سقف‌ها. `src/Data/EnterpriseSettings.php` تنظیمات مسیر مدل را نگه می‌دارد؛ تنظیم ناامن به‌صراحت رد می‌شود.

نمونه مصرف واقعی: `workbench/app/Models/KanbanTask.php` + `workbench/app/Kanban/EloquentBoardFactory.php`. برد بدون sources همچنان روش قبلی را دارد. migration هسته: هیچ.

اعتبارسنجی مرحله‌ای: تست‌های پایه قبل تغییر، ۱۴۶ تست و ۴۹۴ بررسی با صفر خطا/رد/skip. تست‌های واحد و قابلیت‌های قبلی پس از اتصال اولیه، ۵۳ تست و ۱۴۶ بررسی پاس شدند.

## فاز ۲ — داده و کارایی

مسیرهای افزوده‌شده:

- `D:/my-packages/kanban-livewire/src/Providers/EloquentKanbanDataProvider.php`: خواندن محدود، شمارش و آمار SQL، فیلتر و مرتب‌سازی با فهرست مجاز.
- `D:/my-packages/kanban-livewire/src/Support/ModelCursor.php`: نشانگر رمزنگاری‌شده وابسته به هویت/برد/کوئری.
- `D:/my-packages/kanban-livewire/src/Support/ModelQueryCache.php`: کش اختیاری با ابطال پس از commit.
- `D:/my-packages/kanban-livewire/src/Contracts/ColumnMetricsProvider.php`: قرارداد آمار میزبان.
- `D:/my-packages/kanban-livewire/tests/Performance/EnterpriseQueryPerformanceTest.php`: اندازه‌گیری 10k/100k با ۱۰ ستون و baseline واقعی Inline.

نمونه: صفحه `/models/mixed` و راهنمای ۲۳. گزارش عددی در `ENTERPRISE-BENCHMARK.json`. بودجه ثابت تأییدشده: ۲۵۰ کارت، ۲۶۰ رکورد ساخته‌شده و ۲۱ query؛ زمان اندازه‌گیری‌شده ادعای محیط تولید نیست. baseline حافظه‌ای موجود با Provider پایگاه داده مقایسه همسان زمان DB نیست و در گزارش صریحاً برچسب دارد.

آزمون‌های اولیه پایگاه داده: ۲۹ تست و ۷۹ بررسی پاس. مرحله بعد، مجموعه کامل ۱۷۵ تست و ۵۷۳ بررسی پاس شد. نتایج نهایی جدیدتر در انتهای گزارش جایگزین معیار مرحله‌ای‌اند.

## فاز ۳ — عملیات و هم‌زمانی

مسیرهای افزوده‌شده:

- `D:/my-packages/kanban-livewire/src/Actions/EloquentKanbanActionHandler.php`: مجوز دوباره داخل تراکنش، نسخه، WIP، بایگانی، فرم دامنه، عملیات گروهی جزئی/اتمی.
- `D:/my-packages/kanban-livewire/src/Actions/SparseNumericRank.php` و `src/Contracts/RankStrategy.php`: رتبه عددی، کنترل anchors و بازتنظیم حداکثر ۶۴ همسایه؛ راهبرد میزبان قابل جایگزینی.
- `D:/my-packages/kanban-livewire/src/Actions/ModelOperationStore.php` و `src/Contracts/ModelOperationStore.php`: نتیجه عملیات در کش مشترک و مسیر جایگزینی با ذخیره دامنه.
- `D:/my-packages/kanban-livewire/src/Actions/ModelActionRejected.php`: بازگشت کامل تراکنش با نتیجه استاندارد.
- `D:/my-packages/kanban-livewire/src/Events/KanbanModelChanged.php`: رویداد سرور پس از commit.
- `D:/my-packages/kanban-livewire/tests/Integration/EnterpriseConcurrencyTest.php` و `tests/Fixtures/enterprise-child-move.php`: رقابت دو پردازش واقعی روی SQLite.

هماهنگ‌کننده کارت و مؤلفه‌های `HandlesMoves`, `HandlesCardCrud`, `HandlesBulkActions`, `HandlesCustomActions`, `LoadsBoardData` اصلاح شدند تا نسخه دیده‌شده و فرم ساختاریافته به همان قراردادهای قبلی برسند. حافظه جلوگیری از تکرار در مؤلفه و dedupe رویداد، به هویت/محدوده/برد وابسته شد؛ سرویس‌های مربوطه عمر درخواست دارند.

نتیجه رقابت: دقیقاً یک success و یک wip_limit. عملیات معمول میان ۱۰هزار همسایه فقط یک UPDATE دارد. نمونه مصرف: راهنمای ۲۴ و عملیات Complete در مدل نمونه. migration میزبان برای rank/version/archive اختیاری است.

## فاز ۴ — نمایش و تعامل

مسیرهای افزوده‌شده: `D:/my-packages/kanban-livewire/src/Data/SwimlaneDefinition.php`، `src/Contracts/SwimlaneProvider.php` و `src/Livewire/CompactBoard.php`.

مسیرهای اصلاح‌شده:

- `D:/my-packages/kanban-livewire/src/Livewire/Board.php`: گروه، بستن ستون، قالب مدل و تنظیم گزینه‌های مجاز.
- `D:/my-packages/kanban-livewire/src/Livewire/Concerns/LoadsPages.php`: پنجره محدود با کنترل قبلی/بعدی؛ هیچ کارت بارگذاری‌شده‌ای غیرقابل دسترس نمی‌شود.
- `D:/my-packages/kanban-livewire/resources/js/board-interactions.js`: لمس، اسکرول هنگام drag، جداسازی بردها و حفظ منوی عملیات هنگام تازه‌سازی.
- `D:/my-packages/kanban-livewire/resources/js/virtualizer.js`: پنجره قابل جابه‌جایی و حفظ اسکرول.
- `D:/my-packages/kanban-livewire/resources/views/components/`: فیلتر منبع/مرحله/گروه، آمار، collapse و قالب‌های مدل با همان اجزای قبلی.
- `D:/my-packages/kanban-livewire/src/Realtime/RealtimePublisher.php`: رد منبع خارج از برد و ارسال invalidation بدون شناسه ردیف برای مشترکان با دسترسی ردیفی متفاوت.
- `D:/my-packages/kanban-livewire/resources/css/kanban.css`، `resources/dist/` و `lang/en/kanban.php`، `lang/fa/kanban.php`: ظاهر ویجت، دستگیره لمس و متن‌های جدید.

نمونه‌های مستقل Task/Ticket/Class با نگاشت فیلدهای متفاوت در workbench ساخته شدند. تنها migration جدید در `workbench/database/migrations/2026_10_17_000001_create_demo_model_cards.php` است. روش قبلی دموها حفظ شده است.

گروه‌بندی UI فعلی انتخاب تنبل یک گروه است؛ بارگذاری همه ردیف‌های همه گروه‌ها انجام نمی‌شود. تغییر گروه، مجوز و guard صریح می‌خواهد. اتصال تقویم، قرارداد میزبان دارد؛ پکیج تقویم خارجی در این مخزن نصب نیست و آزمایش آن ادعا نمی‌شود.

## فاز ۵ — تست و مستندات

راهنماهای ۲۱ تا ۲۷، README، CHANGELOG، UPGRADE، مرجع عمومی و تصمیم‌های معماری به‌روزرسانی شدند. ۵۰ سند فارسی `tests/Scenarios/KNB101.md` تا `KNB150.md` از نتیجه واقعی تست‌های PHP و مرورگر تولید می‌شوند؛ هر سند متد تست، داده و مقدار مورد انتظار و نتیجه را دارد. نگاشت کامل در `ENTERPRISE-SCENARIO-RESULTS.json` است؛ تست‌های قبلی حذف نشده‌اند.

دستورهای نهایی:

```powershell
composer validate --strict --no-check-version
node scripts/build.mjs
$env:KANBAN_WRITE_BENCHMARK='1'
php vendor/bin/phpunit --no-coverage --log-junit .phpunit.cache/enterprise-junit.xml
$env:KANBAN_E2E_PORT='18100'
$env:KANBAN_E2E_REVERB_PORT='18101'
$env:WORKBENCH_DB=Join-Path $PWD '.phpunit.cache/enterprise-e2e.sqlite'
npx playwright test
node scripts/report-enterprise-scenarios.mjs
```

`--no-check-version` تنها هشدار توصیه‌ای Composer درباره فیلد version موجود را حذف می‌کند؛ اعتبارسنجی سخت ساختار و وابستگی‌ها حفظ می‌شود. مقدار نسخه فعلی برای سازگاری حذف نشد.

آزمون موتورهای دیگر در `tests/Database/EnterprisePortableDatabaseTest.php` با پایگاه داده موقت مستقل `kanban_agent_test_*` اجرا شد: MariaDB 11.8.2، سه تست/۹ بررسی پاس؛ PostgreSQL 18.4، سه تست/۹ بررسی پاس؛ صفر خطا/رد/skip. پایگاه‌های موقت حذف شدند. نام/رمز پایگاه برنامه میزبان استفاده نمی‌شود. این آزمون خارج از مجموعه پیش‌فرض است و فقط با متغیرهای صریح `KANBAN_TEST_DB_DRIVER` و `KANBAN_TEST_DB_DATABASE` اجرا می‌شود.

CI برای PHP 8.3/8.4، مرورگر Edge روی Windows و تست MariaDB/PostgreSQL پیکربندی شده است. اجرای سرویس CI خارجی در این محیط انجام نشده است.

## محدودیت‌های تأیید و انتشار

- کارایی ۱۰۰هزار ردیف و رقابت دو پردازش فقط روی SQLite اندازه‌گیری شده؛ آزمون عملکردی MariaDB/PostgreSQL جای بنچمارک یا رقابت آن‌ها نیست.
- آزمون مرورگر روی Edge با شبیه‌سازی لمس و عرض ۳۹۰ است؛ دستگاه واقعی iOS/Android یا مرورگرهای دیگر آزمایش نشده‌اند.
- مدل دارای version غیرعددی یا عملیات گروهی اتمی روی چند اتصال DB، handler میزبان می‌خواهد. ترتیب دستی منابع مختلط بدون adapter مشترک عمداً غیرفعال است.
- پس از خرابی پردازش بین commit و ذخیره نتیجه، وضعیت عملیات نامعلوم می‌ماند؛ retry خودکار انجام نمی‌شود. بازیابی قطعی domain-owned است.
- فهرست پنج فاز در محیط محلی تکمیل و بررسی شد؛ هیچ قابلیت الزامی به نسخه بعد حواله نشده است. اجرای CI خارجی، دستگاه واقعی موبایل و اتصال پکیج تقویم خارجی، تأییدنشده باقی مانده‌اند و نباید PASS اعلام شوند.
- پنجره نمایش، تعداد عناصر رندرشده را محدود می‌کند؛ وضعیت کارت‌هایی که کاربر با load more دریافت کرده در حافظه مؤلفه باقی می‌ماند.
- در اجرای اولیه مرورگر، Testbench متغیر محیط پایگاه داده را در HTTP نمی‌خواند و بعضی عملیات تست روی دمو در حال اجرا انجام شد. fallback متغیر محیط و پورت‌های جدا اصلاح شدند؛ اجرای نهایی فقط پایگاه آزمایشی جدا را تغییر می‌دهد. داده دمو بدون دانستن وضعیت قبلی به‌صورت حدسی بازنشانی نشد.

این تغییر انتشار یا tag ایجاد نمی‌کند. نتیجه محلی و موارد تأییدنشده باید همراه نسخه بازبینی شوند؛ آماده انتشار بودن محیط تولید از سبزبودن تست‌های محلی نتیجه‌گیری نمی‌شود.

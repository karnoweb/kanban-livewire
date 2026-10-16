# دستور اجرایی Agent — تکمیل سازمانی Karnoweb Livewire Kanban

> **نسخه سند:** 2.1 — 2026-10-16\
> **مبنای بررسی:** محتوای واقعی `kanban-livewire.zip` ارسالی کاربر؛ این سند دستور توسعه است، نه گزارش پیاده‌سازی‌شده.\
> **نام فعلی Composer:** `karnoweb/laravel-livewire-kanban`\
> **Namespace:** `Karnoweb\Kanban`\
> **محیط هدف موجود:** PHP ^8.3، Laravel 13 و Livewire ^4.0 طبق `composer.json` فعلی.\
> **قاعده انتشار:** پنج فاز، یک خروجی کامل؛ هیچ قابلیت الزامی به «نسخه بعد» منتقل نشود.

## 0. مأموریت و شیوه شروع

تو مهندس ارشد PHP/Laravel/Livewire هستی. **در مخزن موجود کار کن؛ بسته جدید یا معماری موازی نساز.** ابتدا کد، تمام `docs/`، `README.md`، تست‌ها و پکیج فعلی را بخوان. سند مکمل `KANBAN-ENTERPRISE-CONFIG-AND-USAGE-FA.md` قرارداد محصول و مثال‌های استفاده نهایی را تعیین می‌کند. اگر یک API پیشنهادی با معماری موجود ناسازگار بود، راهکار سازگارتر را پیاده کن، ولی نام عمومی نهایی و مستندات را همزمان یکسان نگه دار و دلیل تصمیم را در ADR (ثبت تصمیم معماری) مستند کن.

### واقعیت‌های کد فعلی؛ آن‌ها را دوباره نساز

- `BoardBuilder`, `BoardRegistry`, `BoardFactory`, `KanbanManager` و `Kanban::init()` موجودند.
- `DataProvider` دارای `board`, `stages`, `stageCards`, `listCards`, `counts`, `card`, `archives`, `capabilities`, `operationStatus` است.
- `ActionHandler::execute(ActionCommand, BoardContext): ActionResult` وجود دارد.
- `BoardContext` شامل `boardKey`, `scopeKey`, `actorId`, `instanceId` است.
- `BoardQuery` دارای `view`, `stageId`, `cursor`, `limit`, `search`, `filters`, `sort`, `direction`, `queryHash` است.
- `CardData`, `StageData`, `PageResult`, `MoveRequest`, `KanbanSettings`، وضعیت‌ها، امنیت پایه، Realtime، صفحه‌بندی، WIP، Bulk و Viewهای Board/List موجودند.
- `InlineDataProvider` برای داده‌های کوچک باقی بماند؛ برای دیتابیس واقعی یک Provider جدید ضروری است.
- ثبت واقعی برد با `BoardRegistry::register($key, BoardFactory::class)` و نمایش با `<livewire:kanban-board board-key="..." />` انجام می‌شود.
- `config/kanban.php` دارای `features`, `appearance`, `interaction`, `realtime`, `security`, `limits`, `sort_fields`, `filter_fields` است.
- `KanbanSettings::fromArray()` باید برای هر کلید جدید و همه کلیدهای موجود بررسی شود؛ هیچ تنظیمی نباید بی‌صدا حذف شود.
- کامپوننت کنونی `Livewire\Board` برای هر درخواست `BoardContext` را از سرور بازسازی می‌کند؛ این اصل حفظ شود.
- وضعیت تست موجود **فقط گزارش مخزن** است؛ صحت را مستقل در محیط خودت با تست مجدد اثبات کن.

### نتیجه مورد انتظار

مدل‌های `Task`, `Ticket`, `EducationalClass`, `Order` یا سایر مدل‌های Eloquent با افزودن Trait `HasKanbanCards` و تعریف **یک متد مرکزی** `kanbanDefinition()` بتوانند قوانین، نمایش، فیلترها و عملیات خود را معرفی کنند؛ پکیج در بردهای مشخص، فقط منابع مجاز را واکشی کند و تنها رکوردهای قابل مشاهده را با کوئری سطح دیتابیس برگرداند. در عین حال مصرف‌کنندگان قدیمی `DataProvider` و `ActionHandler` بدون شکستن API پابرجا باشند.

## 1. اصول غیرقابل‌مذاکره

1. هیچ جدول یا Migration اجباری در هسته پکیج؛ هیچ نیاز اجباری به `branch_id`، `tenant_id`، `user_id` یا ساختار جدول خاص. `ScopeResolver` مستقل از شعبه/مستأجر بماند.
2. Trait یک **مسیر اختیاری سهولت استفاده** است، نه جایگزین یا محدودکننده قراردادهای مستقل موجود.
3. وجود Trait به‌تنهایی مدل را عمومی نمی‌کند: مدل باید در رجیستری منابع ثبت شود، سپس صریحاً برای همان برد مجاز شود؛ «همه» فقط با تنظیم صریح مجاز است.
4. کوئری‌ها باید ابتدا scope مجاز، سپس محدودیت منبع و ستون و فیلتر را در DB اعمال کنند؛ **never** `Model::all()`، بارگذاری کل برد یا فیلترکردن tenant/permission پس از `get()`.
5. عملیات نوشتن، نمایش جزئیات، Bulk، جست‌وجو، شمارش و Realtime باید همان مجموعه منابع مجاز را رعایت کنند. درخواست دستکاری‌شده از کلاینت نتواند به منابع برد دیگر دسترسی پیدا کند.
6. `ActionCommand`، `ActionResult`, `BoardContext`, `BoardQuery`, `PageResult` و خط‌مشی deny-by-default بدون تغییر مخرب حفظ شوند.
7. تعریف مدل در سمت سرور، در هر درخواست بازسازی شود؛ Closureها و مدل Eloquent در وضعیت Livewire serialize نشوند.
8. ثبت تغییرات domain-owned است؛ بایگانی، مسئول، کامنت، فایل، چک‌لیست و فعالیت‌های حوزه کسب‌وکار در میزبان بمانند. پکیج فقط UI و قرارداد اتصال ارائه کند.
9. امنیت row-level، N+1، هم‌زمانی و idempotency (جلوگیری از اجرای تکراری عملیات) باید با تست قابل اثبات باشند.
10. همه قابلیت‌های این سند برای خروجی نهایی الزامی‌اند؛ اختیاری بودن یعنی هر برد می‌تواند آن را خاموش کند، نه اینکه پیاده‌سازی به آینده منتقل شود.

## 2. قرارداد محصول برای Trait — کمترین کدنویسی مصرف‌کننده

### 2.1 ساختار پیشنهادی، الزامی از نظر رفتار

- Trait: `src/Concerns/HasKanbanCards.php`
- تعریف مدل: `src/Models/KanbanModelDefinition.php` (یا `src/Data/` اگر با الگوی فعلی هماهنگ‌تر است؛ یک نام نهایی انتخاب کن).
- رجیستری مدل‌ها: `src/Models/KanbanModelRegistry.php`.
- Provider مدل: `src/Providers/EloquentKanbanDataProvider.php`.
- Handler مدل: `src/Actions/EloquentKanbanActionHandler.php`.
- اتصال BoardBuilder: `sources(array $keys)` + سازگاری کامل `provider()`/`handler()` فعلی.
- امکان ثبت: `KanbanModels::register('tasks', Task::class)` یا سرویس معادل ثبت‌شده در Container؛ API واحد و مستند.

### 2.2 رفتار `kanbanDefinition()`

این متد باید با **مقادیر پیش‌فرض مفید**، موارد زیر را به‌صورت تعریف تایپ‌شده بازگرداند:

- شناسه/عنوان/توضیح/مرحله/اولویت/مسئول/برچسب/تاریخ و نگاشت ستون‌های غیرهم‌نام؛ پشتیبانی از Accessor امن.
- منبع حالت یا Stage: Enum، داده ثابت، Provider اختصاصی یا نگاشت مقادیر دلخواه.
- نگاشت وضعیت به ستون برد؛ پشتیبانی از چند مدل با مراحل متفاوت و تعریف صریح `stageMap`.
- مجوز query خواندن (scope قابل اجرا در SQL) و مجوز عملیات مشاهده/ایجاد/ویرایش/جابجایی/مرتب‌سازی/آرشیو/بازیابی/Bulk، همگی deny-by-default در غیاب اجازه معتبر.
- فیلترهای تعریفی: ستون، مسئول، اولویت، بازه، وضعیت، متن، دسته‌بندی و فیلد سفارشی دارای allowlist.
- sort fields و انتخاب روش مرتب‌سازی برای مدل، پیش‌فرض stable و قابل صفحه‌بندی.
- view کارت، فرم، عملیات‌های اختصاصی، badges، metric aggregations؛ با fallback پیش‌فرض.
- Hookهای قبل/بعد از عملیات با تفکیک validation / persistence / afterCommit.
- روش conflict/version و operation-id؛ بدون الزام به افزودن ستون مگر مصرف‌کننده از قابلیت مربوطه استفاده کند.

**معیار سادگی:** یک `Task` ساده با Trait + یک متد + ثبت مدل + تعریف برد فعال شود؛ سرویس‌ها/Policy/Scope سفارشی فقط زمانی لازم شوند که قوانین پروژه پیچیده‌اند. تعریف Closure داخل `kanbanDefinition()` فقط هنگام اجرای سرور مجاز است و به Livewire state/Cache داده اجرایی منتقل نمی‌شود. برای کانفیگ قابل کش، descriptor کلاس/متد را ترجیح بده.

### 2.3 حالت بدون Trait

- `BoardFactory` + `DataProvider` + `ActionHandler` فعلی به‌صورت کامل پابرجا بمانند.
- پشتیبانی اختیاری از `ModelSource` و Providerهای API/غیردیتابیسی برای برد ترکیبی؛ مجبورکردن همه منابع به Eloquent ممنوع.
- اسکن خودکار همه کلاس‌های پروژه یا جدول‌ها ممنوع؛ ثبت declarative و قابل مشاهده باشد.
- رجیستری منابع در Octane/queue/worker دارای تعریف ثابت باشد؛ داده کاربر/Scope در singleton نگهداری نشود.

## 3. انتخاب منابع در هر برد؛ تصمیم حیاتی

سه لایه باید مستقل باشند:

1. **Model Registry:** مدل یا منبع چه اسلاگی دارد و چه نحوه اتصالی؟
2. **Board Source Allowlist:** کدام اسلاگ‌ها فقط برای این `boardKey` مجازند؟
3. **UI Filters:** کاربر از بین منابع مجاز کدام را فعلاً می‌بیند؟

مثال‌های پذیرش: `tasks.board` فقط `tasks`؛ `tickets.board` فقط `tickets`؛ `operations.board` شامل `tasks,tickets`؛ `education.board` فقط `educational_classes`. حتی وقتی منبع جدید در رجیستری اضافه می‌شود، بردهای قدیمی نباید ناگهان آن را دریافت کنند.

- شناسه کارت در برد ترکیبی باید namespaced و بی‌ابهام باشد، مثل `tasks:17`, `tickets:17`؛ شناسه خام `17` نباید مدل دیگری را تحت تأثیر قرار دهد.
- لیست مراحل مشترک در مدل‌های مختلف بدون `stageMap` صریح مجاز به ترکیب نیست؛ در برد تک‌مدلی مراحل می‌تواند خودکار از Enum تولید شود.
- افزودن منبع به `filters` کلاینت هرگز موجب دسترسی به منبع خارج از allowlist نشود.
- در `card`, `counts`, `archives`, `operationStatus`, `ActionDispatcher`, Bulk, Broadcast payload و cache key محدودیت منابع اعمال شود.
- برای برد مختلط اگر مرتب‌سازی دستی بین دو مدل ذخیره‌ساز مشترک ندارد، با پیام شفاف آن عملیات را غیرفعال کن یا adapter بین‌منبعی صریح بخواه؛ **ترتیب نادرست و نیمه‌ذخیره‌شده ممنوع**.

## 4. قرارداد بارگذاری داده و کوئری

- Eloquent provider از `BoardContext` و `BoardQuery` فعلی شروع کند؛ Scope امنیتی باید قبل از pagination، search و count در SQL اعمال شود.
- صفحه‌بندی **مستقل هر ستون**؛ keyset/cursor pagination با ترتیب پایدار `(sort_value, id)` و در حالت rank `(rank, id)`؛ نه offset سنگین برای برد بزرگ.
- Cursor opaque و signed/tamper-resistant؛ به scopeHash، boardKey، stage، source selection، filters، search، sort، direction و queryHash متصل باشد. Cursor منقضی یا متعلق به query دیگر صریحاً رد/بازنشانی شود.
- N+1 با انتخاب فیلدها و eager loading محدود؛ هیچ query per card برای badge/assignee در حالت پایه.
- `totalCount` و `loadedCount` و `hasMore` مستقل و صحیح؛ اگر شمارش تقریبی است `countExact=false` تنظیم شود.
- counts و metrics در SQL و scope مجاز؛ نه براساس آیتم‌های loaded. قابلیت memoization فقط با cache key شامل scope/actor/board/sources/query/version.
- read-only، unknown source و empty allowlist: رفتار fail-closed، zero data و خطای مناسب بدون افشای داده.
- امکان eager load و select/joins سفارشی از definition؛ جلوگیری از wildcard filters/sorts، SQL injection و تزریق ستون ورودی کاربر.
- تست dataset حداقل 100,000 کارت و حداقل 10 ستون با کوئری قابل اندازه‌گیری، همراه baseline قبلی و جدید؛ اعداد ثابت acceptance با benchmark واقعی در CI محیط مشخص شوند نه اعداد ادعایی.
- حذف فیلتر front-end روی لیست‌های حجیم؛ فیلتر سمت SQL.

## 5. مرتب‌سازی، گردش‌کار و WIP

### مرتب‌سازی

- حفظ `beforeCardId`, `afterCardId`, `expectedVersion`, `operationId` از `MoveRequest` فعلی.
- پشتیبانی از rank strategy قابل تعویض: LexoRank/fractional rank یا روش sparse numeric در صورت انتخاب میزبان؛ در نسخه پیش‌فرض، write amplification حداقلی.
- حالت بدون ستون rank: مرتب‌سازی دستی اختیاری و با خطای واضح غیرفعال؛ هیچ Migration تحمیلی نیست.
- کشف collision، rebalance فقط در ناحیه لازم، ترتیب پایدار و concurrency locks در transaction.
- داخل بردهای ترکیبی cross-model ordering تنها با storage adapter اختیاری معتبر فعال شود.

### گردش‌کار

- حالت Enum (BackedEnum) از مدل تشخیص داده شود؛ عنوان، رنگ، ترتیب، visible/readOnly و WIP از تعریف مدل قابل استخراج باشند.
- قواعد انتقال در دو سطح: UI برای پیش‌نمایش، Handler سرور برای اعتبار قطعی؛ default deny.
- انتقال با guard دامنه، field validation، confirmation form، note و updated version؛ در صورت خطا UI rollback/refresh.
- WIP سخت در transaction و lock؛ دو کاربر برای آخرین جایگاه مجاز نباید هر دو موفق شوند.
- جلوگیری از retry تکراری عملیات؛ status unknown recovery با operationStatus.

### metrics و extras

- `ColumnMetricsProvider` برای sum/avg/min/max و KPIهای سفارشی؛ اجرای DB-side و actor/tenant scoped.
- `SwimlaneDefinition` برای گروه‌بندی اختیاری براساس assignee/team/priority/branch (بدون فرض وجود branch) و permission-safe drag؛ lazy grouped loading؛ فاز نخست می‌تواند UI ساده داشته باشد ولی قابلیت و تست کامل باید قبل از انتشار وجود داشته باشد.
- card badges, assignees, attachments, checklist summary, dependency status, contextual actions؛ داده واقعی و ذخیره‌سازی متعلق به میزبان.
- عملیات مختص مدل مثل `approve`, `reject`, `complete` فقط با allowlist و handler میزبان؛ از نام متد خامِ ورودی کاربر اجرا نکن.
- تاریخچه تغییر وضعیت: پکیج event/hook قابل مصرف منتشر کند، audit sink موجود حفظ شود، دیتابیس لاگ اجباری نه.
- Calendar integration: مدل می‌تواند هم `HasKanbanCards` و هم `HasCalendarEvents` داشته باشد؛ هیچ package-to-package dependency؛ یک رکورد دامنه مشترک.
- Timeline/Gantt/Diagram اگر به‌عنوان نمایش اختیاری ارائه می‌شوند، قرارداد قابل توسعه داشته باشند؛ موتور Gantt/مدیریت پروژه خارج از هسته است.

## 6. رابط کاربری و تجربه کاربری

- سه نوع مصرف: board کامل، list کامل و ویجت برد مختصر با API مشخص، بدون کپی کامل منطق.
- کارت‌های غنی و خوانا، اندازه و تراکم قابل تنظیم؛ RTL/LTR، Light/Dark و `prefers-reduced-motion`.
- ستون‌های collapsible، drag auto-scroll افقی/عمودی، keyboard move alternative و touch-friendly interaction؛ خطا/موفقیت/empty/loading/partial data مشخص.
- Column metrics، نمایش `loaded/total`، +load more، virtual window پویا و retained scroll position.
- status filter auto-generation و مخفی‌شدن فیلتر منبع وقتی یک منبع فعال است.
- تنظیمات نمایش و قالب هر برد نباید کنترل امنیت و دسترسی را دور بزنند.
- چند نمونه Board در یک صفحه بدون تداخل JS keys, DOM ids, Echo listeners, tooltip/modal state.

## 7. امنیت، پایداری و هم‌زمانی

- `ScopeResolver` موجود تنها مرجع actor/scope در سرور؛ هیچ `scopeKey`, `actorId`, `source` یا model class از props قابل اعتماد نیست.
- هر Eloquent query باید WHERE visibility scopes داشته باشد و برای model-specific Policies از Laravel Gate یا callback server-only استفاده کند.
- کارت ناموجود یا غیرمجاز پاسخ indistinguishable؛ اطلاعات عنوان و metadata لو نرود.
- update/reorder: مجوز در transaction مجدداً چک؛ قفل و expectedVersion در برابر TOCTOU.
- index پیشنهادی در راهنما مستند شود، ولی Migration خودکار تولید نشود.
- Realtime channel authorization و event payload با board/scope/source allowlist؛ تغییر مدل‌های غیرمجاز نباید وجود آن‌ها را افشا کند.
- idempotency guard و transaction afterCommit در همه عملیات جدید؛ رویدادهای تکراری ایمن.
- محدودیت حداکثر payload/bulk/count/search موجود حفظ شود؛ کش اطلاعات scoped و ابطال معتبر.
- ناامنی mass assignment، arbitrary where SQL, unsafe HTML و URLهای غیرمجاز بررسی شود.
- تست روی SQLite + در صورت در دسترس بودن MySQL/PostgreSQL؛ تفاوت locking در داکیومنت ثبت شود.

## 8. برنامه اجرایی — دقیقاً ۵ فاز

### فاز ۱ — قراردادها، Trait، Registry و حفاظت از سازگاری

**اول تغییرات**
- ایجاد/تکمیل `src/Concerns/HasKanbanCards.php`، `src/Models/KanbanModelDefinition.php`، `src/Models/KanbanModelRegistry.php` و قراردادهای scope، policy، stage mapping.
- ثبت مدل با API ساده، `BoardBuilder::sources()`، BoardFactory جدید نمونه بدون از دست دادن factory قبلی.
- طراحی و اعتبارسنجی `sourceId:recordId` و mapping stages.
- تست ثابت‌ماندن روش فعلی Builder/Factory/DataProvider/ActionHandler و parser settings.

**قبولی:** مدل جدید فقط با افزودن Trait + یک متد + ثبت مدل + برد محدود، بدون دسترسی ناخواسته به سایر بردها نمایش داده شود. نمونه old API همچنان کار کند.

### فاز ۲ — Eloquent Provider + Query Performance

**اول تغییرات**
- Eloquent provider, query compiler, visibility filters, per-column cursor pagination, counts, aggregate metrics.
- eager loading, whitelists, encoded cursor/context binding, DB-safe filters/sorts, auto generated filters.
- Benchmarks برای 10k/100k، search/filter/change stage، multi source.

**قبولی:** هیچ full table load، N+1 یا نشت داده وجود نداشته باشد و هیچ query برای منابع انتخاب‌نشده اجرا نشود.

### فاز ۳ — Actions, ordering, workflows, metrics

**اول تغییرات**
- Handler و Action adapters، متدهای permission و domain hooks، confirmation، idempotency، versioning، WIP transaction.
- rank adapter و rebalance، Enum stage discovery، cross-source stage mapping، metrics، custom actions، audit event after commit.
- archive/restore/bulk/reorder across sources، سیاست‌های mixed ordering.

**قبولی:** رقابت هم‌زمان و duplicate requests بی‌خطر باشند و عملیات فقط روی مدل و برد مجاز اعمال شود.

### فاز ۴ — UI/UX, Swimlane, Calendar interoperability

**اول تغییرات**
- UI کارت‌های غنی، گروه‌بندی Swimlane، toolbar/filter automatic، column metrics، widgets یا compact board، auto-scroll، keyboard/touch.
- templates برای Task/Ticket/CRM/EducationalClass، خطای board mixed، view customization و Realtime multi-board.
- Integration contract با تقویم بدون وابستگی اجباری.

**قبولی:** نمای Board/List/compact در RTL/LTR و Light/Dark روی desktop/tablet/mobile، و چند برد یک صفحه با هم بدون تداخل کار کنند.

### فاز ۵ — تست، مستندات مصرف‌کننده، انتشار

**اول تغییرات**
- مستند `docs/21-trait-eloquent-guide.md`, `docs/22-multi-source-boards.md`, `docs/23-eloquent-performance.md`, `docs/24-workflow-enum-ranking.md`, `docs/25-metrics-swimlanes.md`, `docs/26-calendar-integration.md`, `docs/27-migration-and-upgrade.md` را بساز.
- README واقعی و دستور composer install/config publishing/assets با new API؛ مثال‌ها باید قابل اجرا باشند.
- ماتریس سازگاری، تست مرورگر، CI و benchmark، rollback upgrade، CHANGELOG و release checklist.

**قبولی:** همه فازها و سناریوها سبز، اعداد تست و محیط در گزارش ثبت، هیچ feature stub و هیچ فایل docs فاقد مثال عملی نماند.

## 9. تست‌های سناریویی الزامی (حداقل ۵۰ مورد)

هر سناریو یک تست اتوماتیک PHP، در صورت نیاز مرورگر، و یک Markdown فارسی با setup، عملیات، مقادیر عددی، نتیجه مورد انتظار و نتیجه اجرا داشته باشد. نام‌گذاری `tests/Scenarios/KNB101...Test.php` و `tests/Scenarios/KNB101_....md` توصیه می‌شود؛ سناریوهای قبلی حذف نشوند.

### اتصال مدل (۱۰ سناریو)
1. Trait + متد + register روی Task، نمایش صحیح.
2. مدل بدون Trait از registry رد شود، مگر provider مستقل صریح.
3. مدل ثبت‌شده بدون مجوز برد لود نشود.
4. برد فقط Task، مدل Class صفر query.
5. برد فقط Class، مدل Task صفر query.
6. برد Task+Ticket، شناسه عددی مشترک تداخل نداشته باشد.
7. Enum شناخته‌شده ستون‌ها را به ترتیب صحیح تولید کند.
8. stageMap برد مختلط مقادیر مختلف را یکپارچه کند.
9. stageMap ناقص با خطای واضح رد شود.
10. یک صفحه چند برد با منابع و actor/scope مجاز مختلف، بدون آلودگی داده.

### خواندن/صفحه‌بندی/کوئری (۱۲ سناریو)
11. 100,000 کارت، بارگذاری نخست محدود به page size.
12. pagination هر ستون مستقل، count ثابت/صحیح.
13. cursor متعلق به board دیگر رد شود.
14. cursor متعلق به scope/actor دیگر رد شود.
15. تغییر filters/search، cursor قدیمی رد شود.
16. sort `(rank,id)` بدون duplicate/missing بین صفحات.
17. سرچ فارسی و انگلیسی با allowlisted columns.
18. filter priority/assignee/status روی DB و پیش از paginate.
19. aggregate sum محدود به tenant/actor.
20. عدم N+1 هنگام 25 کارت با assignee/badge.
21. readonly/unknown/empty sources fail-closed.
22. listCards و archives مجوز یکسان با stageCards داشته باشند.

### نوشتن/ترتیب/گردش‌کار (۱۶ سناریو)
23. انتقال مجاز تغییر وضعیت و version.
24. انتقال غیرمجاز با drag fake رد شود.
25. تغییر کارت به source خارج از برد رد شود.
26. create در برد تک‌منبعی source خودکار انتخاب شود.
27. create در برد چندمنبعی source لازم/مجاز را دریافت کند.
28. update کارت tenant دیگر indistinguishable not-found.
29. archive/restore از مجوز برد تبعیت کند.
30. custom approve با domain policy و form validation.
31. confirmed move با note و cancel بدون تغییر DB.
32. duplicate operation id فقط یک side effect.
33. stale expectedVersion conflict و refresh.
34. دو نفر برای آخرین WIP slot فقط یک موفقیت.
35. reorder داخل 10k کارت بدون rewrite کل ستون.
36. collision/rank rebalance پایدار.
37. mixed-source reorder بدون adapter خاموش با پیام روشن.
38. bulk ترکیبی فقط روی منابع مجاز با گزارش partial/atomic.

### UI/Realtime/Enterprise (۱۲ سناریو)
39. Board/List/compact UI render صحیح.
40. swimlane بر اساس team/priority با scope صحیح.
41. جابه‌جایی در swimlane ممنوع مجوز را دور نزند.
42. نمایش metrics ستون با داده واقعی، نه loaded count.
43. ستون collapse/expand، حفظ state.
44. touch drag و keyboard alternative.
45. RTL/LTR + Light/Dark + mobile.
46. چند برد یک صفحه Echo listener مشترک نداشته باشند.
47. Broadcast source غیرمجاز هیچ payload حساس نداشته باشد.
48. Event afterCommit، تکراری ارسال نشود.
49. همان Task با Trait تقویم و Kanban بدون وابستگی مستقیم.
50. backward compatibility قدیم، تست‌های فعلی و browser suite تماماً سبز.

### تست‌های تکمیلی بحرانی

- fuzz روی identifiers/cursors/filters؛ source alias injection؛ unauthorized count side channel؛ rapid clicking/drag twice؛ property hydration tamper؛ cleanup چندبردی JS؛ scope isolation در worker طولانی‌مدت؛ empty state؛ unauthorized archived card؛ sort under concurrent writes؛ metrics tenant leak؛ timeouts/unknown status retry.

## 10. فایل‌های موجود که باید مبنای تغییر باشند

**هسته**: `src/Support/BoardBuilder.php`, `src/Support/BoardRegistry.php`, `src/Support/BoardDefinitionValidator.php`, `src/KanbanServiceProvider.php`.

**قراردادها/داده**: `src/Contracts/DataProvider.php`, `src/Contracts/ActionHandler.php`, `src/Contracts/BoardFactory.php`, `src/Contracts/ScopeResolver.php`, `src/Data/BoardDefinition.php`, `src/Data/BoardContext.php`, `src/Data/BoardQuery.php`, `src/Data/CardData.php`, `src/Data/StageData.php`, `src/Data/PageResult.php`, `src/Data/ActionCommand.php`, `src/Data/ActionResult.php`, `src/Data/KanbanSettings.php`.

**نمایش و عملیات**: `src/Livewire/Board.php`, `src/Livewire/Concerns/LoadsBoardData.php`, `src/Livewire/Concerns/LoadsPages.php`, `src/Livewire/Concerns/HandlesMoves.php`, `src/Actions/MoveCoordinator.php`, `src/Actions/ActionDispatcher.php`, `src/Actions/TransitionPolicy.php`, `resources/views/components/`, `resources/js/`.

**کانفیگ/تست**: `config/kanban.php`, `tests/Feature/`, `tests/Scenarios/`, `tests/Browser/`, `tests/Performance/`, `e2e/kanban.spec.mjs`, `.github/workflows/tests.yml`.

## 11. خروجی Agent در پایان هر فاز

بدون خلاصه‌گویی مبهم، در گزارش هر فاز بنویس:

1. فایل‌های اضافه/اصلاح‌شده با مسیر کامل و دلیل هر تغییر.
2. API عمومی جدید و نمونه مصرف واقعی (نه pseudo-code).
3. Migration: آیا لازم است؟ اگر فقط در اپ مثال لازم است، دقیقاً کدام فیلد و چرا؟
4. تمام دستورهای تست اجراشده و تعداد PASS/FAIL/SKIP؛ اگر اجرا نشد دلیل واقعی.
5. صورت‌مسئله امنیت/کارایی پوشش‌یافته، سناریوهای تأییدشده و محدودیت‌ها.
6. تغییرات ناسازگار و مسیر ارتقا؛ بدون تغییر شکستن‌کننده بی‌دلیل.
7. فهرست موارد باز فاز بعدی. آخر فاز پنجم هیچ مورد الزامی باز نماند.

## 12. فرمان نهایی شروع

ابتدا مخزن را audit کن؛ یک جدول gap بین قابلیت‌های موجود و قابلیت‌های این سند بساز، بعد وارد فاز اول شو و همه پنج فاز را پشت سر هم پیاده‌سازی کن. هیچ قابلیت اصلی را به نسخه بعد منتقل نکن. از اسامی و رفتار مستند `KANBAN-ENTERPRISE-CONFIG-AND-USAGE-FA.md` به‌عنوان acceptance contract استفاده کن. در صورت تعارض، از تکرار معماری و شکستن قراردادهای موجود پرهیز کن و در تصمیم‌های فنی دلیل بیاور. **«انجام شد» فقط با کد اجراشده، تست واقعی و مستند دقیق پذیرفته می‌شود.**

<?php

declare(strict_types=1);

$map = [
    '001' => 'BoardRender',
    '002' => 'BoardListSwitch',
    '003' => 'ListDisabled',
    '004' => 'MultipleBoardsSameCardId',
    '005' => 'StageCountAndPageSize',
    '006' => 'NextStagePage',
    '007' => 'FilterAssigneePriority',
    '008' => 'SearchOutOfOrderResponses',
    '009' => 'SortGuard',
    '010' => 'TenThousandCards',
    '011' => 'ValidMove',
    '012' => 'ForbiddenMove',
    '013' => 'CancelMove',
    '014' => 'MoveFormValidation',
    '015' => 'MoveWithNote',
    '016' => 'ServerRejectRollback',
    '017' => 'SameStageReorderDisabled',
    '018' => 'AnchorReorder',
    '019' => 'IdempotentOperation',
    '020' => 'UnknownOutcome',
    '021' => 'VersionConflict',
    '022' => 'WipStrictLimit',
    '023' => 'WipLastSlotConcurrency',
    '024' => 'InlineCardCreate',
    '025' => 'EscapeCancelsCreate',
    '026' => 'CreateValidationKeepsForm',
    '027' => 'StageCreateRenameColor',
    '028' => 'StageReorder',
    '029' => 'ArchiveNonEmptyStageReject',
    '030' => 'ArchiveRestoreCard',
    '031' => 'CardDetailsCustomAction',
    '032' => 'BulkPartial',
    '033' => 'BulkAtomic',
    '034' => 'RealtimeTwoUsers',
    '035' => 'DuplicateRealtimeEvent',
    '036' => 'OutOfOrderEvent',
    '037' => 'OfflineReconnect',
    '038' => 'CrossTenantScope',
    '039' => 'XssInTitle',
    '040' => 'RtlLtrDarkLightResponsive',
    '041' => 'KeyboardOnlyMoveReorder',
    '042' => 'ReducedMotion',
    '043' => 'MountUnmountListenerLeak',
    '044' => 'MultipleBoardsRealtimeIsolation',
];

$titles = [
    '001' => 'رندر برد',
    '002' => 'تغییر دستی برد و فهرست',
    '003' => 'غیرفعال بودن فهرست',
    '004' => 'دو برد با شناسه کارت یکسان',
    '005' => 'شمارش ستون و اندازه صفحه',
    '006' => 'صفحه بعد ستون',
    '007' => 'فیلتر مسئول و اولویت',
    '008' => 'نادیده گرفتن پاسخ قدیمی جست‌وجو',
    '009' => 'قفل مرتب‌سازی دستی',
    '010' => 'ده هزار کارت',
    '011' => 'انتقال مجاز',
    '012' => 'انتقال ممنوع',
    '013' => 'لغو تأیید انتقال',
    '014' => 'اعتبارسنجی فرم انتقال',
    '015' => 'انتقال با یادداشت',
    '016' => 'برگشت بعد از رد سرور',
    '017' => 'جابه‌جایی در همان ستون با ترتیب خاموش',
    '018' => 'ترتیب با لنگر',
    '019' => 'فرمان تکراری',
    '020' => 'نتیجه نامعلوم',
    '021' => 'تعارض نسخه',
    '022' => 'سقف ظرفیت سخت',
    '023' => 'رقابت برای آخرین ظرفیت',
    '024' => 'ساخت سریع کارت',
    '025' => 'لغو ساخت با Escape',
    '026' => 'حفظ فرم نامعتبر',
    '027' => 'ساخت و تغییر ستون',
    '028' => 'ترتیب ستون‌ها',
    '029' => 'رد بایگانی ستون پر',
    '030' => 'بایگانی و بازیابی کارت',
    '031' => 'جزئیات و عمل سفارشی',
    '032' => 'عملیات گروهی جزئی',
    '033' => 'عملیات گروهی اتمی',
    '034' => 'دو کاربر و رویداد',
    '035' => 'رویداد تکراری',
    '036' => 'رویداد خارج از ترتیب',
    '037' => 'قطع و اتصال مجدد',
    '038' => 'جداسازی مستأجر',
    '039' => 'فرار از HTML عنوان',
    '040' => 'راست‌چین و پوسته',
    '041' => 'انتقال با صفحه‌کلید',
    '042' => 'کاهش حرکت',
    '043' => 'نشت شنونده',
    '044' => 'جداسازی رویداد چند برد',
];

foreach ($map as $number => $name) {
    $class = 'KB'.$number.$name.'Test';
    $method = 'kb'.$number;
    $php = <<<PHP
<?php

declare(strict_types=1);

namespace Karnoweb\\Kanban\\Tests\\Scenarios;

final class {$class} extends \\Karnoweb\\Kanban\\Tests\\TestCase
{
    use ScenarioSteps;

    public function test_scenario(): void
    {
        \$this->{$method}();
    }
}

PHP;
    file_put_contents(__DIR__.'/../tests/Scenarios/'.$class.'.php', $php);

    $md = <<<MD
# KB-{$number} — {$titles[$number]}

- دامنه: CRM / Task
- فاز پیاده‌سازی: مطابق نقشه پنج‌فازه
- پیوند تست: `tests/Scenarios/{$class}.php`
- پیش‌نیاز: Laravel 13، Livewire 4، Factory حافظه‌ای، Scope سروری
- کاربران و Tenant: User A روی Tenant A؛ سناریوهای دوکاربره User B را هم می‌سازند
- وضعیت اولیه: داده عددی داخل متد `{$method}()` در `tests/Scenarios/ScenarioSteps.php`
- مراحل اجرا: تست PHPUnit همان متد را روی کامپوننت `kanban-board` اجرا می‌کند
- نتیجه UI: مطابق assertionهای همان متد
- نتیجه Server: Handler فقط وقتی فراخوانی می‌شود که سناریو تغییر تجاری تأییدشده بخواهد
- نتیجه Event: فقط سناریوهای Realtime رویداد `RealtimeEventData` می‌سازند
- نتیجه تست واقعی: با دستور `vendor/bin/phpunit tests/Scenarios/{$class}.php` سنجیده می‌شود

MD;
    file_put_contents(__DIR__.'/../tests/Scenarios/KB'.$number.'_'.$name.'.md', $md);
}

echo count($map), PHP_EOL;

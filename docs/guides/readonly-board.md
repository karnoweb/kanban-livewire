# برد کوچک و فقط‌خواندنی

## هدف و پیش‌نیاز

یک کارت ثابت با تصویر، چک‌لیست و نظر نمایش دهیم. نیازی به مدل یا جدول جدید نیست. نصب assets و احراز هویت میزبان مطابق [نصب](../02-installation-assets.md) و کلاس `AppScopeResolver` و صفحه و route مطابق مراحل ۳ و ۶ [راهنمای اتصال](host-task-board.md) لازم است.

## فایل و ثبت

تصویر JPEG واقعی را در `public/images/kanban-preview.jpg` قرار دهید؛ پکیج تصویر یا فایل آپلود نمی‌کند. فایل `app/Kanban/TaskBoardFactory.php`:

```php
<?php
namespace App\Kanban;

use Karnoweb\Kanban\Contracts\BoardFactory;
use Karnoweb\Kanban\Data\{AttachmentData, BoardContext, BoardData, BoardDefinition,
    CardBadgeData, CardData, ChecklistItemData, CommentData, StageData};
use Karnoweb\Kanban\Support\BoardBuilder;

final class TaskBoardFactory implements BoardFactory
{
    public function make(BoardContext $context): BoardDefinition
    {
        return (new BoardBuilder)->key('tasks.board')
            ->board(new BoardData('tasks.board', 'Read-only preview', $context->scopeKey))
            ->stages([new StageData('todo', 'tasks.board', 'To do', 0),
                new StageData('done', 'tasks.board', 'Done', 1)])
            ->cards([new CardData(
                id: 'preview-1', boardId: 'tasks.board', stageId: 'todo', title: 'First card',
                description: 'This preview is rebuilt on every request.',
                badges: [new CardBadgeData('Preview')],
                attachments: [new AttachmentData('cover', 'Preview image', 'image/jpeg', null,
                    asset('images/kanban-preview.jpg'))],
                checklist: [new ChecklistItemData('check-1', 'Review', true)],
                comments: [new CommentData('comment-1', 'Ready for review', 'Demo')],
            )])
            ->build();
    }
}
```

در `AppServiceProvider::register()` همان اتصال ScopeResolver راهنمای اتصال را نگه دارید. در `boot()` فقط برد را ثبت کنید؛ ثبت مدل لازم نیست:

```php
public function boot(
    KarnowebKanbanSupportBoardRegistry $boards,
): void {
    $boards->register('tasks.board', AppKanbanTaskBoardFactory::class);
}
```

این مسیر جایگزین نمونهٔ Eloquent است؛ Factory همنام را هم‌زمان دوبار تعریف یا ثبت نکنید. در این مثال handler تعیین نشده و `UnsupportedActionHandler` انتخاب می‌شود؛ برد فقط‌خواندنی است. داده در هر درخواست ساخته می‌شود و ذخیرهٔ دائمی ندارد. برای کاربرها یا سازمان‌های مختلف دادهٔ حساس را بدون محدودسازی داخل `cards()` قرار ندهید.

## اجرا و بررسی

از ریشهٔ میزبان `php artisan serve` را اجرا کنید و پس از ورود `/tasks` را باز کنید. دو ستون و یک کارت با تصویر و برچسب Preview دیده می‌شود. جزئیات باید یک نظر و چک‌لیست تکمیل‌شده نشان دهد؛ عمل نوشتن باید در دسترس نباشد.

اگر تصویر دیده نمی‌شود، URL تصویر را مستقیم باز کنید؛ پاسخ باید ۲۰۰ و MIME واقعی تصویر باشد. اولین attachment با MIME `image/*` به‌عنوان تصویر کارت استفاده می‌شود. URL جاوااسکریپتی یا فایل بدون مسیر عمومی معتبر رد می‌شود. اگر برد ناشناخته است، key ثبت، resolver و Factory باید یکی باشند.

# همزیستی با تقویم

کانبان هیچ وابستگی Composer (مدیریت بسته‌های PHP) به پکیج تقویم ندارد. یک رکورد دامنه می‌تواند هم‌زمان دو قابلیت داشته باشد؛ عنوان، موعد و شناسه هر دو از همان ردیف خوانده می‌شوند.

```php
class Task extends \Illuminate\Database\Eloquent\Model
{
    use \Karnoweb\Kanban\Concerns\HasKanbanCards;
    use \App\Calendar\HasCalendarEvents;

    // kanbanDefinition() و تعریف تقویم میزبان، مستقل از یکدیگر.
}
```

`App\Calendar\HasCalendarEvents` در مثال بالا متعلق به میزبان است؛ پکیج تقویم خارجی در این مخزن نصب نیست و namespace خارجی تأیید نشده است. مثال اجرایی همزیستی در `EnterpriseAcceptanceTest::test_calendar_and_kanban_traits_share_one_domain_record_without_package_dependency` از Trait تقویم آزمایشی میزبان استفاده می‌کند؛ این آزمون، آزمون اتصال به محصول تقویم خارجی محسوب نمی‌شود.

در میزبان واقعی، تغییر موعد را از سرویس دامنه واحد انجام دهید و بعد از ثبت، داده هر دو نمایش را تازه کنید. برای اطلاع تغییر کانبان، `KanbanModelChanged` را گوش کنید. ایجاد جدول تقویم یا نسخه دوم از Task توسط کانبان لازم نیست.

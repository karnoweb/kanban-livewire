# نصب و دارایی

CSS و JS ازپیش‌ساخته در `resources/dist` هستند و با تگ `kanban-assets` به `public/vendor/kanban` منتشر می‌شوند. Tailwind میزبان لازم نیست. برای اسکن کلاس‌ها، در صورت تمایل `@source` را به مسیر viewهای پکیج بدهید. اسکریپت پکیج `Alpine.start` یا `Livewire.start` را صدا نمی‌زند. CSP باید `self` را برای همان مسیر vendor اجازه دهد.
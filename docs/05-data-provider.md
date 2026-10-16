# DataProvider

متدهای الزامی: `board`, `stages`, `stageCards`, `listCards`, `counts`, `card`, `archives`, `capabilities`, `operationStatus`. فیلتر و شمارش روی منبع انجام می‌شود. `PageResult` می‌تواند `totalCount = null` باشد. cursor فقط offset و hash کوئری را دارد و شناسه tenant داخل آن نیست.

توضیح: cursor بالا مخصوص `InlineDataProvider` برای آرایه کوچک است. `EloquentKanbanDataProvider` از موقعیت مقدار/شناسه و نشانگر رمزنگاری‌شده وابسته به هویت و کوئری استفاده می‌کند؛ جزئیات در راهنمای ۲۳ است.

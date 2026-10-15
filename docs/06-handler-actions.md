# ActionHandler

تنها ورودی `execute(ActionCommand, BoardContext)` است. نوع فرمان از `ActionType` است: moveCard، reorderCard، createCard، updateCard، archiveCard، restoreCard، createStage، updateStage، reorderStage، archiveStage، executeCardAction، executeBulkAction. میزبان تراکنش، WIP، نسخه و idempotency با `operationId` را خودش تضمین می‌کند. نبود فرمان باید `UNSUPPORTED` برگرداند، نه خطای ۵۰۰.
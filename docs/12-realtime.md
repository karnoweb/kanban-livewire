# Realtime

رویداد بعد از commit توسط میزبان با `BroadcastEventFactory` ساخته می‌شود. کانال `private-kanban.{scopeHash}.{board}` است. Bridge رویداد تکراری و نسخه قدیمی را دور می‌ریزد و شکاف نسخه را با reload پر می‌کند. Echo را میزبان می‌سازد.
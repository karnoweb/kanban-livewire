# Builder و Registry

`Kanban::init()` نمونه تازه می‌دهد. `pipeline()` همان `board()` است و `setting()` همان `settings()` است. `build()` کلید، برد، provider و handler را اعتبارسنجی می‌کند. Registry کلید تکراری را رد می‌کند و factory را در هر درخواست دوباره resolve می‌کند.
> به‌روزرسانی ظاهر: CSS منبع اکنون به DaisyUI 5 وابسته است. میزبان دارای DaisyUI باید آن را در CSS سراسری import کند و فایل مستقل dist را اضافه نکند. قرارداد پوسته، متغیرها و تمام انتخابگرها: [راهنمای سفارشی‌سازی](docs/11-views-customization.md).

# Upgrade

## 13.0.0 → 13.1.0

This release includes a styling integration change that requires reviewing host CSS setup. PHP requirements remain PHP 8.3+, Laravel 13 and Livewire 4; the package version does not imply Laravel 14 support.

- Hosts already using DaisyUI must use DaisyUI 5 (verified 5.7.50), Tailwind CSS 4 and the default empty prefix. Import the package source CSS into the host global CSS; do not also load the standalone dist CSS. See `docs/11-views-customization.md`.
- Hosts without DaisyUI can keep the standalone published CSS, which now includes Tailwind/DaisyUI and a global reset. Review its effect on other host elements.
- Set `appearance.theme` to `system` to inherit the host theme. This mode no longer emits `data-theme="system"`; use `data-kb-theme="system"` for that selector. Explicit light/dark override host inheritance.
- Review published Blade overrides against the updated DaisyUI classes and retain the stable `kb-*` and `data-kb-part` behavior hooks.
- Rebuild host CSS or republish standalone assets with `php artisan vendor:publish --tag=kanban-assets --force`; clear cached views if using published overrides.
- Filters/sorting and appearance controls now live in native details disclosures. UI automation must open the relevant summary first.
- Model adapters are opt-in; existing factory/provider/handler contracts remain available. No package database migration is required.

See `docs/releases/13.1.0.md` for additions, validation and known limitations. The supported PHP API is documented in `docs/20-api-reference.md`.

Do not store closures on Livewire public properties. Register a factory class. Do not read tenant identity from the browser.

For the optional model adapter, follow `docs/27-migration-and-upgrade.md`. There is no required package migration. Declare sources explicitly; a model registry entry does not grant board access. Back up host configuration before merging the new defaults, and use a persistent shared cache for operation deduplication.

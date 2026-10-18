<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Data\{DateSettings, KanbanSettings};
use Karnoweb\Kanban\Support\DateFormatter;
use Karnoweb\Kanban\Tests\TestCase;

final class DateFormatterTest extends TestCase
{
    public function test_iranian_locale_uses_jalali_and_persian_digits(): void
    {
        app()->setLocale('fa_IR');
        self::assertSame('۱۴۰۳/۰۱/۰۱', app(DateFormatter::class)->format('2024-03-20'));
        self::assertSame('۱۴۰۳/۰۱/۰۱ ۰۰:۳۰', app(DateFormatter::class)->format('2024-03-19T21:00:00Z', true));
    }

    public function test_auto_mode_recognizes_country_and_does_not_confuse_all_persian_locales(): void
    {
        $formatter = app(DateFormatter::class);
        foreach (['fa', 'fa-IR', 'en_IR', 'fa_Arab_IR'] as $locale) {
            self::assertSame('1403/01/01', $formatter->format('2024-03-20', settings: new DateSettings(locale: $locale, persianDigits: false)));
        }
        foreach (['en', 'en_US', 'fa_AF'] as $locale) {
            self::assertSame('2024-03-20', $formatter->format('2024-03-20', settings: new DateSettings(locale: $locale)));
        }
    }

    public function test_explicit_calendar_timezone_and_date_only_are_independent(): void
    {
        $formatter = app(DateFormatter::class);
        self::assertSame('2024-03-20 00:30', $formatter->format('2024-03-19T21:00:00Z', true, new DateSettings(calendar: 'gregorian', locale: 'fa_IR')));
        self::assertSame('1402/12/29 21:00', $formatter->format('2024-03-19T21:00:00Z', true, new DateSettings(calendar: 'jalali', locale: 'fa_IR', timezone: 'UTC', persianDigits: false)));
        self::assertSame('1403/01/01', $formatter->format('2024-03-20', settings: new DateSettings(calendar: 'jalali', timezone: 'America/Los_Angeles', persianDigits: false)));
    }

    public function test_board_overrides_global_configuration_and_locale_is_resolved_per_call(): void
    {
        config(['kanban.dates.calendar' => 'gregorian']);
        $settings = KanbanSettings::fromArray(['appearance' => ['dates' => ['calendar' => 'jalali', 'persian_digits' => false]]]);
        self::assertSame('1403/01/01', app(DateFormatter::class)->format('2024-03-20', settings: $settings->appearance->dates));
        config(['kanban.dates.calendar' => 'auto']);
        $formatter = app(DateFormatter::class);
        app()->setLocale('fa');
        self::assertSame('۱۴۰۳/۰۱/۰۱', $formatter->format('2024-03-20'));
        app()->setLocale('en');
        self::assertSame('2024-03-20', $formatter->format('2024-03-20'));
    }

    public function test_other_appearance_overrides_preserve_inherited_date_settings(): void
    {
        config(['kanban.appearance.dates.locale' => 'fa_IR']);
        $settings = KanbanSettings::fromArray(['appearance' => ['theme' => 'dark']]);
        self::assertSame('fa_IR', $settings->appearance->dates->locale);
        self::assertSame('۱۴۰۳/۰۱/۰۱', app(DateFormatter::class)->format('2024-03-20', settings: $settings->appearance->dates));
    }

    public function test_invalid_or_empty_values_do_not_break_rendering(): void
    {
        self::assertSame('', app(DateFormatter::class)->format(null));
        self::assertSame('not-a-date', app(DateFormatter::class)->format('not-a-date'));
    }

    public function test_invalid_configuration_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DateSettings(timezone: 'Not/AZone');
    }
}

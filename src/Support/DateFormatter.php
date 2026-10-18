<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Karnoweb\Kanban\Data\DateSettings;
use Morilog\Jalali\CalendarUtils;
use Morilog\Jalali\Jalalian;

final class DateFormatter
{
    public function format(?string $value, bool $withTime = false, ?DateSettings $settings = null): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }
        $settings ??= DateSettings::fromArray();
        $locale = $settings->locale ?? app()->getLocale();
        $parts = explode('_', strtolower(str_replace('-', '_', $locale)));
        $iran = $parts === ['fa'] || in_array('ir', array_slice($parts, 1), true);
        $jalali = $settings->calendar === 'jalali' || ($settings->calendar === 'auto' && $iran);
        $timezone = $settings->timezone ?? ($iran ? 'Asia/Tehran' : config('app.timezone', 'UTC'));

        try {
            // Calendar-only due dates have no instant to shift between timezones.
            $dateOnly = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) === 1;
            $date = new Carbon($value, $dateOnly ? $timezone : config('app.timezone', 'UTC'));
            if (! $dateOnly) {
                $date->setTimezone($timezone);
            }
        } catch (InvalidFormatException) {
            return $value;
        }

        if ($jalali) {
            $formatted = Jalalian::fromCarbon($date)->format($withTime ? $settings->jalaliDateTimeFormat : $settings->jalaliDateFormat);

            return $settings->persianDigits ? CalendarUtils::convertNumbers($formatted) : $formatted;
        }

        return $date->locale($locale)->translatedFormat($withTime ? $settings->dateTimeFormat : $settings->dateFormat);
    }
}

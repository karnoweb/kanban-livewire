<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use DateTimeZone;
use InvalidArgumentException;

final readonly class DateSettings
{
    public function __construct(
        public string $calendar = 'auto',
        public ?string $locale = null,
        public ?string $timezone = null,
        public bool $persianDigits = true,
        public string $dateFormat = 'Y-m-d',
        public string $dateTimeFormat = 'Y-m-d H:i',
        public string $jalaliDateFormat = 'Y/m/d',
        public string $jalaliDateTimeFormat = 'Y/m/d H:i',
    ) {
        if (! in_array($calendar, ['auto', 'jalali', 'gregorian'], true)) {
            throw new InvalidArgumentException('Calendar must be auto, jalali, or gregorian.');
        }
        if ($timezone !== null) {
            try { new DateTimeZone($timezone); }
            catch (\Exception $error) { throw new InvalidArgumentException('Invalid display timezone.', previous: $error); }
        }
    }

    public static function fromArray(array $values = []): self
    {
        $values = array_replace(config('kanban.dates', []), $values);

        return new self(
            calendar: (string) ($values['calendar'] ?? 'auto'),
            locale: isset($values['locale']) && trim((string) $values['locale']) !== '' ? (string) $values['locale'] : null,
            timezone: isset($values['timezone']) && trim((string) $values['timezone']) !== '' ? (string) $values['timezone'] : null,
            persianDigits: (bool) ($values['persian_digits'] ?? true),
            dateFormat: (string) ($values['date_format'] ?? 'Y-m-d'),
            dateTimeFormat: (string) ($values['datetime_format'] ?? 'Y-m-d H:i'),
            jalaliDateFormat: (string) ($values['jalali_date_format'] ?? 'Y/m/d'),
            jalaliDateTimeFormat: (string) ($values['jalali_datetime_format'] ?? 'Y/m/d H:i'),
        );
    }

    public function toArray(): array
    {
        return [
            'calendar' => $this->calendar,
            'locale' => $this->locale,
            'timezone' => $this->timezone,
            'persian_digits' => $this->persianDigits,
            'date_format' => $this->dateFormat,
            'datetime_format' => $this->dateTimeFormat,
            'jalali_date_format' => $this->jalaliDateFormat,
            'jalali_datetime_format' => $this->jalaliDateTimeFormat,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;

final readonly class AppearanceSettings
{
    public function __construct(
        public string $theme = 'system',
        public string $direction = 'ltr',
    ) {
        if (! in_array($theme, ['light', 'dark', 'system'], true)) {
            throw new InvalidArgumentException('Theme must be light, dark, or system.');
        }

        if (! in_array($direction, ['ltr', 'rtl'], true)) {
            throw new InvalidArgumentException('Direction must be ltr or rtl.');
        }
    }
}

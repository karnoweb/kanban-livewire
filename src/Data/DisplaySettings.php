<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;
use Karnoweb\Kanban\Enums\ViewMode;

final readonly class DisplaySettings
{
    /** @param list<string> $allowedViews */
    public function __construct(
        public ViewMode $defaultView = ViewMode::Board,
        public array $allowedViews = ['board', 'list'],
        public string $columnWidth = '18rem',
        public string $density = 'comfortable',
        public string $detailSurface = 'modal',
    ) {
        if ($allowedViews === []) {
            throw new InvalidArgumentException('At least one view must stay enabled.');
        }

        foreach ($allowedViews as $view) {
            ViewMode::fromSetting($view);
        }

        if (! in_array($defaultView->value, $allowedViews, true)) {
            throw new InvalidArgumentException('The default view is not enabled.');
        }

        if (! in_array($detailSurface, ['modal', 'drawer'], true)) {
            throw new InvalidArgumentException('Detail surface must be modal or drawer.');
        }
    }

    public function allows(ViewMode $view): bool
    {
        return in_array($view->value, $this->allowedViews, true);
    }
}

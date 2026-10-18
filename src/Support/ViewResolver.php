<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Karnoweb\Kanban\Exceptions\InvalidBoardDefinition;

final class ViewResolver
{
    public function assertAllowed(string $slot, string $view): void
    {
        if (! in_array($slot, ['card', 'stage', 'list', 'detail-section'], true)) {
            throw new InvalidBoardDefinition('Unknown view slot.');
        }

        if (preg_match('/^[A-Za-z0-9_.:-]+$/', $view) !== 1 || str_contains($view, '..')) {
            throw new InvalidBoardDefinition('View path is not allowlisted.');
        }
    }

    public function name(string $slot, ?string $override, string $fallback): string
    {
        if ($override === null || $override === '') {
            return $fallback;
        }

        $this->assertAllowed($slot, $override);

        return $override;
    }
}

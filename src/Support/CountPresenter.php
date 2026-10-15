<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

final class CountPresenter
{
    public function label(?int $total, bool $exact): string
    {
        if ($total === null) {
            return __('kanban::kanban.count_unknown');
        }

        if (! $exact) {
            return __('kanban::kanban.count_approx', ['count' => $total]);
        }

        return (string) $total;
    }

    public function loaded(int $loaded, ?int $total, bool $exact): string
    {
        $totalLabel = $this->label($total, $exact);

        return __('kanban::kanban.count_loaded', ['loaded' => $loaded, 'total' => $totalLabel]);
    }
}

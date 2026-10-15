<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Actions;

use Karnoweb\Kanban\Enums\WipMode;

final class WipPresenter
{
    /** @return array{limit: ?int, count: ?int, mode: string, exceeded: bool, label: string} */
    public function present(?int $limit, ?int $count, WipMode $mode, bool $exact): array
    {
        $exceeded = $exact && $limit !== null && $count !== null && $count >= $limit;

        return [
            'limit' => $limit,
            'count' => $count,
            'mode' => $mode->value,
            'exceeded' => $exceeded,
            'label' => $limit === null ? '' : (($count ?? '—').'/'.$limit),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

final class RefreshPlanner
{
    /** @param list<string> $hints
     * @return array{board: bool, stages: list<string>, cards: list<string>, counts: bool, list: bool}
     */
    public function plan(array $hints): array
    {
        $plan = [
            'board' => in_array('board', $hints, true),
            'stages' => [],
            'cards' => [],
            'counts' => in_array('counts', $hints, true) || in_array('board', $hints, true),
            'list' => in_array('list', $hints, true),
        ];

        foreach ($hints as $hint) {
            if (str_starts_with($hint, 'stage:')) {
                $plan['stages'][] = substr($hint, 6);
            }

            if (str_starts_with($hint, 'card:')) {
                $plan['cards'][] = substr($hint, 5);
            }
        }

        return $plan;
    }
}

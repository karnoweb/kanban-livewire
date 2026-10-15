<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Fixtures;

final class SampleDataFactory
{
    /** @return array<string, mixed> */
    public static function standard(int $perStage = 8): array
    {
        $stages = [
            ['id' => 'new', 'title' => 'New', 'order' => 0],
            ['id' => 'progress', 'title' => 'Progress', 'order' => 1],
            ['id' => 'review', 'title' => 'Review', 'order' => 2],
            ['id' => 'done', 'title' => 'Done', 'order' => 3],
        ];
        $cards = [];

        foreach ($stages as $stage) {
            for ($i = 1; $i <= $perStage; $i++) {
                $id = $stage['id'].'-'.$i;
                $cards[] = [
                    'id' => $id,
                    'stageId' => $stage['id'],
                    'title' => strtoupper($stage['id']).' '.$i,
                    'priority' => $i % 2 === 0 ? 'high' : 'low',
                    'assignees' => [['id' => $i % 2 === 0 ? 'alice' : 'bob', 'name' => $i % 2 === 0 ? 'Alice' : 'Bob']],
                    'badges' => [['text' => $i % 2 === 0 ? 'hot' : 'cold', 'tone' => 'info']],
                    'manualOrder' => sprintf('%03d', $i),
                    'dueAt' => sprintf('2026-02-%02d', ($i % 28) + 1),
                ];
            }
        }

        return [
            'title' => 'Sample',
            'stages' => $stages,
            'cards' => $cards,
            'settings' => [
                'interaction' => [
                    'transition_policy' => 'allow',
                    'confirmation' => 'none',
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function large(int $stages = 10, int $cardsPerStage = 1000): array
    {
        $stageRows = [];
        $cards = [];

        for ($s = 1; $s <= $stages; $s++) {
            $stageId = 's'.$s;
            $stageRows[] = ['id' => $stageId, 'title' => 'Stage '.$s, 'order' => $s];

            for ($c = 1; $c <= $cardsPerStage; $c++) {
                $cards[] = [
                    'id' => $stageId.'-'.$c,
                    'stageId' => $stageId,
                    'title' => 'Card '.$stageId.' '.$c,
                    'manualOrder' => sprintf('%05d', $c),
                ];
            }
        }

        return [
            'title' => 'Large',
            'stages' => $stageRows,
            'cards' => $cards,
            'settings' => ['interaction' => ['transition_policy' => 'allow']],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\DemoCard;

/** Enrich original demo fixtures only; preserve existing content and user-created cards. */
final class CardPresentationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoCard::query()->whereIn('public_id', ['101', '102', '103'])->cursor() as $card) {
            $meta = $card->metadata ?? [];
            if (! isset($meta['code'])) {
                continue;
            }
            if (! array_key_exists('comments', $meta)) {
                $meta['comments'] = [
                    ['id' => 'demo-review', 'author' => 'Alice Karimi', 'body' => 'Sample comment: requirements reviewed; ready for the next step.', 'createdAt' => '2026-10-18T09:00:00Z'],
                    ['id' => 'demo-files', 'author' => 'Bob Ahmadi', 'body' => 'Sample comment: please review the attached files before approval.', 'createdAt' => '2026-10-18T09:30:00Z'],
                ];
                $meta['commentsCount'] = count($meta['comments']);
            }
            if (! array_key_exists('checklist', $meta)) {
                $total = (int) ($meta['checklistTotal'] ?? 0);
                $done = (int) ($meta['checklistDone'] ?? 0);
                $meta['checklist'] = [];
                foreach (array_slice(['Review requirements', 'Verify permissions', 'Review attachments', 'Check acceptance criteria', 'Run validation', 'Final approval'], 0, $total) as $index => $title) {
                    $meta['checklist'][] = ['id' => 'demo-step-'.$index, 'title' => $title, 'done' => $index < $done];
                }
            }
            if ($card->public_id === '101' && ! collect($meta['attachments'] ?? [])->contains(fn ($file) => str_starts_with($file['mime'] ?? '', 'image/'))) {
                $meta['attachments'][] = ['id' => 'demo-preview', 'name' => 'board-design-preview.svg', 'mime' => 'image/svg+xml', 'url' => '/workbench/board-preview.svg'];
            }
            $card->metadata = $meta;
            if ($card->isDirty('metadata')) {
                $card->save();
            }
        }
    }
}

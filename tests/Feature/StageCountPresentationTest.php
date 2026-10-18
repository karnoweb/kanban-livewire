<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Tests\TestCase;

final class StageCountPresentationTest extends TestCase
{
    public function test_regular_stage_shows_only_the_total_card_count(): void
    {
        $html = view('kanban::components.count-badge', ['column' => [
            'totalCount' => 2, 'loadedCount' => 2, 'countExact' => true, 'renderedCount' => 2, 'wipLimit' => null,
        ]])->render();

        self::assertMatchesRegularExpression('/>\s*2\s*<span class="kb-rendered"/', $html);
        self::assertStringNotContainsString('2/2', $html);
        self::assertStringNotContainsString('kb-count-progress', $html);
    }

    public function test_limited_stage_shows_one_ratio_and_capacity_progress(): void
    {
        $html = view('kanban::components.count-badge', ['column' => [
            'totalCount' => 2, 'loadedCount' => 2, 'countExact' => true, 'renderedCount' => 2, 'wipLimit' => 3,
        ]])->render();

        self::assertStringContainsString('2/3', $html);
        self::assertStringContainsString('kb-count-progress', $html);
        self::assertStringContainsString('--kb-wip-pct: 67%', $html);
        self::assertStringNotContainsString('kb-stage-summary', $html);
    }
}

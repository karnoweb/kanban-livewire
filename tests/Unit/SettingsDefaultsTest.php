<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Unit;

use InvalidArgumentException;
use Karnoweb\Kanban\Data\KanbanSettings;
use Karnoweb\Kanban\Tests\TestCase;

final class SettingsDefaultsTest extends TestCase
{
    public function test_defaults_are_secure(): void
    {
        $settings = KanbanSettings::defaults();

        $this->assertSame(25, $settings->pagination->pageSize);
        $this->assertSame('deny', $settings->interaction->transitionPolicy);
        $this->assertFalse($settings->realtime->enabled);
        $this->assertContains('board', $settings->views->allowedViews);
        $this->assertContains('list', $settings->views->allowedViews);
    }

    public function test_disabling_both_views_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        KanbanSettings::fromArray(['views' => ['allowed' => []]]);
    }

    public function test_page_size_cannot_exceed_the_cap(): void
    {
        $this->expectException(InvalidArgumentException::class);
        KanbanSettings::fromArray(['pagination' => ['page_size' => 500, 'max_page_size' => 100]]);
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Unit;

use InvalidArgumentException;
use Karnoweb\Kanban\Data\AttachmentData;
use Karnoweb\Kanban\Data\CardData;
use Karnoweb\Kanban\Data\PageResult;
use Karnoweb\Kanban\Data\StageData;
use Karnoweb\Kanban\Support\CursorGuard;
use Karnoweb\Kanban\Support\UrlSanitizer;
use Karnoweb\Kanban\Tests\TestCase;

final class DtoValidationTest extends TestCase
{
    public function test_identity_cannot_be_blank(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StageData(' ', 'board', 'Title', 1);
    }

    public function test_loaded_count_cannot_exceed_exact_total(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StageData('s', 'b', 'Title', 1, totalCount: 1, loadedCount: 2);
    }

    public function test_javascript_urls_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AttachmentData('a', 'file', 'text/plain', 10, 'javascript:alert(1)');
    }

    public function test_https_urls_pass(): void
    {
        $url = (new UrlSanitizer)->sanitize('https://cdn.example/file.png');
        $this->assertSame('https://cdn.example/file.png', $url);
    }

    public function test_metadata_cap(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CardData('c', 'b', 's', 'Title', metadata: ['blob' => str_repeat('x', 9000)]);
    }

    public function test_cursor_hides_tenant_identifiers(): void
    {
        $guard = new CursorGuard;
        $cursor = $guard->encode(25, 'hash');
        $this->assertStringNotContainsString('tenant', $cursor);
        $this->assertSame(25, $guard->offset($cursor, 'hash'));
    }

    public function test_page_requires_cursor_when_more_exist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PageResult([], null, true, 10);
    }
}

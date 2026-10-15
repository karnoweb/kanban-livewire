<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Unit;

use Karnoweb\Kanban\Realtime\ChannelNameFactory;
use PHPUnit\Framework\TestCase;

final class ChannelNameFactoryTest extends TestCase
{
    public function test_keys_that_used_to_collide_get_distinct_reversible_names(): void
    {
        $f = new ChannelNameFactory;
        $keys = ['a.b', 'a-b', 'a_b', 'a/b', 'A.B'];
        $names = array_map(static fn (string $k): string => $f->name(str_repeat('a', 64), $k), $keys);

        $this->assertCount(count($keys), array_unique($names));

        foreach ($keys as $k) {
            $this->assertSame($k, $f->decode($f->encode($k)));
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $f->encode($k));
        }
    }

    public function test_garbage_does_not_decode(): void
    {
        $f = new ChannelNameFactory;
        $this->assertNull($f->decode(''));
        $this->assertNull($f->decode('!!'));
        $this->assertNull($f->decode('tasks-board'));
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Unit;

use Karnoweb\Kanban\Models\CardIdentity;
use Karnoweb\Kanban\Tests\TestCase;

final class CardIdentityTest extends TestCase
{
    public function test_domain_keys_round_trip_without_confusing_source_or_bulk_delimiters(): void
    {
        foreach (['17', 'a:b', 'a,b', 'شناسه', 'with space', 'a%2Cb'] as $record) {
            $identity = new CardIdentity('tasks', $record);
            self::assertSame($record, CardIdentity::parse($identity->encode())->recordId);
            self::assertStringNotContainsString(',', $identity->encode());
            self::assertSame(1, substr_count($identity->encode(), ':'));
        }
    }

    public function test_noncanonical_and_injected_identifiers_are_rejected(): void
    {
        foreach (['17', 'Tasks:1', '../tasks:1', 'tasks:', 'tasks:1:2', 'tasks:%31', 'tasks:a%2cb', 'tasks:%00', 'tasks:with space'] as $id) {
            try { CardIdentity::parse($id); self::fail('Invalid card identity accepted: '.$id); }
            catch (\InvalidArgumentException) { self::assertTrue(true); }
        }
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use InvalidArgumentException;
use Karnoweb\Kanban\Data\{CardData, ChecklistItemData, CommentData};
use Karnoweb\Kanban\Tests\TestCase;

final class CardDetailContractTest extends TestCase
{
    public function test_optional_detail_records_serialize_without_nested_metadata(): void
    {
        $card = new CardData('1', 'tasks', 'todo', 'Card',
            checklist: [new ChecklistItemData('step-1', 'Review', true)],
            comments: [new CommentData('comment-1', 'Ready', 'Alice', '2026-10-18')]);
        $data = $card->toArray();
        self::assertSame([], $data['metadata']);
        self::assertSame('Review', $data['checklist'][0]['title']);
        self::assertTrue($data['checklist'][0]['done']);
        self::assertSame('Alice', $data['comments'][0]['author']);
        self::assertSame([], (new CardData('2', 'tasks', 'todo', 'Minimal'))->toArray()['comments']);
    }

    public function test_detail_arrays_reject_unvalidated_records(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CardData('1', 'tasks', 'todo', 'Card', comments: [['body' => 'untyped']]);
    }

    public function test_detail_payloads_are_bounded(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CardData('1', 'tasks', 'todo', 'Card', comments: array_map(
            fn ($id) => new CommentData((string) $id, str_repeat('a', 5000)), range(1, 8),
        ));
    }
}

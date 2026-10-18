<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Fixtures;

use Karnoweb\Kanban\Contracts\CardDetailsProvider;
use Karnoweb\Kanban\Data\{ActionResult, BoardContext, CardData, CardDetailAction, CardDetailSectionData};

final class TestCardDetailsProvider implements CardDetailsProvider
{
    public static int $loads = 0;
    public static int $actions = 0;
    public static bool $canView = true;
    public static bool $unknown = false;
    public static array $operationIds = [];
    public static array $keys = ['comments'];
    public static array $deniedKeys = [];

    public static function reset(): void
    {
        self::$loads = 0;
        self::$actions = 0;
        self::$canView = true;
        self::$unknown = false;
        self::$operationIds = [];
        self::$keys = ['comments'];
        self::$deniedKeys = [];
    }

    public function sections(BoardContext $context, CardData $card): array
    {
        return array_map(fn ($key) => new CardDetailSectionData(
            key: $key, title: ucfirst($key), order: 20, state: 'unloaded',
            capabilities: ['canView' => self::$canView && ! in_array($key, self::$deniedKeys, true)], actions: ['refresh' => 'Refresh comments'],
        ), self::$keys);
    }

    public function loadSection(BoardContext $context, CardData $card, string $key, ?string $cursor, int $limit): CardDetailSectionData
    {
        self::$loads++;

        return new CardDetailSectionData(
            key: $key, title: 'Comments', order: 20, state: 'ready',
            items: [['label' => $cursor === null ? 'Initial comment' : 'Next comment', 'value' => '<script>escaped</script>']],
            nextCursor: $cursor === null ? 'next-page' : null,
            capabilities: ['canView' => self::$canView], actions: ['refresh' => 'Refresh comments'],
        );
    }

    public function performAction(BoardContext $context, CardData $card, CardDetailAction $action): ActionResult
    {
        self::$actions++;
        self::$operationIds[] = $action->operationId;

        if (self::$unknown) {
            return ActionResult::unknown($action->operationId);
        }

        return ActionResult::success(['comment:1']);
    }
}

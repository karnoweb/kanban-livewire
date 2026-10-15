<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Fixtures;

use InvalidArgumentException;
use Karnoweb\Kanban\Data\AssigneeData;
use Karnoweb\Kanban\Data\CardData;
use Karnoweb\Kanban\Enums\WipMode;

final class MemoryStore
{
    /** @var array<string, array<string, mixed>> */
    public static array $boards = [];

    /** @var list<array<string, mixed>> */
    public static array $calls = [];

    /** @var array<string, array<string, mixed>> */
    public static array $operations = [];

    public static int $constructedCards = 0;

    public static bool $returnUnknown = false;

    public static function reset(): void
    {
        self::$boards = [];
        self::$calls = [];
        self::$operations = [];
        self::$constructedCards = 0;
        self::$returnUnknown = false;
    }

    /** @param array<string, mixed> $config */
    public static function seed(string $scope, string $boardKey, array $config): void
    {
        $stages = [];

        foreach ($config['stages'] as $index => $stage) {
            $stages[$stage['id']] = [
                'id' => $stage['id'],
                'title' => $stage['title'],
                'order' => $stage['order'] ?? $index,
                'color' => $stage['color'] ?? '#336699',
                'visible' => $stage['visible'] ?? true,
                'readOnly' => $stage['readOnly'] ?? false,
                'disabled' => $stage['disabled'] ?? false,
                'wipLimit' => $stage['wipLimit'] ?? null,
                'wipMode' => $stage['wipMode'] ?? 'off',
                'archived' => false,
            ];
        }

        $cards = [];

        foreach ($config['cards'] ?? [] as $card) {
            $cards[$card['id']] = array_replace([
                'priority' => null,
                'description' => null,
                'assignees' => [],
                'badges' => [],
                'attachments' => [],
                'actions' => [],
                'metadata' => [],
                'dueAt' => null,
                'createdAt' => '2026-01-01T00:00:00Z',
                'version' => '1',
                'manualOrder' => $card['id'],
                'archived' => false,
                'permissions' => [],
            ], $card);
        }

        self::$boards[self::key($scope, $boardKey)] = [
            'title' => $config['title'] ?? $boardKey,
            'version' => '1',
            'stages' => $stages,
            'cards' => $cards,
            'settings' => $config['settings'] ?? ['interaction' => ['transition_policy' => 'allow']],
            'allowArchiveNonEmpty' => $config['allowArchiveNonEmpty'] ?? false,
            'denyActors' => $config['denyActors'] ?? [],
            'bulkDeny' => $config['bulkDeny'] ?? [],
        ];
    }

    /** @return array<string, mixed> */
    public static function board(string $scope, string $boardKey): array
    {
        return self::$boards[self::key($scope, $boardKey)]
            ?? throw new InvalidArgumentException('Board fixture is missing.');
    }

    public static function put(string $scope, string $boardKey, array $board): void
    {
        self::$boards[self::key($scope, $boardKey)] = $board;
    }

    public static function trackCard(): void
    {
        self::$constructedCards++;
    }

    public static function card(array $row, string $boardId): CardData
    {
        self::trackCard();
        $assignees = [];

        foreach ($row['assignees'] as $assignee) {
            $assignees[] = new AssigneeData($assignee['id'], $assignee['name'], $assignee['avatarUrl'] ?? null);
        }

        $badges = [];

        foreach ($row['badges'] as $badge) {
            $badges[] = new \Karnoweb\Kanban\Data\CardBadgeData(
                $badge['text'],
                \Karnoweb\Kanban\Enums\BadgeTone::from($badge['tone'] ?? 'neutral'),
            );
        }

        $actions = [];

        foreach ($row['actions'] as $action) {
            $actions[] = new \Karnoweb\Kanban\Data\CardActionData($action['id'], $action['label'], $action['enabled'] ?? true);
        }

        return new CardData(
            id: $row['id'],
            boardId: $boardId,
            stageId: $row['stageId'],
            title: $row['title'],
            description: $row['description'],
            priority: $row['priority'],
            badges: $badges,
            assignees: $assignees,
            createdAt: $row['createdAt'],
            dueAt: $row['dueAt'],
            metadata: $row['metadata'],
            actions: $actions,
            version: (string) $row['version'],
            manualOrder: (string) $row['manualOrder'],
            archived: (bool) $row['archived'],
        );
    }

    /** @return list<array<string, mixed>> */
    public static function activeCards(array $board, ?string $stageId = null): array
    {
        $cards = array_values(array_filter(
            $board['cards'],
            static fn (array $card): bool => ! $card['archived'] && ($stageId === null || $card['stageId'] === $stageId),
        ));

        return $cards;
    }

    public static function wipMode(array $stage): WipMode
    {
        return WipMode::from($stage['wipMode'] ?? 'off');
    }

    private static function key(string $scope, string $boardKey): string
    {
        return $scope.'|'.$boardKey;
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Enums;

enum ActionType: string
{
    case MoveCard = 'moveCard';
    case ReorderCard = 'reorderCard';
    case CreateCard = 'createCard';
    case UpdateCard = 'updateCard';
    case ArchiveCard = 'archiveCard';
    case DeleteCard = 'deleteCard';
    case RestoreCard = 'restoreCard';
    case CreateStage = 'createStage';
    case UpdateStage = 'updateStage';
    case ReorderStage = 'reorderStage';
    case ArchiveStage = 'archiveStage';
    case ExecuteCardAction = 'executeCardAction';
    case ExecuteBulkAction = 'executeBulkAction';

    /** @return list<string> */
    public static function allowlist(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}

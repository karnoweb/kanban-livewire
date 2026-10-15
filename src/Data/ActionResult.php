<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use Karnoweb\Kanban\Enums\ActionStatus;

final readonly class ActionResult
{
    /**
     * @param array<string, string> $fieldErrors
     * @param list<string> $affectedIds
     * @param list<string> $refreshHints
     * @param list<BulkItemResult> $itemResults
     */
    public function __construct(
        public ActionStatus $status,
        public ?string $messageCode = null,
        public array $fieldErrors = [],
        public array $affectedIds = [],
        public array $refreshHints = [],
        public ?string $newVersion = null,
        public ?string $safeMessage = null,
        public ?CardData $updatedCard = null,
        public ?StageData $updatedStage = null,
        public array $itemResults = [],
        public int $successCount = 0,
        public int $failureCount = 0,
        public bool $noop = false,
    ) {}

    /** @param list<string> $affectedIds
     * @param list<string> $refreshHints
     */
    public static function success(array $affectedIds = [], array $refreshHints = [], ?string $newVersion = null): self
    {
        return new self(ActionStatus::SUCCESS, affectedIds: $affectedIds, refreshHints: $refreshHints, newVersion: $newVersion, successCount: count($affectedIds));
    }

    public static function unsupported(): self
    {
        return new self(ActionStatus::UNSUPPORTED, messageCode: 'unsupported');
    }

    public static function unknown(string $operationId): self
    {
        return new self(ActionStatus::UNKNOWN_OUTCOME, messageCode: 'unknown_outcome', affectedIds: [$operationId]);
    }
}

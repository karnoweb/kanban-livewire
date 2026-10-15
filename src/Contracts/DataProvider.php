<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Contracts;

use Karnoweb\Kanban\Data\ActionResult;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardData;
use Karnoweb\Kanban\Data\BoardQuery;
use Karnoweb\Kanban\Data\CardData;
use Karnoweb\Kanban\Data\CountResult;
use Karnoweb\Kanban\Data\PageResult;

interface DataProvider
{
    public function board(BoardContext $context): BoardData;

    /** @return list<\Karnoweb\Kanban\Data\StageData> */
    public function stages(BoardContext $context, BoardQuery $query): array;

    public function stageCards(BoardContext $context, string $stageId, BoardQuery $query): PageResult;

    public function listCards(BoardContext $context, BoardQuery $query): PageResult;

    public function counts(BoardContext $context, BoardQuery $query): CountResult;

    public function card(BoardContext $context, string $cardId): ?CardData;

    public function archives(BoardContext $context, BoardQuery $query): PageResult;

    /** @return array<string, bool> */
    public function capabilities(BoardContext $context): array;

    public function operationStatus(BoardContext $context, string $operationId): ?ActionResult;
}

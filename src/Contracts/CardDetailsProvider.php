<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Contracts;

use Karnoweb\Kanban\Data\ActionResult;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\CardData;
use Karnoweb\Kanban\Data\CardDetailAction;
use Karnoweb\Kanban\Data\CardDetailSectionData;

/** Optional, request-scoped extension for card detail sections. */
interface CardDetailsProvider
{
    /** @return list<CardDetailSectionData> Lightweight descriptors only; defer section records until loadSection(). */
    public function sections(BoardContext $context, CardData $card): array;

    /** Load one authorized section page. Providers must recheck actor and record scope on every call. */
    public function loadSection(BoardContext $context, CardData $card, string $key, ?string $cursor, int $limit): CardDetailSectionData;

    /** Execute a domain-owned section action with idempotency and authorization enforced by the provider. */
    public function performAction(BoardContext $context, CardData $card, CardDetailAction $action): ActionResult;
}

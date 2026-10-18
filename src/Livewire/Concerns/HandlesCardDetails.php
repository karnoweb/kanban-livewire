<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Livewire\Concerns;

use InvalidArgumentException;
use Karnoweb\Kanban\Contracts\CardDetailsProvider;
use Karnoweb\Kanban\Data\ActionResult;
use Karnoweb\Kanban\Data\CardData;
use Karnoweb\Kanban\Data\CardDetailAction;
use Karnoweb\Kanban\Data\CardDetailSectionData;
use Livewire\Attributes\Locked;

trait HandlesCardDetails
{
    public function openDetails(string $cardId, ?string $surface = null): void
    {
        if (! $this->definition()->settings->features->details) {
            $this->closeDetails();

            return;
        }
        $card = $this->provider()->card($this->context(), $cardId);

        if ($card === null) {
            $this->closeDetails();
            $this->notice = ['status' => 'not_found', 'messageCode' => 'not_found', 'fieldErrors' => [], 'safeMessage' => null, 'successCount' => 0, 'failureCount' => 0, 'noop' => false, 'items' => []];

            return;
        }

        $this->detail = $card->toArray();
        $this->detailCardId = $card->id;
        $this->detailSectionPages = [];
        $this->detailSectionOperations = [];
        $this->detailSurface = in_array($surface, ['modal', 'drawer'], true) ? $surface : $this->detailSurface;
    }

    public function closeDetails(): void
    {
        $this->detail = null;
        $this->detailCardId = null;
        $this->detailSectionPages = [];
        $this->detailSectionOperations = [];
    }

    /** @var array<string, array<string, mixed>> */
    #[Locked]
    public array $detailSectionPages = [];

    /** Stable command identity/version for retries of the currently displayed section. */
    #[Locked]
    public array $detailSectionOperations = [];

    public function loadDetailSection(string $key, ?string $cursor = null): void
    {
        [$provider, $card, $descriptor] = $this->authorizedDetailSection($key);
        if ($provider === null || $card === null || $descriptor === null) {
            return;
        }
        if ($cursor !== null && ($this->detailSectionPages[$key]['nextCursor'] ?? null) !== $cursor) {
            return;
        }

        $pageSize = max(1, min(100, (int) config('kanban.limits.detail_section_page_size', 25)));
        $page = $provider->loadSection($this->context(), $card, $key, $cursor, $pageSize);
        if (! $page instanceof CardDetailSectionData || $page->key !== $key || ! $page->canView() || $page->state === 'unloaded') {
            unset($this->detailSectionPages[$key], $this->detailSectionOperations[$key]);

            return;
        }

        // Keep one bounded page; advancing must never silently drop later records.
        $items = $page->items;
        $this->detailSectionPages[$key] = (new CardDetailSectionData(
            key: $page->key,
            title: $page->title,
            order: $descriptor->order,
            state: $page->state,
            summary: $page->summary ?? $descriptor->summary,
            items: $items,
            nextCursor: $page->nextCursor,
            capabilities: $page->capabilities,
            actions: $descriptor->actions,
        ))->toArray();
        $this->detailSectionOperations[$key] = ['version' => $card->version, 'actions' => []];
        foreach ($descriptor->actions as $action => $label) {
            $this->detailSectionOperations[$key]['actions'][$action] = app(\Karnoweb\Kanban\Support\OperationIdFactory::class)->make();
        }
    }

    /** @param array<string, mixed> $payload */
    public function performDetailAction(string $key, string $action, array $payload = []): void
    {
        if (! $this->definition()->settings->features->customActions) {
            return;
        }
        [$provider, $card, $descriptor] = $this->authorizedDetailSection($key);
        $operationId = $this->detailSectionOperations[$key]['actions'][$action] ?? null;
        if ($provider === null || $card === null || $descriptor === null || ! array_key_exists($action, $descriptor->actions) || ! is_string($operationId)) {
            return;
        }

        $command = new CardDetailAction(
            $key,
            $action,
            $payload,
            $operationId,
            $this->detailSectionOperations[$key]['version'] ?? null,
        );
        try {
            $result = $provider->performAction($this->context(), $card, $command);
        } catch (\Throwable $exception) {
            report($exception);
            $result = ActionResult::unknown($command->operationId);
        }
        if (! $result instanceof ActionResult) {
            throw new InvalidArgumentException('Card detail actions must return ActionResult.');
        }
        $this->notice = app(\Karnoweb\Kanban\Actions\ResultMapper::class)->notice($result);
        if ($result->status === \Karnoweb\Kanban\Enums\ActionStatus::UNKNOWN_OUTCOME) {
            $this->unknownOperationId = $command->operationId;
        }
        if ($result->status === \Karnoweb\Kanban\Enums\ActionStatus::SUCCESS) {
            unset($this->detailSectionPages[$key]);
            $this->loadDetailSection($key);
        }
    }

    /** @return array{?CardDetailsProvider, ?CardData, ?CardDetailSectionData} */
    private function authorizedDetailSection(string $key): array
    {
        if (! $this->definition()->settings->features->details || $this->detailCardId === null || $this->detail === null) {
            return [null, null, null];
        }
        $class = $this->definition()->detailsProviderClass;
        if ($class === null) {
            return [null, null, null];
        }
        $provider = app($class);
        if (! $provider instanceof CardDetailsProvider) {
            return [null, null, null];
        }
        $card = $this->provider()->card($this->context(), $this->detailCardId);
        if ($card === null) {
            $this->closeDetails();

            return [null, null, null];
        }
        $this->detail = $card->toArray();
        foreach ($this->sectionDescriptors($provider, $card) as $descriptor) {
            if ($descriptor->key === $key && $descriptor->canView()) {
                return [$provider, $card, $descriptor];
            }
        }

        unset($this->detailSectionPages[$key], $this->detailSectionOperations[$key]);

        return [null, $card, null];
    }

    /** @return list<CardDetailSectionData> */
    private function sectionDescriptors(CardDetailsProvider $provider, CardData $card): array
    {
        $sections = $provider->sections($this->context(), $card);
        $limit = max(1, min(32, (int) config('kanban.limits.detail_sections', 16)));
        if (count($sections) > $limit) {
            throw new InvalidArgumentException('Card detail section count exceeds the configured limit.');
        }
        $keys = [];
        foreach ($sections as $section) {
            if (! $section instanceof CardDetailSectionData || isset($keys[$section->key])) {
                throw new InvalidArgumentException('Card detail providers must return uniquely keyed section data.');
            }
            $keys[$section->key] = true;
        }

        usort($sections, static fn (CardDetailSectionData $a, CardDetailSectionData $b): int => [$a->order, $a->key] <=> [$b->order, $b->key]);

        return $sections;
    }

    /** @return list<array<string, mixed>> */
    private function detailSectionsForRender(): array
    {
        if (! $this->definition()->settings->features->details) {
            $this->closeDetails();

            return [];
        }
        if ($this->detailCardId === null || $this->detail === null) {
            return [];
        }
        $card = $this->provider()->card($this->context(), $this->detailCardId);
        if ($card === null) {
            $this->closeDetails();

            return [];
        }
        $this->detail = $card->toArray();
        $class = $this->definition()->detailsProviderClass;
        if ($class === null) {
            $this->detailSectionPages = [];

            return [];
        }
        $provider = app($class);
        if (! $provider instanceof CardDetailsProvider) {
            $this->detailSectionPages = [];

            return [];
        }

        $visible = [];
        foreach ($this->sectionDescriptors($provider, $card) as $descriptor) {
            if (! $descriptor->canView()) {
                unset($this->detailSectionPages[$descriptor->key]);
                continue;
            }
            $section = $descriptor->toArray();
            $section['loaded'] = isset($this->detailSectionPages[$descriptor->key]);
            $section['content'] = $this->detailSectionPages[$descriptor->key] ?? null;
            if (! $this->definition()->settings->features->customActions) {
                $section['actions'] = [];
                if (is_array($section['content'])) {
                    $section['content']['actions'] = [];
                }
            }
            $visible[] = $section;
        }
        $allowedKeys = array_column($visible, 'key');
        $this->detailSectionPages = array_intersect_key($this->detailSectionPages, array_fill_keys($allowedKeys, true));
        $this->detailSectionOperations = array_intersect_key($this->detailSectionOperations, array_fill_keys($allowedKeys, true));

        return $visible;
    }

    public function openArchive(): void
    {
        $page = $this->provider()->archives($this->context(), $this->query());
        $this->archiveItems = array_map(static fn ($card) => $card->toArray(), $page->items);
        $this->archiveOpen = true;
    }

    public function closeArchive(): void
    {
        $this->archiveOpen = false;
    }
}

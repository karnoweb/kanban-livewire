<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use Karnoweb\Kanban\Actions\UnsupportedActionHandler;

final readonly class BoardDefinition
{
    /**
     * @param class-string $providerClass
     * @param class-string $handlerClass
     * @param array<string, string> $views
     * @param list<StageData> $inlineStages
     * @param list<CardData> $inlineCards
     */
    public function __construct(
        public string $key,
        public BoardData $board,
        public string $providerClass,
        public string $handlerClass,
        public KanbanSettings $settings,
        public array $views = [],
        public array $inlineStages = [],
        public array $inlineCards = [],
        public CardFormSchema $cardForm = new CardFormSchema,
        public StageFormSchema $stageForm = new StageFormSchema,
    ) {}

    public function isReadOnly(): bool
    {
        return $this->handlerClass === UnsupportedActionHandler::class || ! $this->settings->features->writesEnabled();
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Closure;
use Karnoweb\Kanban\Actions\UnsupportedActionHandler;
use Karnoweb\Kanban\Contracts\ActionHandler;
use Karnoweb\Kanban\Contracts\DataProvider;
use Karnoweb\Kanban\Data\BoardData;
use Karnoweb\Kanban\Data\BoardDefinition;
use Karnoweb\Kanban\Data\CardData;
use Karnoweb\Kanban\Data\KanbanSettings;
use Karnoweb\Kanban\Data\StageData;
use Karnoweb\Kanban\Exceptions\InvalidBoardDefinition;
use Karnoweb\Kanban\Providers\InlineDataProvider;

final class BoardBuilder
{
    private ?string $key = null;

    private ?BoardData $board = null;

    private ?string $providerClass = null;

    private ?string $handlerClass = null;

    private KanbanSettings $settings;

    /** @var array<string, string> */
    private array $views = [];

    /** @var list<StageData> */
    private array $stages = [];

    /** @var list<CardData> */
    private array $cards = [];

    public function __construct()
    {
        $this->settings = KanbanSettings::defaults();
    }

    public function key(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function board(BoardData $board): self
    {
        $this->board = $board;

        return $this;
    }

    public function pipeline(BoardData $board): self
    {
        return $this->board($board);
    }

    /** @param list<StageData> $stages */
    public function stages(array $stages): self
    {
        $this->stages = $stages;

        return $this;
    }

    /** @param list<CardData> $cards */
    public function cards(array $cards): self
    {
        $this->cards = $cards;

        return $this;
    }

    /** @param class-string<DataProvider> $class */
    public function provider(string $class): self
    {
        $this->providerClass = $class;

        return $this;
    }

    /** @param class-string<ActionHandler> $class */
    public function handler(string $class): self
    {
        $this->handlerClass = $class;

        return $this;
    }

    public function settings(KanbanSettings|array $settings): self
    {
        $this->settings = $settings instanceof KanbanSettings ? $settings : KanbanSettings::fromArray($settings);

        return $this;
    }

    public function setting(KanbanSettings|array $settings): self
    {
        return $this->settings($settings);
    }

    /** @param class-string<ActionHandler>|Closure $handler */
    public function onMove(string|Closure $handler): self
    {
        return $this->rejectClosure($handler);
    }

    /** @param class-string<ActionHandler>|Closure $handler */
    public function onClick(string|Closure $handler): self
    {
        return $this->rejectClosure($handler);
    }

    public function cardView(string $view): self
    {
        $this->views['card'] = $view;

        return $this;
    }

    public function columnView(string $view): self
    {
        $this->views['stage'] = $view;

        return $this;
    }

    public function listView(string $view): self
    {
        $this->views['list'] = $view;

        return $this;
    }

    public function build(): BoardDefinition
    {
        $provider = $this->providerClass ?? InlineDataProvider::class;
        $handler = $this->handlerClass ?? UnsupportedActionHandler::class;

        $definition = new BoardDefinition(
            key: (string) $this->key,
            board: $this->board ?? throw new InvalidBoardDefinition('Board data is required.', $this->key),
            providerClass: $provider,
            handlerClass: $handler,
            settings: $this->settings,
            views: $this->views,
            inlineStages: $this->stages,
            inlineCards: $this->cards,
        );

        return (new BoardDefinitionValidator)->validate($definition);
    }

    /** @param class-string<ActionHandler>|Closure $handler */
    private function rejectClosure(string|Closure $handler): self
    {
        if ($handler instanceof Closure) {
            throw new InvalidBoardDefinition('Closures cannot be stored in board state. Pass an ActionHandler class.', $this->key);
        }

        return $this->handler($handler);
    }
}

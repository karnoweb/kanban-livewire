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

    private ?array $sources = null;

    private array $stageMap = [];

    private ?\Karnoweb\Kanban\Data\SwimlaneDefinition $swimlane = null;

    private bool $pageSizeExplicit = false;

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

    /** Explicit per-board allowlist; an empty array permits no sources. */
    public function sources(array $keys): self
    {
        foreach ($keys as $key) {
            if (! is_string($key)) {
                throw new InvalidBoardDefinition('Sources must be string slugs.', $this->key);
            }
            \Karnoweb\Kanban\Models\CardIdentity::assertSource($key);
        }
        $this->sources = array_values(array_unique($keys));

        return $this;
    }

    public function stageMap(array $map): self
    {
        $this->stageMap = $map;

        return $this;
    }

    public function swimlanes(\Karnoweb\Kanban\Data\SwimlaneDefinition $definition): self
    {
        $this->swimlane = $definition;

        return $this;
    }

    public function settings(KanbanSettings|array $settings): self
    {
        $this->pageSizeExplicit = $settings instanceof KanbanSettings || isset($settings['pagination']['page_size']);
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
        $provider = $this->providerClass ?? ($this->sources !== null ? \Karnoweb\Kanban\Providers\EloquentKanbanDataProvider::class : InlineDataProvider::class);
        $handler = $this->handlerClass ?? ($this->sources !== null && $this->providerClass === null ? \Karnoweb\Kanban\Actions\EloquentKanbanActionHandler::class : UnsupportedActionHandler::class);
        $settings = $this->settings;
        if ($provider === \Karnoweb\Kanban\Providers\EloquentKanbanDataProvider::class) {
            $values = get_object_vars($settings);
            $values['features'] = new \Karnoweb\Kanban\Data\FeatureSettings(...array_replace(get_object_vars($settings->features), [
                'createStage' => false, 'updateStage' => false, 'reorderStages' => false, 'archiveStage' => false,
            ]));
            if (! $this->pageSizeExplicit) {
                $page = get_object_vars($settings->pagination);
                $page['pageSize'] = min((int) $settings->model->eloquent['default_page_size'], $settings->pagination->maxPageSize);
                $values['pagination'] = new \Karnoweb\Kanban\Data\PaginationSettings(...$page);
            }
            $settings = new KanbanSettings(...$values);
        }

        $definition = new BoardDefinition(
            key: (string) $this->key,
            board: $this->board ?? throw new InvalidBoardDefinition('Board data is required.', $this->key),
            providerClass: $provider,
            handlerClass: $handler,
            settings: $settings,
            views: $this->views,
            inlineStages: $this->stages,
            inlineCards: $this->cards,
            cardForm: new \Karnoweb\Kanban\Data\CardFormSchema(array_merge((new \Karnoweb\Kanban\Data\CardFormSchema)->fields, $this->sources !== null ? ['source'] : [])),
            sources: $this->sources,
            stageMap: $this->stageMap,
            swimlane: $this->swimlane,
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

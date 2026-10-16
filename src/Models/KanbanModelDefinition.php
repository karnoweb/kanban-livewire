<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Models;

use BackedEnum;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\StageData;
use Karnoweb\Kanban\Enums\WipMode;

/** Request-local executable definition; never store in Livewire state or a singleton. */
final class KanbanModelDefinition
{
    public array $fieldMap = ['id' => 'id', 'title' => 'title', 'stage' => 'status'];
    public array $stageDefinitions = [];
    public array $filterMap = [];
    public array $sortFields = ['manualOrder', 'title'];
    public array $searchFields = ['title'];
    public array $relations = [];
    public array $selected = ['*'];
    public array $transitionMap = [];
    public array $validationRules = ['title' => ['required', 'string', 'max:300']];
    public array $hooks = [];
    public array $customActions = [];
    public array $actionRules = [];
    public array $metricDefinitions = [];
    public ?Closure $visibility = null;
    public ?Closure $authorization = null;
    public ?Closure $presentation = null;
    public ?Closure $groupAuthorization = null;
    public ?string $cardTemplate = null;
    public ?string $formTemplate = null;

    private function __construct(public readonly string $source, public readonly string $model)
    {
        CardIdentity::assertSource($source);
        if (! is_subclass_of($model, Model::class)) {
            throw new InvalidArgumentException('Definition model must extend Eloquent Model.');
        }
    }

    public static function make(string $source, string $model): self
    {
        return new self($source, $model);
    }

    public static function assertColumn(string $column): string
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $column) !== 1) {
            throw new InvalidArgumentException('Only declared, simple column names are supported.');
        }

        return $column;
    }

    public function fields(array $fields): self
    {
        $allowed = ['id', 'title', 'description', 'stage', 'rank', 'priority', 'assignee', 'dueAt', 'createdAt', 'version', 'archivedAt', 'group'];
        foreach ($fields as $field => $column) {
            if (! in_array($field, $allowed, true) || ! is_string($column)) {
                throw new InvalidArgumentException('Unknown field mapping.');
            }
            self::assertColumn($column);
        }
        $this->fieldMap = array_replace($this->fieldMap, $fields);

        return $this;
    }

    /** BackedEnum class or value => label/options. */
    public function stages(string|array $stages): self
    {
        if (is_string($stages)) {
            if (! is_subclass_of($stages, BackedEnum::class)) {
                throw new InvalidArgumentException('Stage enum must be a BackedEnum.');
            }
            $stages = array_combine(array_map(fn ($case) => (string) $case->value, $stages::cases()), array_map(
                fn ($case) => ['title' => method_exists($case, 'label') ? $case->label() : $case->name,
                    'color' => method_exists($case, 'color') ? $case->color() : null,
                    'order' => method_exists($case, 'order') ? $case->order() : array_search($case, $stages::cases(), true),
                    'wipLimit' => method_exists($case, 'wipLimit') ? $case->wipLimit() : null,
                    'transitions' => method_exists($case, 'allowedTransitions') ? $case->allowedTransitions() : [],
                ], $stages::cases(),
            ));
        }
        foreach ($stages as $value => $options) {
            if (! is_string($options) && ! is_array($options)) {
                throw new InvalidArgumentException('Stage must be a label or an options array.');
            }
            $this->stageDefinitions[(string) $value] = is_string($options) ? ['title' => $options] : $options;
        }

        return $this;
    }

    public function visibleQuery(callable $callback): self
    {
        $this->visibility = Closure::fromCallable($callback);

        return $this;
    }

    public function authorize(callable $callback): self
    {
        $this->authorization = Closure::fromCallable($callback);

        return $this;
    }

    /** Explicit domain guard receives the requested group as well as the record and context. */
    public function moveGroups(callable $callback): self
    {
        $this->groupAuthorization = Closure::fromCallable($callback);

        return $this;
    }

    public function query(BoardContext $context): Builder
    {
        $query = (new $this->model)->newQuery();
        if ($this->visibility === null) {
            return $query->whereRaw('1 = 0');
        }
        $resolved = ($this->visibility)($query, $context);
        if ($resolved !== null && $resolved !== $query) {
            throw new InvalidArgumentException('visibleQuery must constrain and return the supplied builder, or return void.');
        }

        return $query;
    }

    public function allows(string $ability, ?Model $record, BoardContext $context): bool
    {
        return $this->authorization !== null && ($this->authorization)($ability, $record, $context) === true;
    }

    public function filters(array $filters): self
    {
        foreach ($filters as $name => $column) {
            if (is_int($name)) {
                $name = $column;
                $column = $this->fieldMap[$column] ?? $column;
            }
            self::assertColumn((string) $name);
            $this->filterMap[$name] = self::assertColumn($column);
        }

        return $this;
    }

    public function sorts(array $fields): self
    {
        foreach ($fields as $field) {
            if (! in_array($field, ['id', 'manualOrder', 'title', 'priority', 'dueAt', 'createdAt'], true)) {
                throw new InvalidArgumentException('Unknown sort field.');
            }
        }
        $this->sortFields = $fields;

        return $this;
    }

    public function searchable(array $columns): self
    {
        $this->searchFields = array_map(self::assertColumn(...), $columns);

        return $this;
    }

    public function with(array $relations): self
    {
        $this->relations = $relations;

        return $this;
    }

    public function select(array $columns): self
    {
        $this->selected = array_map(self::assertColumn(...), $columns);

        return $this;
    }

    public function transitions(array $transitions): self
    {
        $this->transitionMap = $transitions;

        return $this;
    }

    public function rules(array $rules): self
    {
        $this->validationRules = $rules;

        return $this;
    }

    public function present(callable $callback): self
    {
        $this->presentation = Closure::fromCallable($callback);

        return $this;
    }

    public function cardView(string $view): self
    {
        (new \Karnoweb\Kanban\Support\ViewResolver)->assertAllowed('card', $view);
        $this->cardTemplate = $view;

        return $this;
    }

    public function formView(string $view): self
    {
        (new \Karnoweb\Kanban\Support\ViewResolver)->assertAllowed('card', $view);
        $this->formTemplate = $view;

        return $this;
    }

    public function hook(string $phase, callable $callback): self
    {
        if (! in_array($phase, ['validate', 'before', 'after', 'afterCommit'], true)) {
            throw new InvalidArgumentException('Unknown lifecycle hook.');
        }
        $this->hooks[$phase] = Closure::fromCallable($callback);

        return $this;
    }

    public function action(string $name, callable $callback, array $rules = []): self
    {
        CardIdentity::assertSource($name);
        $this->customActions[$name] = Closure::fromCallable($callback);
        $this->actionRules[$name] = $rules;

        return $this;
    }

    public function metrics(array $metrics): self
    {
        foreach ($metrics as $name => $metric) {
            if (! is_array($metric) || ! in_array($metric['aggregate'] ?? '', ['sum', 'avg', 'min', 'max'], true)) {
                throw new InvalidArgumentException('Unknown aggregate.');
            }
            self::assertColumn($metric['column']);
            self::assertColumn($name);
        }
        $this->metricDefinitions = $metrics;

        return $this;
    }

    public function stage(string $value, string $boardId, ?string $mapped = null): StageData
    {
        $options = $this->stageDefinitions[$value] ?? throw new InvalidArgumentException('Unknown stage value.');

        return new StageData(
            id: $mapped ?? $value, boardId: $boardId, title: $options['title'] ?? $value,
            order: (int) ($options['order'] ?? array_search($value, array_map('strval', array_keys($this->stageDefinitions)), true)),
            visible: (bool) ($options['visible'] ?? true), readOnly: (bool) ($options['readOnly'] ?? false),
            disabled: (bool) ($options['disabled'] ?? false), wipLimit: $options['wipLimit'] ?? null,
            wipMode: WipMode::from($options['wipMode'] ?? (isset($options['wipLimit']) ? 'strict' : 'off')),
            color: $options['color'] ?? null,
        );
    }
}

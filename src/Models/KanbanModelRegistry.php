<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Karnoweb\Kanban\Concerns\HasKanbanCards;
use Karnoweb\Kanban\Contracts\DataProvider;

final class KanbanModelRegistry
{
    /** Only class descriptors; never actor, query, model instance or definition state. */
    private array $models = [];

    private array $providers = [];

    public function register(string $source, string $model): void
    {
        $this->assertAvailable($source);
        if (! is_subclass_of($model, Model::class) || ! in_array(HasKanbanCards::class, class_uses_recursive($model), true)) {
            throw new InvalidArgumentException('Registered models must extend Eloquent Model and use HasKanbanCards.');
        }
        $this->models[$source] = $model;
    }

    /** Non-Eloquent sources require an explicit board provider/handler. */
    public function registerProvider(string $source, string $provider): void
    {
        $this->assertAvailable($source);
        if (! is_a($provider, DataProvider::class, true)) {
            throw new InvalidArgumentException('Source provider must implement DataProvider.');
        }
        $this->providers[$source] = $provider;
    }

    public function definition(string $source): KanbanModelDefinition
    {
        $model = $this->models[$source] ?? throw new InvalidArgumentException('Unknown Eloquent source.');
        $definition = $model::kanbanDefinition();
        if ($definition->source !== $source || $definition->model !== $model) {
            throw new InvalidArgumentException('Model definition must match its registered source and class.');
        }
        if ($definition->stageDefinitions === []) {
            $cast = (new $model)->getCasts()[$definition->fieldMap['stage']] ?? null;
            if (is_string($cast) && is_subclass_of($cast, \BackedEnum::class)) {
                $definition->stages($cast);
            }
        }

        return $definition;
    }

    public function has(string $source): bool
    {
        return isset($this->models[$source]) || isset($this->providers[$source]);
    }

    public function isEloquent(string $source): bool
    {
        return isset($this->models[$source]);
    }

    private function assertAvailable(string $source): void
    {
        CardIdentity::assertSource($source);
        if ($this->has($source)) {
            throw new InvalidArgumentException('Source is already registered.');
        }
    }
}

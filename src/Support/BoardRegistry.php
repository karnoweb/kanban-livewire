<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Illuminate\Contracts\Container\Container;
use Karnoweb\Kanban\Contracts\BoardFactory;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardDefinition;
use Karnoweb\Kanban\Exceptions\InvalidBoardDefinition;
use Karnoweb\Kanban\Exceptions\UnknownBoard;

final class BoardRegistry
{
    /** @var array<string, class-string<BoardFactory>> */
    private array $factories = [];

    public function __construct(private readonly Container $container) {}

    /** @param class-string<BoardFactory> $class */
    public function register(string $key, string $class): void
    {
        $key = trim($key);

        if ($key === '' || isset($this->factories[$key]) || ! is_a($class, BoardFactory::class, true)) {
            throw new InvalidBoardDefinition('Board key must be unique and factory must implement BoardFactory.', $key);
        }

        $this->factories[$key] = $class;
    }

    public function resolve(string $key, BoardContext $context): BoardDefinition
    {
        if ($context->boardKey !== $key) {
            throw new UnknownBoard($key);
        }

        $class = $this->factories[$key] ?? throw new UnknownBoard($key);
        $factory = $this->container->make($class);

        if (! $factory instanceof BoardFactory) {
            throw new InvalidBoardDefinition('Resolved factory is invalid.', $key);
        }

        $definition = $factory->make($context);

        if ($definition->key !== $key) {
            throw new InvalidBoardDefinition('Factory key does not match the registry key.', $key);
        }

        return $definition;
    }

    public function has(string $key): bool
    {
        return isset($this->factories[$key]);
    }

    /** @return class-string<BoardFactory> */
    public function factory(string $key): string
    {
        return $this->factories[$key] ?? throw new UnknownBoard($key);
    }
}

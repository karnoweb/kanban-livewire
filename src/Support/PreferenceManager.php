<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use Karnoweb\Kanban\Contracts\PreferenceStore;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Enums\ViewMode;

final class PreferenceManager
{
    public function __construct(
        private readonly PreferenceStore $store,
        private readonly ScopeHasher $hasher,
    ) {}

    public function view(BoardContext $context): ?ViewMode
    {
        $value = $this->store->get($this->key($context, 'view'));

        return is_string($value) ? ViewMode::tryFrom($value) : null;
    }

    public function rememberView(BoardContext $context, ViewMode $view): void
    {
        $this->store->set($this->key($context, 'view'), $view->value);
    }

    public function theme(BoardContext $context): ?string
    {
        $value = $this->store->get($this->key($context, 'theme'));

        return is_string($value) && in_array($value, ['light', 'dark', 'system'], true) ? $value : null;
    }

    public function rememberTheme(BoardContext $context, string $theme): void
    {
        if (in_array($theme, ['light', 'dark', 'system'], true)) {
            $this->store->set($this->key($context, 'theme'), $theme);
        }
    }

    public function direction(BoardContext $context): ?string
    {
        $value = $this->store->get($this->key($context, 'dir'));

        return is_string($value) && in_array($value, ['ltr', 'rtl'], true) ? $value : null;
    }

    public function rememberDirection(BoardContext $context, string $direction): void
    {
        if (in_array($direction, ['ltr', 'rtl'], true)) {
            $this->store->set($this->key($context, 'dir'), $direction);
        }
    }

    public function density(BoardContext $context): ?string
    {
        $value = $this->store->get($this->key($context, 'density'));

        return is_string($value) && in_array($value, ['compact', 'comfortable'], true) ? $value : null;
    }

    public function rememberDensity(BoardContext $context, string $density): void
    {
        if (in_array($density, ['compact', 'comfortable'], true)) {
            $this->store->set($this->key($context, 'density'), $density);
        }
    }

    public function clear(BoardContext $context): void
    {
        foreach (['view', 'theme', 'dir', 'density'] as $slot) {
            $this->store->forget($this->key($context, $slot));
        }
    }

    public function collapsed(BoardContext $context): array
    {
        $value = $this->store->get($this->key($context, 'collapsed'));

        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }

    public function rememberCollapsed(BoardContext $context, array $ids): void
    {
        $this->store->set($this->key($context, 'collapsed'), array_slice(array_values(array_filter($ids, 'is_string')), 0, 100));
    }

    private function key(BoardContext $context, string $slot): string
    {
        return 'kanban.'.$slot.'.'.$context->actorId.'.'.$this->hasher->hash($context->scopeKey).'.'.$context->boardKey;
    }
}

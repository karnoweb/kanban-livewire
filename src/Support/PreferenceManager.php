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
        $value = $this->store->get($this->key($context));

        return is_string($value) ? ViewMode::tryFrom($value) : null;
    }

    public function rememberView(BoardContext $context, ViewMode $view): void
    {
        $this->store->set($this->key($context), $view->value);
    }

    private function key(BoardContext $context): string
    {
        return 'kanban.view.'.$context->actorId.'.'.$this->hasher->hash($context->scopeKey).'.'.$context->boardKey;
    }
}

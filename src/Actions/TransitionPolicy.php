<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Actions;

use Karnoweb\Kanban\Data\InteractionSettings;

final class TransitionPolicy
{
    public function allows(InteractionSettings $settings, string $fromStageId, string $toStageId): bool
    {
        if ($fromStageId === $toStageId) {
            return true;
        }

        if ($settings->transitions !== []) {
            return in_array($toStageId, $settings->transitions[$fromStageId] ?? [], true);
        }

        return $settings->transitionPolicy === 'allow';
    }
}

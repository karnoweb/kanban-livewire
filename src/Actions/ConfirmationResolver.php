<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Actions;

use Karnoweb\Kanban\Data\InteractionSettings;
use Karnoweb\Kanban\Enums\ConfirmationMode;

final class ConfirmationResolver
{
    /** @return array{mode: ConfirmationMode, fields: array<string, mixed>} */
    public function forTransition(InteractionSettings $settings, string $fromStageId, string $toStageId): array
    {
        $rule = $settings->confirmationRules[$fromStageId.'>'.$toStageId] ?? null;

        if (is_array($rule)) {
            return [
                'mode' => ConfirmationMode::from((string) ($rule['mode'] ?? ConfirmationMode::Form->value)),
                'fields' => is_array($rule['fields'] ?? null) ? $rule['fields'] : [],
            ];
        }

        return [
            'mode' => $settings->confirmation,
            'fields' => [],
        ];
    }
}

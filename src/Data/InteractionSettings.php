<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;
use Karnoweb\Kanban\Enums\ConfirmationMode;

final readonly class InteractionSettings
{
    public function __construct(
        public bool $animations = true,
        public bool $reducedMotion = false,
        public bool $keyboardAlternative = true,
        public ConfirmationMode $confirmation = ConfirmationMode::None,
        public string $transitionPolicy = 'deny',
        /** @var array<string, list<string>> */
        public array $transitions = [],
        /** @var array<string, array<string, mixed>> */
        public array $confirmationRules = [],
    ) {
        if (! in_array($transitionPolicy, ['deny', 'allow'], true)) {
            throw new InvalidArgumentException('Transition policy must be deny or allow.');
        }
    }
}

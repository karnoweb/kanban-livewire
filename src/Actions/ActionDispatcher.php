<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Actions;

use Throwable;
use Karnoweb\Kanban\Contracts\ActionHandler;
use Karnoweb\Kanban\Contracts\DataProvider;
use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\ActionResult;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardDefinition;
use Karnoweb\Kanban\Enums\ActionStatus;
use Karnoweb\Kanban\Exceptions\BoardAccessDenied;
use Karnoweb\Kanban\Providers\InlineDataProvider;
use Karnoweb\Kanban\Support\ActionRateLimiter;
use Karnoweb\Kanban\Support\AuthorizationGuard;
use Karnoweb\Kanban\Support\TelemetryDispatcher;

final class ActionDispatcher
{
    public function __construct(
        private readonly AuthorizationGuard $guard,
        private readonly ActionRateLimiter $rateLimiter,
        private readonly TelemetryDispatcher $telemetry,
    ) {}

    public function dispatch(ActionCommand $command, BoardContext $context, BoardDefinition $definition): ActionResult
    {
        $provider = $this->provider($definition);
        $started = microtime(true);

        try {
            $this->guard->authorize($command, $context, $definition, $provider);
        } catch (BoardAccessDenied) {
            return new ActionResult(ActionStatus::FORBIDDEN, messageCode: 'forbidden');
        }

        $denied = app(\Karnoweb\Kanban\Support\CommandPolicy::class)->check($command, $context, $definition, $provider);

        if ($denied !== null) {
            return $denied;
        }

        if ($this->rateLimiter->tooMany($context, $definition->settings->security->rateLimitPerMinute)) {
            return new ActionResult(ActionStatus::RATE_LIMITED, messageCode: 'rate_limited');
        }

        $handler = app($definition->handlerClass);

        if (! $handler instanceof ActionHandler) {
            return ActionResult::unsupported();
        }

        try {
            $result = $handler->execute($command, $context);
        } catch (Throwable) {
            $result = new ActionResult(ActionStatus::RETRYABLE_ERROR, messageCode: 'retryable_error');
        }

        $this->telemetry->timing($command->type->value, $started, $result->status->value, [
            'operationId' => $command->operationId,
        ]);
        $this->telemetry->audit($command->operationId, $command->type->value, $result->status->value, [
            'operationId' => $command->operationId,
        ]);

        return $result;
    }

    private function provider(BoardDefinition $definition): DataProvider
    {
        if ($definition->providerClass === InlineDataProvider::class) {
            return new InlineDataProvider($definition->board, $definition->inlineStages, $definition->inlineCards);
        }

        $provider = app($definition->providerClass);

        return $provider instanceof DataProvider ? $provider : new InlineDataProvider($definition->board);
    }
}

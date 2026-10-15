<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Enums;

enum ActionStatus: string
{
    case SUCCESS = 'success';
    case VALIDATION_ERROR = 'validation_error';
    case FORBIDDEN = 'forbidden';
    case NOT_FOUND = 'not_found';
    case CONFLICT = 'conflict';
    case WIP_LIMIT = 'wip_limit';
    case DOMAIN_REJECTED = 'domain_rejected';
    case RATE_LIMITED = 'rate_limited';
    case RETRYABLE_ERROR = 'retryable_error';
    case UNKNOWN_OUTCOME = 'unknown_outcome';
    case UNSUPPORTED = 'unsupported';

    public function isSettledFailure(): bool
    {
        return ! in_array($this, [self::SUCCESS, self::UNKNOWN_OUTCOME], true);
    }
}

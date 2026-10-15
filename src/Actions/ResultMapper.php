<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Actions;

use Karnoweb\Kanban\Data\ActionResult;

final class ResultMapper
{
    /** @return array<string, mixed> */
    public function notice(ActionResult $result): array
    {
        return [
            'status' => $result->status->value,
            'messageCode' => $result->messageCode ?? $result->status->value,
            'fieldErrors' => $result->fieldErrors,
            'safeMessage' => $result->safeMessage,
            'successCount' => $result->successCount,
            'failureCount' => $result->failureCount,
            'noop' => $result->noop,
            'items' => array_map(static fn ($item) => $item->toArray(), $result->itemResults),
        ];
    }
}

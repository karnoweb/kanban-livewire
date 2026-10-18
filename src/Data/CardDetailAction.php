<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;
use Karnoweb\Kanban\Support\ValueGuard;

/** A client request to an explicitly advertised detail-section action. */
final readonly class CardDetailAction
{
    public array $payload;

    public function __construct(
        public string $section,
        public string $action,
        array $payload = [],
        public string $operationId = '',
        public ?string $expectedVersion = null,
    ) {
        foreach (['section' => $section, 'action' => $action] as $field => $value) {
            if (preg_match('/^[a-z][a-z0-9_.-]{0,63}$/D', $value) !== 1) {
                throw new InvalidArgumentException('Detail action '.$field.' is invalid.');
            }
        }
        ValueGuard::id($operationId, 'detailAction.operationId');
        ValueGuard::optionalText($expectedVersion, 128);
        $this->payload = ValueGuard::formData($payload);
    }
}

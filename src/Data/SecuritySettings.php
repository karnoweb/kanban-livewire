<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

final readonly class SecuritySettings
{
    public function __construct(
        public int $metadataBytes = 8192,
        public int $searchLength = 200,
        public int $bulkIds = 100,
        public ?int $rateLimitPerMinute = null,
        public array $allowedUrlSchemes = ['http', 'https'],
    ) {}
}

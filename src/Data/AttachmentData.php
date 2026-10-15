<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;
use Karnoweb\Kanban\Support\UrlSanitizer;
use Karnoweb\Kanban\Support\ValueGuard;

final readonly class AttachmentData
{
    public string $id;

    public string $name;

    public string $mime;

    public ?int $size;

    public string $url;

    public ?string $thumbnailUrl;

    public function __construct(
        string $id,
        string $name,
        string $mime,
        ?int $size,
        string $url,
        ?string $thumbnailUrl = null,
    ) {
        $this->id = ValueGuard::id($id, 'attachment.id');
        $this->name = ValueGuard::requiredText($name, 'attachment.name');
        $this->mime = ValueGuard::requiredText($mime, 'attachment.mime', 120);

        if ($size !== null && $size < 0) {
            throw new InvalidArgumentException('Attachment size cannot be negative.');
        }

        $this->size = $size;
        $sanitizer = new UrlSanitizer;
        $safe = $sanitizer->sanitize($url);

        if ($safe === null) {
            throw new InvalidArgumentException('Attachment URL is required.');
        }

        $this->url = $safe;
        $this->thumbnailUrl = $sanitizer->sanitize($thumbnailUrl);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mime' => $this->mime,
            'size' => $this->size,
            'url' => $this->url,
            'thumbnailUrl' => $this->thumbnailUrl,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use Karnoweb\Kanban\Support\ValueGuard;

final readonly class CommentData
{
    public function __construct(public string $id, public string $body, public ?string $author = null, public ?string $createdAt = null)
    {
        ValueGuard::id($id, 'comment.id');
        ValueGuard::requiredText($body, 'comment.body', 5000);
        ValueGuard::optionalText($author, 200);
        ValueGuard::optionalText($createdAt, 100);
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'body' => $this->body, 'author' => $this->author, 'createdAt' => $this->createdAt];
    }
}

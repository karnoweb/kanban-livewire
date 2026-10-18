<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;
use Karnoweb\Kanban\Support\ValueGuard;

final readonly class CardData
{
    /** @param list<CardBadgeData> $badges
     * @param list<AssigneeData> $assignees
     * @param list<AttachmentData> $attachments
     * @param list<CardActionData> $actions
     * @param array<string, mixed> $metadata
     * @param array<string, bool> $permissions
     * @param list<ChecklistItemData> $checklist
     * @param list<CommentData> $comments
     */
    public function __construct(
        public string $id,
        public string $boardId,
        public string $stageId,
        public string $title,
        public ?string $description = null,
        public ?string $priority = null,
        public array $badges = [],
        public array $assignees = [],
        public array $attachments = [],
        public ?string $createdAt = null,
        public ?string $dueAt = null,
        public array $metadata = [],
        public array $actions = [],
        public array $permissions = [],
        public ?string $version = null,
        public ?string $manualOrder = null,
        public bool $archived = false,
        public array $checklist = [],
        public array $comments = [],
    ) {
        ValueGuard::id($id, 'card.id');
        ValueGuard::id($boardId, 'card.boardId');
        ValueGuard::id($stageId, 'card.stageId');
        ValueGuard::requiredText($title, 'card.title');
        ValueGuard::optionalText($description);
        ValueGuard::metadata($metadata);

        foreach ($checklist as $item) {
            if (! $item instanceof ChecklistItemData) {
                throw new InvalidArgumentException('Checklist items must be ChecklistItemData.');
            }
        }
        foreach ($comments as $comment) {
            if (! $comment instanceof CommentData) {
                throw new InvalidArgumentException('Comments must be CommentData.');
            }
        }
        if (count($checklist) > 100 || count($comments) > 100
            || strlen(json_encode([$checklist, $comments], JSON_THROW_ON_ERROR)) > (int) config('kanban.limits.detail_bytes', 32768)) {
            throw new InvalidArgumentException('Card details exceed the size cap.');
        }

        foreach ($badges as $badge) {
            if (! $badge instanceof CardBadgeData) {
                throw new InvalidArgumentException('Card badges must be CardBadgeData.');
            }
        }

        foreach ($assignees as $assignee) {
            if (! $assignee instanceof AssigneeData) {
                throw new InvalidArgumentException('Card assignees must be AssigneeData.');
            }
        }

        foreach ($attachments as $attachment) {
            if (! $attachment instanceof AttachmentData) {
                throw new InvalidArgumentException('Card attachments must be AttachmentData.');
            }
        }

        foreach ($actions as $action) {
            if (! $action instanceof CardActionData) {
                throw new InvalidArgumentException('Card actions must be CardActionData.');
            }
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'boardId' => $this->boardId,
            'stageId' => $this->stageId,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'badges' => array_map(static fn (CardBadgeData $badge): array => $badge->toArray(), $this->badges),
            'assignees' => array_map(static fn (AssigneeData $assignee): array => $assignee->toArray(), $this->assignees),
            'attachments' => array_map(static fn (AttachmentData $attachment): array => $attachment->toArray(), $this->attachments),
            'createdAt' => $this->createdAt,
            'dueAt' => $this->dueAt,
            'metadata' => $this->metadata,
            'actions' => array_map(static fn (CardActionData $action): array => $action->toArray(), $this->actions),
            'permissions' => $this->permissions,
            'version' => $this->version,
            'manualOrder' => $this->manualOrder,
            'archived' => $this->archived,
            'checklist' => array_map(static fn (ChecklistItemData $item): array => $item->toArray(), $this->checklist),
            'comments' => array_map(static fn (CommentData $comment): array => $comment->toArray(), $this->comments),
        ];
    }
}

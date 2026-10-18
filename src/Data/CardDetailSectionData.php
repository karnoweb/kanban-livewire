<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use InvalidArgumentException;
use Karnoweb\Kanban\Support\ValueGuard;

/** Safe, serializable section data. Values are escaped by the package view; HTML is never accepted. */
final readonly class CardDetailSectionData
{
    /**
     * @param list<array{label:string, id?:string, value?:string, description?:string, status?:string, url?:string, thumbnailUrl?:string}> $items
     * @param array<string, string> $actions action key => visible label
     */
    public function __construct(
        public string $key,
        public string $title,
        public int $order = 0,
        public string $state = 'unloaded',
        public ?string $summary = null,
        public array $items = [],
        public ?string $nextCursor = null,
        public array $capabilities = [],
        public array $actions = [],
    ) {
        if (preg_match('/^[a-z][a-z0-9_.-]{0,63}$/D', $key) !== 1) {
            throw new InvalidArgumentException('Detail section key is invalid.');
        }
        ValueGuard::requiredText($title, 'detailSection.title', 120);
        if (! in_array($state, ['unloaded', 'ready', 'empty', 'unavailable'], true)) {
            throw new InvalidArgumentException('Detail section state is invalid.');
        }
        ValueGuard::optionalText($summary, 1000);
        if (count($items) > 100) {
            throw new InvalidArgumentException('Detail section page exceeds the item limit.');
        }
        foreach ($items as $item) {
            if (! is_array($item) || ! is_string($item['label'] ?? null)) {
                throw new InvalidArgumentException('Detail section items require a text label.');
            }
            foreach (['label' => 300, 'id' => 128, 'value' => 1000, 'description' => 2000, 'status' => 120, 'url' => 2048, 'thumbnailUrl' => 2048] as $field => $max) {
                if (isset($item[$field]) && (! is_string($item[$field]) || strlen($item[$field]) > $max)) {
                    throw new InvalidArgumentException('Detail section item text is invalid.');
                }
            }
            if (array_diff(array_keys($item), ['label', 'id', 'value', 'description', 'status', 'url', 'thumbnailUrl']) !== []) {
                throw new InvalidArgumentException('Detail section items accept text fields only.');
            }
            foreach (['url', 'thumbnailUrl'] as $field) {
                if (isset($item[$field])) {
                    (new \Karnoweb\Kanban\Support\UrlSanitizer)->sanitize($item[$field]);
                }
            }
        }
        if (count($capabilities) > 12 || array_diff(array_keys($capabilities), ['canView', 'canEdit', 'canCreate', 'canDelete', 'canComment', 'canReorder']) !== []) {
            throw new InvalidArgumentException('Detail section capabilities are invalid.');
        }
        foreach ($capabilities as $capability => $allowed) {
            if (! is_bool($allowed)) {
                throw new InvalidArgumentException('Detail section capabilities must be boolean.');
            }
        }
        if (count($actions) > 12) {
            throw new InvalidArgumentException('Detail section action limit exceeded.');
        }
        foreach ($actions as $name => $label) {
            if (! is_string($name) || preg_match('/^[a-z][a-z0-9_.-]{0,63}$/D', $name) !== 1 || ! is_string($label)) {
                throw new InvalidArgumentException('Detail section action is invalid.');
            }
            ValueGuard::requiredText($label, 'detailSection.action', 80);
        }
        ValueGuard::optionalText($nextCursor, 2048);
        if (strlen(json_encode([$items, $actions], JSON_THROW_ON_ERROR)) > (int) config('kanban.limits.detail_section_bytes', 32768)) {
            throw new InvalidArgumentException('Detail section payload exceeds the size cap.');
        }
    }

    public function canView(): bool
    {
        return ($this->capabilities['canView'] ?? false) === true;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key, 'title' => $this->title, 'order' => $this->order, 'state' => $this->state,
            'summary' => $this->summary, 'items' => $this->items, 'nextCursor' => $this->nextCursor,
            'capabilities' => $this->capabilities, 'actions' => $this->actions,
        ];
    }
}

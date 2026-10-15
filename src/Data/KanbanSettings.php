<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Data;

use Karnoweb\Kanban\Enums\ViewMode;
use Karnoweb\Kanban\Realtime\RealtimeSettings;

final readonly class KanbanSettings
{
    public function __construct(
        public DisplaySettings $views,
        public FeatureSettings $features,
        public PaginationSettings $pagination,
        public AppearanceSettings $appearance,
        public RealtimeSettings $realtime,
        public InteractionSettings $interaction,
        public SecuritySettings $security,
    ) {}

    public static function defaults(): self
    {
        $features = config('kanban.features', []);
        $appearance = config('kanban.appearance', []);
        $interaction = config('kanban.interaction', []);
        $realtime = config('kanban.realtime', []);
        $security = config('kanban.security', []);

        return new self(
            views: new DisplaySettings(
                defaultView: ViewMode::fromSetting((string) config('kanban.default_view', 'board')),
                allowedViews: config('kanban.allowed_views', ['board', 'list']),
                columnWidth: '18rem',
                density: (string) ($appearance['density'] ?? 'comfortable'),
                detailSurface: (string) ($appearance['detail_surface'] ?? 'modal'),
            ),
            features: new FeatureSettings(
                dragDrop: (bool) ($features['drag_drop'] ?? true),
                reorderCards: (bool) ($features['reorder_cards'] ?? true),
                reorderStages: (bool) ($features['reorder_stages'] ?? true),
                createCard: (bool) ($features['create_card'] ?? true),
                updateCard: (bool) ($features['update_card'] ?? true),
                createStage: (bool) ($features['create_stage'] ?? true),
                updateStage: (bool) ($features['update_stage'] ?? true),
                archiveCard: (bool) ($features['archive_card'] ?? true),
                archiveStage: (bool) ($features['archive_stage'] ?? true),
                restoreCard: (bool) ($features['restore_card'] ?? true),
                search: (bool) ($features['search'] ?? true),
                filters: (bool) ($features['filters'] ?? true),
                bulk: (bool) ($features['bulk'] ?? true),
                wip: (bool) ($features['wip'] ?? true),
                details: (bool) ($features['details'] ?? true),
                virtualization: (bool) ($features['virtualization'] ?? true),
                realtime: (bool) ($features['realtime'] ?? false),
                customActions: (bool) ($features['custom_actions'] ?? true),
            ),
            pagination: new PaginationSettings(
                pageSize: (int) config('kanban.page_size', 25),
                maxPageSize: (int) config('kanban.max_page_size', 100),
                virtualization: (bool) ($features['virtualization'] ?? true),
            ),
            appearance: new AppearanceSettings(
                theme: (string) ($appearance['theme'] ?? 'system'),
                direction: (string) ($appearance['direction'] ?? 'ltr'),
            ),
            realtime: new RealtimeSettings(
                enabled: (bool) ($realtime['enabled'] ?? false),
                refresh: (string) ($realtime['refresh'] ?? 'invalidate'),
                reconnect: (bool) ($realtime['reconnect'] ?? true),
                dedupeLimit: (int) ($realtime['dedupe_limit'] ?? 200),
                coalesceMs: (int) ($realtime['coalesce_ms'] ?? 50),
            ),
            interaction: new InteractionSettings(
                animations: (bool) ($interaction['animations'] ?? true),
                reducedMotion: (bool) ($interaction['reduced_motion'] ?? false),
                keyboardAlternative: (bool) ($interaction['keyboard_alternative'] ?? true),
                transitionPolicy: (string) config('kanban.default_transition_policy', 'deny'),
            ),
            security: new SecuritySettings(
                metadataBytes: (int) ($security['metadata_bytes'] ?? 8192),
                searchLength: (int) ($security['search_length'] ?? 200),
                bulkIds: (int) ($security['bulk_ids'] ?? 100),
                rateLimitPerMinute: $security['rate_limit_per_minute'] ?? null,
            ),
        );
    }

    /** @param array<string, mixed> $settings */
    public static function fromArray(array $settings): self
    {
        $base = self::defaults();

        if (isset($settings['views']) && is_array($settings['views'])) {
            $views = $settings['views'];
            $base = new self(
                views: new DisplaySettings(
                    defaultView: ViewMode::fromSetting((string) ($views['default'] ?? $base->views->defaultView->value)),
                    allowedViews: $views['allowed'] ?? $base->views->allowedViews,
                    columnWidth: (string) ($views['column_width'] ?? $base->views->columnWidth),
                    density: (string) ($views['density'] ?? $base->views->density),
                    detailSurface: (string) ($views['detail_surface'] ?? $base->views->detailSurface),
                ),
                features: $base->features,
                pagination: $base->pagination,
                appearance: $base->appearance,
                realtime: $base->realtime,
                interaction: $base->interaction,
                security: $base->security,
            );
        }

        if (isset($settings['features']) && is_array($settings['features'])) {
            $features = array_replace([
                'drag_drop' => $base->features->dragDrop,
                'reorder_cards' => $base->features->reorderCards,
                'reorder_stages' => $base->features->reorderStages,
                'create_card' => $base->features->createCard,
                'update_card' => $base->features->updateCard,
                'create_stage' => $base->features->createStage,
                'update_stage' => $base->features->updateStage,
                'archive_card' => $base->features->archiveCard,
                'archive_stage' => $base->features->archiveStage,
                'restore_card' => $base->features->restoreCard,
                'search' => $base->features->search,
                'filters' => $base->features->filters,
                'bulk' => $base->features->bulk,
                'wip' => $base->features->wip,
                'details' => $base->features->details,
                'virtualization' => $base->features->virtualization,
                'realtime' => $base->features->realtime,
                'custom_actions' => $base->features->customActions,
            ], $settings['features']);

            $base = new self(
                views: $base->views,
                features: new FeatureSettings(
                    (bool) $features['drag_drop'],
                    (bool) $features['reorder_cards'],
                    (bool) $features['reorder_stages'],
                    (bool) $features['create_card'],
                    (bool) $features['update_card'],
                    (bool) $features['create_stage'],
                    (bool) $features['update_stage'],
                    (bool) $features['archive_card'],
                    (bool) $features['archive_stage'],
                    (bool) $features['restore_card'],
                    (bool) $features['search'],
                    (bool) $features['filters'],
                    (bool) $features['bulk'],
                    (bool) $features['wip'],
                    (bool) $features['details'],
                    (bool) $features['virtualization'],
                    (bool) $features['realtime'],
                    (bool) $features['custom_actions'],
                ),
                pagination: $base->pagination,
                appearance: $base->appearance,
                realtime: $base->realtime,
                interaction: $base->interaction,
                security: $base->security,
            );
        }

        if (isset($settings['pagination']) && is_array($settings['pagination'])) {
            $page = $settings['pagination'];
            $base = new self(
                views: $base->views,
                features: $base->features,
                pagination: new PaginationSettings(
                    pageSize: (int) ($page['page_size'] ?? $base->pagination->pageSize),
                    maxPageSize: (int) ($page['max_page_size'] ?? $base->pagination->maxPageSize),
                    virtualization: (bool) ($page['virtualization'] ?? $base->pagination->virtualization),
                    virtualizationThreshold: (int) ($page['threshold'] ?? $base->pagination->virtualizationThreshold),
                    overscan: (int) ($page['overscan'] ?? $base->pagination->overscan),
                ),
                appearance: $base->appearance,
                realtime: $base->realtime,
                interaction: $base->interaction,
                security: $base->security,
            );
        }

        if (isset($settings['appearance']) && is_array($settings['appearance'])) {
            $appearance = $settings['appearance'];
            $base = new self(
                views: $base->views,
                features: $base->features,
                pagination: $base->pagination,
                appearance: new AppearanceSettings(
                    theme: (string) ($appearance['theme'] ?? $base->appearance->theme),
                    direction: (string) ($appearance['direction'] ?? $base->appearance->direction),
                ),
                realtime: $base->realtime,
                interaction: $base->interaction,
                security: $base->security,
            );
        }

        if (isset($settings['realtime']) && is_array($settings['realtime'])) {
            $realtime = $settings['realtime'];
            $base = new self(
                views: $base->views,
                features: $base->features,
                pagination: $base->pagination,
                appearance: $base->appearance,
                realtime: new RealtimeSettings(
                    enabled: (bool) ($realtime['enabled'] ?? $base->realtime->enabled),
                    refresh: (string) ($realtime['refresh'] ?? $base->realtime->refresh),
                    reconnect: (bool) ($realtime['reconnect'] ?? $base->realtime->reconnect),
                    dedupeLimit: (int) ($realtime['dedupe_limit'] ?? $base->realtime->dedupeLimit),
                    coalesceMs: (int) ($realtime['coalesce_ms'] ?? $base->realtime->coalesceMs),
                ),
                interaction: $base->interaction,
                security: $base->security,
            );
        }

        if (isset($settings['interaction']) && is_array($settings['interaction'])) {
            $interaction = $settings['interaction'];
            $base = new self(
                views: $base->views,
                features: $base->features,
                pagination: $base->pagination,
                appearance: $base->appearance,
                realtime: $base->realtime,
                interaction: new InteractionSettings(
                    animations: (bool) ($interaction['animations'] ?? $base->interaction->animations),
                    reducedMotion: (bool) ($interaction['reduced_motion'] ?? $base->interaction->reducedMotion),
                    keyboardAlternative: (bool) ($interaction['keyboard_alternative'] ?? $base->interaction->keyboardAlternative),
                    confirmation: \Karnoweb\Kanban\Enums\ConfirmationMode::from((string) ($interaction['confirmation'] ?? $base->interaction->confirmation->value)),
                    transitionPolicy: (string) ($interaction['transition_policy'] ?? $base->interaction->transitionPolicy),
                    transitions: $interaction['transitions'] ?? $base->interaction->transitions,
                    confirmationRules: $interaction['confirmation_rules'] ?? $base->interaction->confirmationRules,
                ),
                security: $base->security,
            );
        }

        return $base;
    }
}

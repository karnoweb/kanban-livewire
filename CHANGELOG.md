# Changelog

## 13.2.0 — 2026-10-18

### Added

- Optional lazy card-detail sections with independent permissions, bounded pagination, safe links and server-registered custom views.
- Typed detail section/action contracts, stable retry operation identities and version handoff to domain handlers.
- Configurable desktop drawer width, full-screen mobile drawer, fixed header/actions and background scroll locking.
- Jalali date presentation through `morilog/jalali`, with locale, timezone and Persian digit settings.
- Card image covers, typed checklist/comment details, member overflow, archived-card deletion and configurable host create/edit pages.
- Consumer guides, CRM/Order/Ticket adapter examples, execution reports and Board/List/Drawer screenshots.

### Changed

- Refined Board/List cards, desktop toolbar, filters, icon actions and stage capacity counts while preserving package behavior hooks.
- Workbench header controls global theme and locale; RTL applies Persian text, font and date presentation.
- CRM demo enforces note-required transitions and disables direct card/stage creation.
- Optional detail contracts preserve the existing DataProvider and ActionHandler interfaces.

### Fixed

- When `theme_switch`, `direction_switch`, or `density_switch` is disabled, boot and preference actions follow board appearance settings instead of a stored user preference (so hosts can inherit the panel theme without a separate board theme).
- Revalidate detail visibility on each request and clear loaded sections when permission is revoked or a card disappears.
- Separate Workbench theme/locale selectors and avoid waiting on external assets in browser navigation tests.

## 13.1.0 — 2026-10-17

### Changed — breaking CSS integration

- Use DaisyUI 5.7.50 components with Tailwind CSS 4.3.3 and default plugin configuration.
- Add a host-global CSS import path that inherits semantic theme tokens; retain a separate standalone bundle.
- Group secondary toolbar controls, increase control readability, contain dialog keyboard focus, and reserve space for Workbench settings.
- Document the complete selector inventory and a critical design audit with remaining workflow/accessibility findings.

### Added — enterprise model integration

- Optional model definitions and explicit per-board sources; existing factories/providers remain supported.
- Scoped Eloquent reads, authenticated keyset cursors, SQL counts/metrics and bounded eager loading.
- Transactional model actions, scoped operation recovery, numeric rank strategy, local rebalance, version/WIP enforcement and atomic/partial bulk.
- Compact presentation, scoped group selection, column collapse, bounded render windows, touch handles and drag auto-scroll.
- Runnable model demos and enterprise security/performance/browser acceptance evidence; see the enterprise status report for release limitations.

## 13.0.0

First implementation of the public board contract: registry, typed DTOs, Livewire board/list, allowlisted actions, realtime bridge and the KB-001..KB-044 suite.



Upgrade instructions and known limitations: [13.1.0 release notes](docs/releases/13.1.0.md).

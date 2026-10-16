# Changelog

## 14.0.0 — 2026-10-17

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



Upgrade instructions and known limitations: [14.0.0 release notes](docs/releases/14.0.0.md).

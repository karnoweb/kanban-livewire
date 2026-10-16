# Release checklist

> This table describes the original implementation. The optional enterprise model adapter has separate executed evidence in `ENTERPRISE-IMPLEMENTATION-STATUS-FA.md`, `ENTERPRISE-SCENARIO-RESULTS.json`, and guides 21–27. Local acceptance does not claim that remote CI, physical mobile devices, or an external Calendar package have been exercised.

| Requirement | Owner file | Test | Result |
| --- | --- | --- | --- |
| Board and list | `src/Livewire/Board.php` | KB-001, KB-002 | PHPUnit |
| Disable one view | `DisplaySettings` | KB-003 | PHPUnit |
| Two boards, same card id | `DomIdFactory` | KB-004 | PHPUnit |
| Page size 25 | `PaginationSettings` | KB-005, KB-006 | PHPUnit |
| Server filters | `QueryNormalizer` | KB-007, KB-008 | PHPUnit |
| Manual reorder guard | `HandlesMoves` | KB-009 | PHPUnit |
| 10k source, 250 loaded | `InMemoryProvider` | KB-010 | PHPUnit |
| Move and deny | `TransitionPolicy` | KB-011, KB-012 | PHPUnit |
| Confirmation | `MoveCoordinator` | KB-013..KB-015 | PHPUnit |
| Rollback and reorder | `ActionDispatcher` | KB-016..KB-018 | PHPUnit |
| Idempotency and unknown | `IdempotencyGuard` | KB-019, KB-020 | PHPUnit |
| Conflict and WIP | host handler contract | KB-021..KB-023 | PHPUnit |
| Card and stage CRUD | coordinators | KB-024..KB-031 | PHPUnit |
| Bulk partial and atomic | `BulkCoordinator` | KB-032, KB-033 | PHPUnit |
| Realtime | `RealtimeBridge` | KB-034..KB-037, KB-044 | PHPUnit |
| Tenant and XSS | scope + Blade escape | KB-038, KB-039 | PHPUnit |
| RTL, theme, keyboard, motion, listeners | views + JS | KB-040..KB-043 | PHPUnit |
| No migration in src | repository layout | review | required |
| Optional features still implemented | `FeatureSettings` | settings tests | required |

## 14.0.0 release verification

- Local PHP: 221 tests, 794 assertions passed.
- Local Edge browser: 13 tests passed, including realtime, touch, host token inheritance and modal keyboard focus.
- MariaDB/PostgreSQL adapter evidence is recorded in `ENTERPRISE-DATABASE-RESULTS.json`.
- Build, dependency audit and diff whitespace checks passed.
- Breaking CSS migration and open design findings are disclosed in `releases/14.0.0.md`.
- Remote CI status is tracked separately; local results do not imply remote CI has completed.
- Existing proprietary license is unchanged.

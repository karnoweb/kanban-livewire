# Implementation status

Last measured: 2026-10-15, PHP 8.4.22, Windows, Microsoft Edge (Chromium) via Playwright.

## Results

| Check | Command | Result |
| --- | --- | --- |
| PHP tests (unit, feature, scenarios, security, performance, browser-render, workbench) | `vendor/bin/phpunit --no-coverage` | OK (127 tests, 389 assertions) |
| Real browser + real Reverb, two users, two tenants | `npx playwright test` (servers: `testbench serve`, `testbench reverb:start`) | 8 passed |
| Two OS processes racing for the last strict-WIP slot on SQLite | `tests/Workbench/WorkbenchConcurrencyTest.php` | exactly one `success`, one `wip_limit` |
| Event delivered after DB commit | `WorkbenchDatabaseTest::test_move_persists_bumps_version_and_publishes_after_commit` | transaction level 0 at delivery |
| Realtime latency, alice drop to bob DOM, 20 samples | `docs/E2E-REALTIME-LATENCY.json` | p95 207 ms (local Reverb, SQLite) |

## Phases

- Phase 1 contracts and registry: PASS.
- Phase 2 board/list UI, pages, filters, virtualization window: PASS (server window; browser drag verified in Playwright).
- Phase 3 actions, confirmation, CRUD, archive, bulk, WIP: PASS (in-memory fixtures and the database-backed workbench handler).
- Phase 4 realtime, tenant isolation, idempotency, XSS: PASS (Reverb private channels, channel authorizer rejects a foreign scope hash in a real browser).
- Phase 5 packaging, docs, 44 scenarios, CI: PASS.

## Defects found by the real-browser run and fixed

- `kanban-board` alias was never registered: `Livewire\Board::class` resolved through the `Livewire` import alias to a non-existent class. Fixed with an explicit import; `ComponentRegistrationTest` guards it.
- JS used `component.$wire.method()`; `Livewire.find()` already returns the `$wire` proxy. Fixed in `sort-adapter.js` and `realtime-bridge.js`.
- JS was a stub. Implemented HTML5 drag/drop with before/after anchors and expected version, Echo private-channel subscription, offline/reconnect, and listener cleanup.
- Channel names contained `.`, which Laravel's channel pattern cannot match. Board keys are now sanitized to `[A-Za-z0-9_-]`.

## Not claimed

- Browsers other than Edge/Chromium were not run (Playwright could not download its bundled Chromium in this environment, so `channel: 'msedge'` is used).
- The latency figure is a local measurement, not the PRD's multi-user production environment.
- SQLite is the only database exercised. The write-lock-first pattern is also valid for MySQL/Postgres row locks but was not run there.
- On PHP 8.3 `transaction_mode=IMMEDIATE` is ignored by Laravel; the writer's first-statement write still serialises SQLite.
- License remains `proprietary` until the owner chooses one. No `1.0.0` tag was created.

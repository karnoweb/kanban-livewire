# Architecture decisions

## ADR-1 — Factory class, not a closure, is the board identity

Livewire serializes public state between requests. A closure stored there breaks on the next request and can capture the wrong tenant. `BoardRegistry` maps a stable key to a `BoardFactory` class. Each request resolves that class and calls `make(BoardContext)`.

## ADR-2 — Server scope is the only authority

`ContextFactory` drops any client-supplied scope key. `ScopeResolver` belongs to the host. The same card id may exist in two tenants; lookups are always scope plus board plus id.

## ADR-3 — wire:sort is not the drag owner

Livewire 4 `wire:sort` commits order through its own request and does not give this package a place to open confirmation, keep an anchor across an unloaded page, or suspend virtualization for the drag. Those are required before a write. The package therefore has one drag owner: `resources/js/sort-adapter.js`. It only calls `requestMove` with before/after card ids. Keyboard and menu moves use the same server command. A second sortable library is not loaded.

## ADR-4 — Default transition policy is deny

If the host does not publish an allow matrix, `TransitionPolicy` rejects cross-stage moves. Same-stage reorder is a no-op when `reorder_cards` is off, and it does not call the handler.

## ADR-5 — Unknown outcome is not success

After a timeout the component keeps the original `operationId` and asks `DataProvider::operationStatus`. It does not mint a new id. The host handler must store the first result for that id.

## ADR-6 — Realtime payload is minimal

`BroadcastEventFactory` emits schema version, event id, operation id, type, scope hash, board id, entity id, entity version and time. Titles, notes, attachment URLs and raw tenant keys are rejected by `RealtimeEventData::fromArray`.

## ADR-7 — No package database

There is no migration under `src`. Demo tables live only under `workbench/`.

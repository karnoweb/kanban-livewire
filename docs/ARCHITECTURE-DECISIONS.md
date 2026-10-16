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

## ADR-8 — Explicit model sources are an optional adapter

`sources(null)` is represented by an omitted builder call and preserves the existing provider path. `sources([])` selects an empty model source set. The model registry contains class descriptors only; each resolution rebuilds the executable definition on the server. Mixed models require a complete explicit stage map. Non-Eloquent sources use an explicit host provider, preserving the existing contracts.

## ADR-9 — Bounded keyset merge and binary text order

Model cursors use authenticated encryption and bind actor/scope/board/source selection/stage/query. Each source fetches at most page size plus one. The merge keeps a position per source. Text predicates and ordering use the same binary semantics on SQL and PHP so engine collation cannot silently skip records.

## ADR-10 — No hidden operation table

The default model operation store uses a persistent shared host cache. A pending marker precedes persistence, preventing a crashed operation from being blindly re-executed. Unknown commit outcomes remain unknown until the host reconciles them. Hosts can implement `Contracts\ModelOperationStore` on an existing domain store. No mandatory rank, version or operation table is introduced.

## ADR-11 — Scope-wide model broadcasts contain no row identity

Subscribers in one scope may have different row visibility. Model broadcasts therefore redact card/source/version and send only board invalidation after an allowed-source check. Each client refetches its own authorized data. Domain hooks receive the detailed server event after commit; broadcasting remains host-owned.

## ADR-12 — Group selection and reachable render windows

The first group presentation lazily selects one authorized group across the columns instead of loading all group rows. Render windows have explicit previous/next controls so loaded cards never disappear without a path back. Scope/actor/board preferences and component-local scroll state remain isolated. Compact presentation reuses the same component and data/action contracts.

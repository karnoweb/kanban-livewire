# Registered sources and board allowlists

Sources have three separate controls: a host explicitly registers a source key, a board explicitly allowlists keys, and the user may filter only within that board's allowlist. Adding `HasKanbanCards` to a model does not publish it.

## Register an Eloquent source

In the host service provider, register a stable key and model class:

```php
app(KanbanModelRegistry::class)->register('tickets', App\Models\Ticket::class);
```

The model must use `HasKanbanCards` and implement `kanbanDefinition(): KanbanModelDefinition`. Its `visibleQuery()` must apply tenant/owner scope in SQL. The registry keeps class names only; each request rebuilds the executable model definition.

## Allow sources on a board

```php
->sources(['tickets'])
```

For a mixed board, explicitly map every native status to a shared stage:

```php
->sources(['orders', 'tickets'])
->stageMap([
    'orders' => ['pending' => 'open', 'fulfilled' => 'closed'],
    'tickets' => ['new' => 'open', 'resolved' => 'closed'],
])
```

Mixed record identities are source-qualified (`tickets:12` and `orders:12` remain distinct). Incomplete mappings, unknown sources, and client attempts to add a source are rejected. Reads for cards, counts, details, search, archives, and writes use the same board allowlist and scoped model query.

## Non-Eloquent sources

For API or in-memory records, register a `DataProvider` source explicitly and attach a combined provider/handler to the board. The package does not guess a model class or query all registered sources. `[]` is an empty allowlist; it never expands access.

More detail: [multi-source boards](22-multi-source-boards.md), [security and tenant scope](13-multitenancy-security.md), and [Eloquent performance](23-eloquent-performance.md).

# Kanban package usage

This package renders a domain-owned board and list. The host application owns records, authorization, persistence, comments, checklists, attachments, and domain rules. No package migration is required for the model adapter.

## Install and register a board

Install with Composer, register the package provider through Laravel package discovery, and build a `BoardFactory` that returns `BoardBuilder`. Register each board key with `BoardRegistry` in the host service provider. Render it with `<livewire:kanban-board board-key="support.tickets" />`.

For a small or non-database board, keep the existing `DataProvider` and `ActionHandler` flow. For Eloquent records, use `HasKanbanCards`, register the source explicitly, and set `->sources([...])` on that board. See [the model guide](21-trait-eloquent-guide.md) and [multi-source guide](22-multi-source-boards.md).

```php
return (new BoardBuilder)
    ->key('support.tickets')
    ->board(new BoardData('support.tickets', 'Tickets', $context->scopeKey))
    ->sources(['tickets'])
    ->settings(['pagination' => ['page_size' => 25]])
    ->build();
```

The board factory is resolved for the current `BoardContext`; register only a factory class and rebuild the definition for each request. Do not put a model instance, query builder, actor, or closure into Livewire public state.

## Own the domain rules

The Eloquent adapter reads from each model's scoped `visibleQuery()` and writes through the model definition's explicit authorization and lifecycle hooks. Use a host `ActionHandler` when a transition must call an existing domain service. Creation, status changes, and destructive actions deny by default unless the model explicitly authorizes them.

For additional detail sections, register an optional `CardDetailsProvider` with `->detailsProvider(HostCardDetailsProvider::class)`. It receives a freshly scoped `CardData`; see [Drawer sections](DRAWER.md).

## Test and build

Run `composer test`, then `npm run build`. Browser tests use the separate Playwright configuration and an isolated Workbench database; see [test scenarios](TEST-SCENARIOS.md). Do not point browser tests at a host application's database.

More complete references: [builder and registry](03-builder-registry.md), [provider contract](05-data-provider.md), [handler actions](06-handler-actions.md), and [API reference](20-api-reference.md).

# Upgrade and compatibility notes

The board factory, `DataProvider`, `ActionHandler`, `BoardBuilder`, and existing `openDetails()` flow remain supported. New source and detail behavior is opt-in.

| Existing integration | Current path |
|---|---|
| Custom provider/handler board | Keep `provider()` and `handler()`; no Eloquent trait required. |
| Eloquent model adapter | Add `HasKanbanCards`, implement `kanbanDefinition()`, register its source, and opt each board in with `sources()`. |
| Mixed model board | Add `sources([...])` and a complete `stageMap`; source-qualified IDs prevent collisions. |
| Existing card details | Keep `DataProvider::card()` returning `CardData`; the base drawer continues to render. |
| Extra detail sections | Implement the separate optional `CardDetailsProvider` and register it with `detailsProvider()`. No method was added to `DataProvider`. |
| Sorting and pagination | Keep allowlisted fields. Existing offset cursors are not accepted by the model adapter; use the signed query-bound cursor returned in `PageResult`. |

The package does not add host-owned task, comment, checklist, attachment, or tenant migrations. Any host migration for rank/version columns remains optional and belongs to the host repository. Review [the root upgrade guide](../UPGRADE.md) and [release notes](releases/13.1.0.md) for package-version changes.

# Card details drawer and optional sections

The built-in detail view reads the current `CardData` and remains available to old providers that implement only `DataProvider`. Its properties, description, labels, checklist, comments, and attachments are rendered as escaped text and typed records. No comments, checklist, or attachment storage is added to the package.

## Add lazy sections

Implement `Karnoweb\Kanban\Contracts\CardDetailsProvider` and register the class on a board:

```php
->detailsProvider(App\Kanban\TicketDetails::class)
```

`sections()` returns lightweight `CardDetailSectionData` descriptors. Set `capabilities['canView']` to `true` only after checking the current actor and record; omitted permission is denied. Do not query full comment/activity history here. The section is fetched only after its Load button is used, through `loadSection(context, card, key, cursor, limit)`.

`CardDetailSectionData` supports ordered sections with a summary, ready/empty/unavailable state, scalar display items, a next cursor, capabilities, and named actions. The built-in renderer escapes each label/value/description and accepts no HTML. Use keys such as `subtasks`, `dependencies`, `checklist`, `comments`, `attachments`, `activity`, or `related`; custom keys follow the same contract.

`performAction()` receives a `CardDetailAction` only when that action was advertised by the freshly authorized descriptor. It must recheck permission, validate its payload, use the operation ID for idempotency, and return the package `ActionResult` so validation, forbidden, conflict, and unknown outcomes are not reported as success. Domain writes belong in the host service.

Use `views.drawer_width` (320–960 pixels, default 560) to control desktop width. Mobile uses the complete viewport. The header and action footer remain outside the independently scrolling content. `custom_actions = false` prevents direct section-action calls as well as hiding action buttons. The displayed page's operation ID and expected version are server-owned; an unknown result retains the operation ID for a safe domain-owned status check or retry.

For a host-specific form or presenter, register `->detailSectionView('items', 'app.kanban.order-items')` in trusted server code. Only registered template names execute; a section record cannot supply a Blade path. The partial receives `section` and `content`, and may call `performDetailAction(section, action, payload)`. Render the returned validation errors next to the host form fields. Custom form draft handling belongs to that host partial; the built-in generic sections are read-only until an explicitly advertised action is used.

## Security and compatibility

The component re-fetches the card and section descriptors on each request while the drawer is open. A card that is deleted or becomes inaccessible closes the drawer and clears loaded section data. Section visibility is checked again before loading, paging, or acting. Providers must still scope every query themselves and must not trust the supplied card as authorization proof.

The optional interface is separate from `DataProvider`, preserving existing integrations. A board with no details provider renders its normal base card details. Select `views.detail_surface = drawer` in board settings; Board and List actions call the same `openDetails()` method. Section pagination replaces the current bounded page; it does not retain an unbounded history in Livewire state.

Call `openDetails($cardId, 'drawer')` to open the drawer from a host action; Board and List use the same card identity and provider. See [the existing details implementation report](redesign/REPORT.md) for UI behavior and [the API reference](20-api-reference.md) for the base provider contracts.

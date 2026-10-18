# Configuration

The source of truth is `config/kanban.php`. Publish it with the `kanban-config` tag when the host needs to override defaults. Unknown model columns, invalid class contracts, and unsafe view names are rejected during definition validation.

| Area | Main options | Purpose |
|---|---|---|
| `pagination` | `page_size`, `max_page_size`, cursor lifetime | Bound each column and list request. The signed cursor is tied to board, scope, sources, filters, search, sort, and page size. |
| `limits` | `search_length`, `bulk_ids`, `metadata_bytes`, `id_length`, `title_length`, `detail_section_bytes`, `detail_section_page_size`, `detail_sections` | Bound client input and serialized detail data. |
| `model.eloquent` | default page size, cache settings, ordering strategy | Configure the optional Eloquent adapter. Visibility and write authorization still belong to each model definition. |
| `security` | scope requirements, public board behavior, operation limits | Control request-level authorization and defensive limits. Supply a trusted `ScopeResolver` for the host identity. |
| `realtime` | enabled, channel and throttling settings | Enable scoped refresh notifications; a host still configures its broadcasting driver and authorization. |
| `assets` | publish tag and prefixed path | Control package CSS/JavaScript publishing. |
| `telemetry` | `enabled` | Opt into package telemetry hooks; disabled by default. |

Appearance and interaction settings are usually board-specific through `BoardBuilder::settings()`: board/list availability, column width, density, direction, theme, page size, filters, transitions, WIP behavior, and enabled actions. Date presentation can select automatic, Jalali, or Gregorian formatting and a locale/timezone; see [Jalali dates](JALALI-DATES.md).

`views.detail_surface` selects `modal` or `drawer`. `views.drawer_width` defaults to 560 pixels and accepts 320–960; mobile drawers use the full viewport. The corresponding `appearance` aliases remain supported.

`->sources([])` explicitly means the board has no model sources. Omitting `sources()` keeps the existing provider/factory behavior. An empty source selection never means “all registered models.”

## Detail section limits

`limits.detail_sections` caps advertised sections, `limits.detail_section_page_size` caps each lazy page, and `limits.detail_section_bytes` caps serialized items/actions. Providers should return only display-ready scalar text and cursor values, never HTML or unbounded domain objects.

Do not raise limits to compensate for loading entire comment or attachment histories. Use independent paging and reauthorize each request.

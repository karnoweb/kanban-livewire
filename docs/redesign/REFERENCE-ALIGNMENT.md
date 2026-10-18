# Reference alignment

- Main toolbar: search, members, labels, additional filters (stage and priority); view switch, separate sorting, primary create action, direct density toggle, overflow actions.
- Default columns are 21rem; existing per-board choices remain configurable. Active CRM appearance was updated to 21rem with filters enabled.
- Table: selection, code, inline title/description, stage pill, priority, labels, progress, date, overlapping avatars, points, actions. Rows are 40px at desktop size, with horizontal scrolling where necessary.
- CRM prohibits direct card/stage creation, preserves its transition paths, and requires a nonempty string note on each stage change. Host-side validation is enforced independently of playground settings. Notes are persisted as comments, with bounded detail payloads.
- `BoardBuilder::cardPages(create: '/tasks/create?stage={stage}', edit: '/tasks/{id}/edit')` routes create/edit actions to host pages. Omit either URL to retain the package form. URLs are sanitized and identifiers encoded; host pages retain responsibility for their own authorization and forms.
- Custom providers can implement `FilterOptionsProvider` to supply scoped member/label options independent of currently loaded pages. Providers must also implement these filters in their data queries. Inline and Workbench providers support them.
- Card covers use the first image attachment, including generic uploads recognized by raster image filename or URL extension. Empty thumbnails fall back to the original URL.
- Workbench fixtures previously contained only comment counts and PDF/CSV files. `CardPresentationSeeder` now supplies clearly labeled sample comments, checklist items and a local preview image. It was applied to original demo records without resetting the database or replacing existing detail content. New user cards are untouched.

Validation: server tests cover mandatory CRM notes (including malformed payloads), forbidden direct creation, configured page redirects, combined/scoped filters, and persisted detail data. Browser tests cover real image loading, wide cards, compact rows, note dialog, details, existing interactions, mobile and RTL bounds.

Toolbar refinement: status/priority moved into More filters; create/density actions use accessible icon buttons. Desktop controls stay on one row. Assignees show up to three avatars and a bounded +N popup for the remainder. The demo host loads Inter and Vazirmatn using the Google Fonts stylesheet CDN.

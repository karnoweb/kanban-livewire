# Kanban design audit

Review date: 2026-10-17. Scope: the rendered Workbench `/tasks` page at 1440×1000 and 390×844, plus shared Blade, CSS and interaction code used by model, list and compact boards. `11-views-customization.md` is documentation, not a rendered page; it was reviewed as the integration contract. The host application's own source was not supplied.

This is a critical review of the **starting implementation**. Scores below are baseline judgments, not a WCAG certification or a claim about measured conversion. “Implemented” identifies changes in this working tree; remaining recommendations still require product decisions or additional validation. Demo data includes test titles; those titles are not treated as a visual defect.

## Findings

### 01 — Theme ownership conflicts
- **Severity:** High
- **Location:** root `.kb`, light/dark/system palettes; customization guide §2.
- **Problem:** local hexadecimal colors override the host's theme, and a second palette must be maintained for dark mode. The guide encourages another independent palette.
- **Why it matters:** embedding the board produces mismatched surfaces, incorrect brand colors and inconsistent selected/error states.
- **Recommendation:** inherit DaisyUI semantic color pairs and public radius tokens; import structural CSS into the host build. Keep the standalone bundle separate.
- **Principle violated:** consistency and standards; single source of truth.
- **Status:** Implemented. `system` now inherits the host; explicit light/dark still require those host themes.

### 02 — Toolbar competes with the task
- **Severity:** High
- **Location:** search/view/filter/sort/theme/direction/density/create/archive toolbar.
- **Problem:** unrelated commands occupy several rows with similar emphasis. The user must scan presentation settings to reach task actions.
- **Why it matters:** decision cost rises, especially when returning to frequent actions.
- **Recommendation:** keep search, view and creation visible; disclose filters/sorting and appearance separately. Add active-filter count in a subsequent iteration.
- **Principle violated:** Hick's Law; visual hierarchy; progressive disclosure.
- **Status:** Disclosure implemented; active-filter count remains open.

### 03 — Mobile chrome consumes the working area
- **Severity:** High
- **Location:** 390×844 page header, example banner and toolbar.
- **Problem:** baseline first column begins around y=489; roughly 58% of the viewport is spent before work content begins.
- **Why it matters:** users see few actionable cards and repeatedly scroll two axes.
- **Recommendation:** collapse secondary controls, remove demo explanation from the narrow layout, constrain panels and preserve vertical card space.
- **Principle violated:** content prioritization; responsive design.
- **Status:** Implemented. Narrow toolbar measured 206px after the change; actual height varies with translations and enabled features.

### 04 — Floating playground obscures content
- **Severity:** High
- **Location:** bottom-right settings launcher over the board.
- **Problem:** the fixed launcher covers card content and can intercept pointer access to controls near the bottom.
- **Why it matters:** a visible action may be impossible to activate reliably.
- **Recommendation:** reserve a layout row for the launcher; keep only its expanded settings panel floating.
- **Principle violated:** Fitts's Law; error prevention; focus not obscured.
- **Status:** Implemented in Workbench.

### 05 — Modal behavior is incomplete
- **Severity:** High
- **Location:** details modal/drawer and confirmation surface; `focus-manager.js`.
- **Problem:** baseline helper focuses one control but is never attached to these surfaces; it neither traps Tab nor makes the background unavailable.
- **Why it matters:** keyboard and screen-reader users can act behind a surface declared modal and lose their place.
- **Recommendation:** initialize on insertion, loop Tab/Shift+Tab, make outside branches inert, restore focus and support Escape.
- **Principle violated:** accessibility; predictable focus; Nielsen user control.
- **Status:** Implemented with shared surface lifecycle; requires broader assistive-technology testing.

### 06 — Dialogs lack accessible names
- **Severity:** High
- **Location:** details modal/drawer and confirmation; floating creation forms and archive.
- **Problem:** modal declarations have no programmatic name; several floating surfaces lack dialog semantics entirely.
- **Why it matters:** screen readers cannot announce the current task or distinguish it from the background.
- **Recommendation:** provide translated names and dialog semantics for every floating surface; use the selected card title where appropriate in a future refinement.
- **Principle violated:** WCAG name, role and value.
- **Status:** Translated names/roles implemented.

### 07 — Small menu and dismiss targets
- **Severity:** High
- **Location:** card ellipsis, notice dismiss, toolbar chips and touch drag handle.
- **Problem:** baseline ellipsis uses 15px text with 1px vertical padding; touch drag handle is 32px. Several controls are tightly adjacent.
- **Why it matters:** accidental activation increases for touch and users with motor impairments. Target size must be evaluated with spacing exceptions, not text size alone.
- **Recommendation:** adopt at least 44px touch controls, 40px desktop controls and visible focus. Preserve adequate separation around checkboxes.
- **Principle violated:** Fitts's Law; WCAG 2.5.8 target size.
- **Status:** Main controls improved; a complete target/spacing audit across custom host views remains necessary.

### 08 — Metadata is too small
- **Severity:** High
- **Location:** badges/stat pills at 10.5px, avatar initials at 9.5px, column lock at 10px.
- **Problem:** essential priority, date and work status information is rendered at decorative-text sizes.
- **Why it matters:** scanning and zoom use become harder, particularly on high-density mobile displays.
- **Recommendation:** raise normal metadata to at least 13px; avoid shrinking semantic content merely to fit more badges. Move secondary details into the detail surface.
- **Principle violated:** readability; visual hierarchy.
- **Status:** Shared metadata increased; avatar initials still need identity presentation redesign.

### 09 — Status color contrast is unreliable
- **Severity:** High
- **Location:** baseline warning/information pills and muted indicators.
- **Problem:** source palette uses warning `#d97706` on `#fef3c7` (about 2.8:1) and information `#0284c7` on `#e0f2fe` (about 3.5:1), below 4.5:1 for normal text. Exact composited colors vary by surface.
- **Why it matters:** priority and status can be unreadable even when body copy has adequate contrast.
- **Recommendation:** use semantic foreground/background pairs; measure every host theme and custom stage color. Do not assume a component library certifies arbitrary tokens.
- **Principle violated:** WCAG 1.4.3 contrast.
- **Status:** Semantic pairs applied to status pills; custom host palette validation remains open.

### 10 — Creation requires an internal stage identifier
- **Severity:** High
- **Location:** card creation form, status field.
- **Problem:** users enter a free-text stage ID rather than choose a visible stage name. Editing makes this field read-only without explaining why.
- **Why it matters:** users must remember internal vocabulary and can submit invalid destinations.
- **Recommendation:** replace with a labeled stage selector populated from allowed stages; explain editing/movement constraints beside the field.
- **Principle violated:** recognition rather than recall; error prevention.
- **Status:** Open; requires provider/permission-aware form behavior.

### 11 — Validation is detached from fields
- **Severity:** High
- **Location:** card/stage form error loops after all fields.
- **Problem:** errors are displayed as a block, without `aria-describedby` or `aria-invalid` associations.
- **Why it matters:** users cannot reliably identify which input failed, especially with screen readers.
- **Recommendation:** render errors beside each field, associate stable IDs, focus the first invalid input and summarize errors for long forms.
- **Principle violated:** WCAG error identification; Nielsen recovery.
- **Status:** Open.

### 12 — Menu clipping remains possible
- **Severity:** High
- **Location:** absolutely positioned card menu inside independently scrolling columns.
- **Problem:** the menu opens below the card within a clipping scroll container; z-index cannot escape clipping boundaries.
- **Why it matters:** actions on cards near an edge can disappear or require awkward scrolling.
- **Recommendation:** use an anchored top-layer popover with viewport collision handling and explicit focus return; test first/last cards in every scroll position.
- **Principle violated:** visibility; operability; error prevention.
- **Status:** Open. Toolbar disclosures are separately constrained; card menu collision handling is not implemented.

### 13 — Viewport locking is fragile
- **Severity:** High
- **Location:** `.kb`, host shell, stage/list scroll containers, floating forms.
- **Problem:** correctness depends on every ancestor having a constrained height and `min-height:0`. Long dialog content originally overflows visibly.
- **Why it matters:** browser zoom, short windows and mobile keyboards can strand controls outside reachable space.
- **Recommendation:** retain the documented shell contract, make dialogs scrollable and test 200%/400% zoom plus actual software keyboards. Consider a page-scroll layout for narrow hosts.
- **Principle violated:** WCAG reflow; responsive resilience.
- **Status:** Dialog scrolling implemented; full zoom/device validation remains open.

### 14 — Inconsistent radii and component styling
- **Severity:** Medium
- **Location:** buttons, inputs, badges, menus, cards and dialogs.
- **Problem:** independent 4/5/6/7/8/9/10/12px radius values and bespoke controls create multiple visual systems.
- **Why it matters:** global product changes do not propagate predictably and maintenance introduces drift.
- **Recommendation:** use DaisyUI components and public radius tokens; reserve package CSS for layout and behavioral states.
- **Principle violated:** consistency; design-system reuse.
- **Status:** Component classes and token bridge implemented; package-specific state/layout rules remain.

### 15 — Spacing lacks a clear rhythm
- **Severity:** Medium
- **Location:** toolbar gaps, card metadata, column header and form fields.
- **Problem:** numerous 2/3/4/5/6/7/8/10/12px values make grouping depend on incidental wrapping.
- **Why it matters:** scanning patterns vary between equivalent blocks.
- **Recommendation:** establish a 4px-based spacing scale, separate groups with 12–16px and use compact mode as an explicit density variation.
- **Principle violated:** Gestalt proximity; consistency.
- **Status:** Major groups normalized; complete tokenization remains open.

### 16 — Column header alignment changes per column
- **Severity:** Medium
- **Location:** baseline Doing header versus To do/Done headers.
- **Problem:** count, WIP and collapse controls wrap differently according to label length.
- **Why it matters:** users cannot scan status metrics across a common line.
- **Recommendation:** give title and controls separate rows, with consistent wrapping and a concise collapse affordance.
- **Principle violated:** alignment; Gestalt similarity.
- **Status:** Consistent title/control rows implemented; collapse label remains verbose.

### 17 — Counts are ambiguous
- **Severity:** Medium
- **Location:** `5 / 5`, approximate count and WIP badges in stage headers.
- **Problem:** loaded/total and occupancy/limit use similar fraction forms without visible explanation.
- **Why it matters:** users may interpret loaded records as completed work or assume approximate counts are exact.
- **Recommendation:** label “5 loaded · 20 total” and “1 of 3 capacity”; explain unknown/approximate totals in accessible text.
- **Principle violated:** match with the real world; information scent.
- **Status:** Open.

### 18 — Excess card decoration competes with title
- **Severity:** Medium
- **Location:** badge row, priority edge, progress, attachments, six metadata pills and avatars.
- **Problem:** too many small color blocks share emphasis, while the title has limited horizontal room.
- **Why it matters:** users must decode every card instead of quickly comparing work items.
- **Recommendation:** define a default card information budget: title, owner, due/priority; disclose attachments and secondary statistics on demand.
- **Principle violated:** visual hierarchy; signal-to-noise ratio.
- **Status:** Typography improved; content budget remains a product decision.

### 19 — Empty state lacks context
- **Severity:** Medium
- **Location:** empty column body and filtered results.
- **Problem:** “No cards in this stage” does not distinguish an empty stage from a filter excluding every card.
- **Why it matters:** users can infer lost data or create duplicate work.
- **Recommendation:** show filter-aware empty copy and a clear reset action; tailor creation prompts to permissions.
- **Principle violated:** visibility of system status; recovery.
- **Status:** Open.

### 20 — Loading and repeated submission feedback
- **Severity:** Medium
- **Location:** save/create actions, filtering, load more and archive restore.
- **Problem:** shared forms do not consistently show operation-specific pending feedback or disable the submitting action.
- **Why it matters:** slow requests look unresponsive and invite duplicate actions.
- **Recommendation:** add targeted `wire:loading` indicators, disable only the active mutation and preserve layout while pending.
- **Principle violated:** visibility of system status; error prevention.
- **Status:** Open; server concurrency safeguards do not replace UI feedback.

### 21 — Emoji-based iconography is inconsistent
- **Severity:** Medium
- **Location:** due date, comments, checklist and attachment metadata.
- **Problem:** emoji glyphs differ by operating system, color and baseline; raw counts lack descriptive text.
- **Why it matters:** density and meaning vary across devices and assistive technologies.
- **Recommendation:** use one SVG icon set, hide decorative icons from screen readers and add translated labels to each count.
- **Principle violated:** consistency; accessible naming.
- **Status:** Open.

### 22 — Dates and numbers are not localized consistently
- **Severity:** Medium
- **Location:** card due date and `pt` statistic; sort labels and stage color form label.
- **Problem:** raw timestamps, English abbreviations and labels coexist with Persian controls.
- **Why it matters:** users must translate conventions mentally and can misread dates.
- **Recommendation:** format dates/numbers through host locale and timezone, translate remaining labels and expose full absolute date accessibly.
- **Principle violated:** match with the real world; internationalization.
- **Status:** Open; new disclosure labels are translated.

### 23 — Horizontal board navigation has weak discoverability
- **Severity:** Medium
- **Location:** narrow board showing one column and a sliver of the next.
- **Problem:** hidden stages require horizontal movement while each column also scrolls vertically.
- **Why it matters:** users can miss stages or lose context during navigation.
- **Recommendation:** add an accessible stage jump control; consider one-stage-at-a-time mode with persistent stage position on mobile.
- **Principle violated:** navigation visibility; spatial orientation.
- **Status:** Open; column width now stays within the narrow viewport.

### 24 — Drag handle is invisible on desktop
- **Severity:** Medium
- **Location:** `.kb-drag` clipped to 1×1px on fine-pointer devices.
- **Problem:** drag affordance depends on users guessing the entire card can move; keyboard users may encounter a visually hidden interactive control.
- **Why it matters:** movement is hard to discover and focus can appear lost.
- **Recommendation:** reveal the handle on hover/focus and provide equivalent named move actions; avoid invisible tab stops.
- **Principle violated:** affordance; keyboard accessibility.
- **Status:** Open for desktop; coarse-pointer handle size increased.

### 25 — Theme choice was incompatible with host themes
- **Severity:** Medium
- **Location:** `data-theme="system"` on board and redundant theme-provider node.
- **Problem:** “system” is not a DaisyUI theme name; local dark/light palettes ignore a branded host theme.
- **Why it matters:** host customization produces unexpected results and multiple boards can diverge from the product.
- **Recommendation:** omit local `data-theme` for inherited mode; preserve the preference separately and document explicit-theme requirements.
- **Principle violated:** consistency and standards; predictable configuration.
- **Status:** Implemented using `data-kb-theme` for the preference.

### 26 — Destructive actions lack differentiated recovery
- **Severity:** Medium
- **Location:** archive actions in the same menu as details and movement.
- **Problem:** archive sits among frequent navigation commands; restoration requires finding a separate archive browser.
- **Why it matters:** accidental actions interrupt work and create uncertainty about data loss.
- **Recommendation:** separate destructive commands visually and offer an immediate undo notice when business rules permit.
- **Principle violated:** Nielsen error prevention and user control.
- **Status:** Error tone retained; undo flow remains open.

### 27 — Empty archive and stage form exit
- **Severity:** Medium
- **Location:** archive browser with zero items; stage creation form.
- **Problem:** empty archive has no explanation, and stage form originally has no visible Cancel action.
- **Why it matters:** users lack a clear conclusion or exit path.
- **Recommendation:** add a semantic empty state and explicit cancellation, including Escape.
- **Principle violated:** user control; closure.
- **Status:** Stage Cancel/Escape implemented; archive empty copy remains open.

### 28 — Attachment titles and card descriptions truncate
- **Severity:** Low
- **Location:** 11rem badge/attachment widths and line-clamped descriptions.
- **Problem:** important differentiating text can disappear without a consistent way to reveal it.
- **Why it matters:** similar items become harder to distinguish.
- **Recommendation:** preserve full text in details, allow wrapping where possible and provide keyboard-accessible expansion rather than hover-only title attributes.
- **Principle violated:** information availability; accessibility.
- **Status:** Open.

### 29 — Demo identity/navigation is not production navigation
- **Severity:** Medium
- **Location:** user-switch links, board navigation and mixed-direction explanatory banner.
- **Problem:** session switching is visually adjacent to navigation, and the page does not clearly separate example configuration from application work.
- **Why it matters:** demo-only behavior could be copied into production and undermine trust or confuse account identity.
- **Recommendation:** mark the environment as a demo and keep user impersonation out of production navigation; provide clear active/current-page semantics.
- **Principle violated:** error prevention; trust; information architecture.
- **Status:** Shell colors and narrow layout changed; production navigation belongs to the host.

### 30 — Typography is imposed by the package
- **Severity:** Medium
- **Location:** `.kb` root font stack and compact mode.
- **Problem:** the root overrides host typeface and uses independent fractional font sizes.
- **Why it matters:** embedded UI loses brand continuity and multilingual font selection can diverge.
- **Recommendation:** inherit host font family, use relative sizes and keep meaningful text legible in compact mode.
- **Principle violated:** consistency; readability.
- **Status:** Implemented for shared root/body/metadata. Host font loading remains the host's responsibility.

### 31 — List roles describe a table without cells
- **Severity:** High
- **Location:** list root `role="table"` and card articles `role="row"`.
- **Problem:** row content is composed of generic divs and headings, without table cell/header roles or a meaningful column model.
- **Why it matters:** screen readers receive a misleading table structure and cannot use normal cell navigation predictably.
- **Recommendation:** use list/list-item semantics for the current card layout, or implement a real table with headers and cells. Keep pagination outside the semantic item collection.
- **Principle violated:** WCAG information and relationships; semantic HTML.
- **Status:** Open; the current visual migration preserves the existing list interaction contract.

## Top 10 priorities

1. **05** Complete modal keyboard and background behavior.
2. **09** Validate status/text contrast for every deployed host theme.
3. **03** Recover mobile working space.
4. **04** Remove content-obscuring launcher placement.
5. **10** Replace internal stage-ID entry with allowed stage choices.
6. **11** Associate form errors with fields.
7. **12** Prevent action-menu clipping at viewport/scroll edges.
8. **07** Make pointer/touch targets reliable.
9. **01/25** Make theme ownership and CSS integration predictable.
10. **02** Reduce toolbar decision load.

No Critical issue is assigned from the evidence reviewed. High issues are release concerns; a screenshot alone cannot justify claims of catastrophic task failure for every user.

## Quick wins

- Inherit DaisyUI token pairs and host fonts; remove independent palette definitions. Implemented.
- Group secondary controls; retain one visually dominant Create card action. Implemented.
- Reserve space for the demo settings launcher. Implemented.
- Add dialog names, Cancel/Escape and focus containment. Implemented.
- Increase metadata and action target sizes. Implemented for shared components.
- Next: connect validation messages, add loading labels, translate raw labels, explain counts and empty archives.

## Long-term improvements

- Build permission-aware stage selection, undo/recovery and contextual bulk workflows.
- Replace clipped menus with accessible anchored popovers and test scroll/zoom collision cases.
- Establish a semantic spacing/type/icon system across cards, compact mode and host-supplied views.
- Test real touch devices, software keyboards, 200%/400% zoom, high-contrast settings and screen readers in both directions.
- Run task-based usability studies for finding work, moving cards, resolving conflicts and restoring archived work. Measure completion/error rates before changing the information budget.
- Add theme-specific contrast checks and accessibility regression coverage to the host's deployment checks. Component defaults cannot validate custom host colors.

## Baseline scores

| Dimension | Score /10 | Reason |
| --- | ---: | --- |
| Visual design | 4 | Dense metadata, competing emphasis, independent palettes and microtypography. |
| UX | 4 | Toolbar overload, internal-ID forms, ambiguous counts and recovery gaps. |
| Accessibility | 3 | Incomplete modality, small controls, contrast and validation associations. |
| Consistency | 4 | Competing token systems, radii, fonts and icon conventions. |
| Mobile readiness | 3 | Excess chrome, nested scrolling and obscured content. |
| Conversion optimization | 4 | Proxy for primary-task discoverability; no funnel or conversion data supplied. |
| Overall | 3.7 | Arithmetic mean; not a production-readiness certification. |

Do not promote these to post-change scores without completing the open findings and testing with representative users. Visual modernization alone does not resolve the remaining workflow issues.

## Integration references

The verified current release is [DaisyUI 5.7.50](https://daisyui.com/docs/install/). Integration follows its [semantic colors](https://daisyui.com/docs/colors/) and [host themes](https://daisyui.com/docs/themes/), using default plugin configuration. See [global CSS and selector contract](11-views-customization.md) for the actual host setup and standalone alternative.

## Verification record

- PHP suite: 221 tests, 794 assertions passed.
- Browser suite: 13 scenarios passed in Microsoft Edge, including realtime, touch movement, window navigation, inherited host colors/radii and modal focus containment/return.
- Production and development dependency audit: no reported vulnerabilities after selecting the compiler API rather than the CLI watch dependency chain.
- These checks do not constitute full WCAG conformance testing. Open findings above remain release-review work.

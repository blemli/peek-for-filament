# Changelog

All notable changes to `peek-for-filament` will be documented in this file.

## v0.1.0 - 2026-08-08

Initial release.

- Zero-conf hover previews for relationship table columns and infolist entries (BelongsTo, MorphTo, dot-notation chains) — Filament `^4.0|^5.0`
- Peek on any anchor linking to a resource record (global search, breadcrumbs, awcodes/recently menu, activity-log timelines) via a server-side record-URL resolver locked to same-panel view/edit routes
- Adaptive slot card: resource-first content (record title, model label, open link) with attribute-name heuristics (image/avatar, title, email, description, address, phone, website, color, money, author) and custom views (`peek.{model}` convention, `Peekable` contract, `->peekView()`)
- Authorization: dedicated `peek` policy ability wins over `view`; denials are silent and indistinguishable from empty results
- Opt-outs: `->noPeeking()` globally / per resource (`NoPeeking` marker) / per column or entry; `->peekable()` to force-enable
- Opt-in nesting (`->peekDepth()`), opt-in throttled activity log (`->logActivity()`, suggests spatie/laravel-activitylog), mobile long-press, de+en translations, `peek:install` / `peek:uninstall`

Browser QA (Filament v5 host, Chrome engine): relationship + MorphTo hovers ✓, infolist entries on view pages ✓, global-search anchor with clustered route ✓, cell token beats row-click anchor ✓, activity log written + throttled ✓, SPA navigation and Escape close ✓, dark + light mode ✓, zero console errors, all requests 200. Recently/Timeline integrations covered by unit tests (packages absent in QA host); nesting depth clamp covered by unit tests.

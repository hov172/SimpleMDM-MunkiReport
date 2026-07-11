# Changelog

All notable changes to the SimpleMDM MunkiReport module are documented here.

This module is in beta (see README). Versions before 2.0 may include schema
or route changes without a deprecation period.

---

## [1.3.0] — 2026-07-11
### Added
- MCP findings browser page `module/simplemdm/findings` — the full-set companion to the MCP Findings dashboard widget, reachable directly, via the widget's "+N more" links, and via its truncation-note "Open findings browser" link. Filters (`status`, `severity`, `category`, `source`, `finding_type`, comma-separated), pagination (50 rows/page), CSV/JSON export carrying the active filters, and deep-link support (`?status=&severity=&category=&finding_type=&source=`). Bulk Acknowledge/Resolve/Ignore/Suppress actions are global-admin-only.
- MCP findings section on the standalone device page (`module/simplemdm/device/{serial}`): severity badges, a `<details>` disclosure for the raw pushed `data`, and admin lifecycle action buttons (Acknowledge/Resolve/Ignore/Suppress); the section is hidden entirely when a device has no findings.
- Five new MCP findings dashboard widgets: `simplemdm_mcp_severity` (severity donut), `simplemdm_mcp_source` (source donut, top 8 + other), `simplemdm_mcp_critical` (open danger-severity findings list), `simplemdm_mcp_timeline` (30-day New/Resolved line chart), and `simplemdm_mcp_top_devices` (ranked per-device risk list). All five are addable/removable via the existing Widget Visibility settings.
- `get_mcp_finding_timeline?days=N` route (token-readable, `days` defaults to 30; values below 1 fall back to 30 and values above 90 are capped at 90) returning daily New/Resolved counts for the last N days, backing the `simplemdm_mcp_timeline` widget.
- `top_devices` field on `get_mcp_finding_stats` — up to 10 devices ranked by a 3/2/1-weighted (danger/warning/info) open-finding risk score, backing the `simplemdm_mcp_top_devices` widget.
- `finding_type` filter (comma-separated, case-sensitive exact match) on `get_mcp_findings` and `get_mcp_finding_stats`, matching the existing `category` filter's semantics.
- Two admin settings gating an opt-in, deduplicated fleet findings summary event (PRD section 13): `mcp_findings_event_enabled` (default `0`) and `mcp_findings_event_warning_threshold` (default `1`, minimum `1`). When enabled, ingest and admin-action call sites best-effort write a single event under module key `simplemdm_mcp_findings_summary`, anchored to the worst-affected device (highest danger count, then warning count, then total active findings, then lowest serial); existing installs see no Events UI change without opting in.

### Changed
- The four MCP finding admin-action routes (`acknowledge_mcp_finding`, `resolve_mcp_finding`, `ignore_mcp_finding`, `suppress_mcp_finding`) now also accept a global-admin MunkiReport session, in addition to the existing sync-token auth, so the new findings browser page and device-page action buttons can call them from a logged-in browser session without exposing the sync token to page JavaScript.

---

## [1.2.1] — 2026-07-11
### Changed
- MCP Findings dashboard widget scales to auto-publish volume (200+ findings from SimpleMDM-MCP v0.34.0's middleware): each category section now sub-groups findings by `finding_type` with a per-type count, renders at most 25 rows per type with a "+N more not shown" note pointing at `export_mcp_findings`/`get_mcp_findings`, and fetches up to 500 findings (the server cap; was 100 — which previously hid findings entirely, e.g. 2 `info` findings counted in the totals badges but unreachable in the list). When the fetch is still truncated, category headers show the true total from `get_mcp_finding_stats` as a separate "N total" badge alongside the per-severity badges (which reflect fetched rows only).

---

## [1.2.0] — 2026-07-11
### Added
- Documentation for the automated push sources arriving from SimpleMDM-MCP v0.34.0's findings auto-publish middleware: `ingest_mcp_findings` now also receives machine-triggered pushes under per-tool source namespaces — `mcp_auto_<tool>` (compliance/health-check and allowlisted inventory reads), `mcp_auto_action_<tool>` (action-tool failures, category `Action Failure`, severity `danger`), and `sofa_audit` (fleet-audit `--publish`) — each with `replace: true` scoped to its own source. No module code changes were needed; the existing caps and validation (2000 findings/push, 2 MB payload, `^[a-z0-9_\-]{1,64}$` source slug, 128-char `scan_id`/`category`) already accommodate them. Documented in `docs/API_REFERENCE.md` §11, README, `docs/DEVELOPER_GUIDE.md`, and `docs/SECURITY.md` (new write-path entry, auth-matrix rows, and monitoring guidance).
- Three read-only findings analytics routes: `get_mcp_finding_stats` (severity/status/category/source count breakdowns), `export_mcp_findings` (CSV/JSON bulk export, 10,000-row cap), `get_mcp_scan_status` (per-source last-scan summary). All three are token-readable via `X-SIMPLEMDM-API-KEY`, the same mechanism `get_mcp_findings` already uses — no changes needed for existing client apps.
- Three admin settings for MCP findings: `mcp_findings_enabled` (disable ingest/read/admin-action routes), `mcp_findings_metadata_max_bytes` (configurable `data` field truncation cap, now defaulting to 65536 instead of a hardcoded 4096, with a 1024-byte minimum floor), and `mcp_findings_auto_resolve` (global kill-switch overriding the per-request `replace` flag's auto-resolve behavior). Managed via the existing `save_config`/`get_config` routes and a new "MCP Findings Settings" panel in the admin UI.
- Four admin action routes — `acknowledge_mcp_finding`, `resolve_mcp_finding`, `ignore_mcp_finding`, `suppress_mcp_finding` — to manually change a finding's status by id (single or batch), independent of the automatic ingest lifecycle. Same sync-token auth as the existing ingest/read routes. `suppress_mcp_finding` only changes the named finding's status; it does not create a persistent suppression rule for future findings.
- PHPUnit test suite (`tests/`, `phpunit.xml`, `composer.json` `require-dev`) covering finding normalization, upsert/dedup/reopen/auto-resolve behavior, and status-update parsing, run against an in-memory SQLite database with the module's real migrations applied. See `docs/TESTING.md` for how to run it.

### Changed
- Findings gain an optional `category` field (e.g. `FileVault`, `Compliance`). The dedup fingerprint now includes `category` alongside `source`/`serial_number`/`finding_type`, matching the original PRD intent — two findings that differ only by category are now distinct findings rather than colliding into one. A finding pushed without `category` continues to dedupe exactly as before this change (hashes against an empty category, both before and after the migration). `get_mcp_findings` gains a `category` filter (comma-separated, case-sensitive exact match).
- `ingest_mcp_findings` now upserts findings by a deterministic `(source, serial_number, finding_type, category)` fingerprint instead of deleting and replacing all findings for a source on every push. Findings persist `status`, `occurrence_count`, `first_seen_at`, `last_seen_at`, and `resolved_at`. A complete scan (`replace: true`, the default) auto-resolves findings from that source that were not present in the push; a resolved finding reopens if it reappears later.
- `get_mcp_findings` gains `status`, `since`, `offset`, and `scan_id` filters, and a new `status_totals` response field. Without an explicit `status` filter it now returns only active (`open`/`acknowledged`/`in_progress`) findings, matching what the dashboard widget always displayed.
- MCP Findings dashboard widget now groups findings by `category` into collapsible sections instead of showing a flat top-5 list. Each group shows per-severity count badges and expands by default only if it contains a `danger`-severity finding; others start collapsed. The widget now fetches up to 100 findings (was 5) and scrolls internally rather than growing the dashboard layout.

### Fixed
- Disabled the row `:hover` lift/shadow transition (`translateY(-1px)`) for rows inside scrollable lists (`.simplemdm-list-scroll .list-group-item`). On a stationary cursor with trackpad scrolling, each row was hovering/unhovering as it passed under the pointer, replaying that lift transition down the whole list.
- **Fixed a permanent layout-thrash feedback loop that shook scrollable widgets and broke widget controls in Safari.** `applyLayoutMode()` in the shared widget assets unconditionally dispatched a synthetic `window` `resize` event on every run, and the module's own `resize` listener re-scheduled `applyLayoutMode` on the next animation frame — so the dashboard re-ran full widget collection, chart resizing, and three scheduled grid layouts ~120 times per second, forever, from page load (measured 377 synthetic resize events in 3 seconds; 0 after the fix). Chrome recomputed identical positions so nothing visibly moved, but Safari's measurements oscillate while its overlay scrollbar/elastic bounce is active, making the widget being scrolled visibly shake — and because Safari only delivers a `click` when the element doesn't move between mousedown and mouseup, the constant relayout also made expand/collapse controls intermittently unresponsive there. The synthetic resize is now dispatched only when the layout/theme mode actually changed, with a re-entrancy flag so the module's own dispatch never re-triggers its own listener; genuine window resizes and widget drag-resize still relayout the grid as before.
- **Fixed inner widget lists not wheel-scrolling at all in Safari with phase-less scroll input.** Safari only drives sub-scrollers (`overflow: auto` boxes like the MCP Findings list and the Devices Table rows) from wheel input carrying trackpad gesture phases; input from plain mice, KVMs, remote-control sessions, or synthesized events dispatches wheel DOM events over the container but native scrolling moves nothing — the page scrolls fine, the inner box stays pinned at `scrollTop 0`. Verified by synthesizing native OS-level scroll events against a live Safari session; CSS-level counter-theories (`overflow: hidden` un-nesting, compositing-layer promotion, overflow re-registration, `overscroll-behavior` variants — note `overscroll-behavior: none` on a sub-scroller freezes Safari wheel-scrolling entirely and was itself reverted) were each tested the same way and disproven. Fix: both containers (`#simplemdm-mcp-findings-groups`, `.simplemdm-devices-table-scroll`) now handle vertical `wheel` events in JS and scroll themselves, with `preventDefault()` so engines with working native scroll don't double-scroll; at a scroll boundary the event is left alone so it chains to the page normally, and the hard clamp inherently prevents elastic-bounce shake inside the widget. Chrome behavior is unchanged in feel and Safari now scrolls 1:1 with input regardless of input device.

### Security
- `save_config` now requires a global-admin session; the sync-token alternative has been removed. No legitimate caller ever used that branch, but it previously let a non-global authenticated session that also held a valid sync token bypass the `authorized('global')` scope check inside `save_config()` and rewrite `client_reporter_secret` and other admin-only secrets/settings. A sync-token-only request with no session was never able to reach `save_config()` at all — the controller already blocked those before this fix.

---

## [1.1.0] — 2026-07-08

### SimpleMDM-MCP integration
- **MCP findings channel** — `ingest_mcp_findings` (sync-token POST) accepts computed findings (CVE exposure, audit deltas, stale/compliance detections) from the companion [SimpleMDM-MCP](https://github.com/hov172/SimpleMDM-MCP) server; `get_mcp_findings[/serial]?severity&source&limit` reads them back, with severity totals and a dashboard widget.
- **MCP Findings widget** shows the 5 most recent findings with severity badge, finding type, device serial (linked to the device page), message, source, and reported time; the pushed data JSON is available as a hover tooltip. Included in dashboard layout and widget-visibility settings.
- **`get_events` route** exposes SimpleMDM alert/regression events (13 built-in event types plus custom rules) for the MCP's alert tooling; dead webhook code removed in the same pass.
- Route map, API reference, and developer guide docs kept in sync as the MCP's tool surface grew (14 → 16 tools as of SimpleMDM-MCP v0.33.0).

### ReportSimpleMDM connectivity
- **Token-readable dashboard routes** — ten read-only routes (`get_sync_telemetry`, `get_compliance_stats`, `get_command_status_stats`, `get_assignment_group_stats`, `get_resource_type_stats`, `get_os_security_stats`, `get_supplemental_status`, `get_supplemental_overview_stats`, `get_supplemental_applecare_stats`, `get_device_resources/{serial}`) now accept the `X-SIMPLEMDM-API-KEY` sync token as an alternative to a MunkiReport session, so the [ReportSimpleMDM](https://github.com/hov172/ReportSimpleMDM) macOS/iOS app (and other headless clients) can read dashboard data without browser auth. Extended to the MCP's six additional read routes and to the direct-URL form of the sync/ingest routes; `get_config` is excluded from the vouch so token callers still receive masked secrets rather than the full stored config.
- **`get_device_resources` memory fix** — the connected-resource scan previously hydrated every `installed_app` row (250k+ on large fleets) before filtering, exhausting PHP's memory limit. It now matches only rows mentioning the target device's id/serial/udid in SQL.
- Full setup guide, curl verification, and troubleshooting added to the README's "Connect ReportSimpleMDM" section.

### SimpleMDM devices API & sync
- **Full devices API passthrough** with deep per-device subresource sync (profiles, installed apps, users).
- **In-module sync runner** — admin-controlled scheduled sync gating, a queued sync workflow with cron helpers, sync run history, and an in-module "Run Sync Now" path alongside the host/cron worker.
- **Action-secret enforcement** for device detail actions and subresource operations.
- **Companion Dockerfile** and hosted/VM runtime docs for running the Python sync worker without relying on the PHP host's environment.

### Supplemental enrichment & Client Reporter
- **Supplemental data aggregation** from other installed MunkiReport modules (FileVault, AppleCare, ManagedInstalls errors) with configurable per-source enable/disable and staleness thresholds.
- **Client Reporter add-on** — a hardened per-device fact-reporting path (nonce/timestamp/signature verification) with its own deployment guide, separate from the server-side sync worker.
- Assignment group + apps sync and widget; SQLite collation fixes for supplemental tables.

### Widgets & UI
- Unified dashboard/report widget UX (layout defaults, visibility settings, grid width handling, header alignment across themes) across the SimpleMDM Report, group-apps, and MCP Findings widgets.
- Legacy module pages modernized; admin menu registration fixed.

### Documentation
- Added `DEVELOPER_GUIDE.md`, `SECURITY.md`, `UPGRADE.md`, `API_REFERENCE.md`, `TESTING.md`, `CLIENT_REPORTER_ADDON.md`, and `CLIENT_REPORTER_DEPLOYMENT.md`.
- README restructured with a table of contents, hosted/VM and Docker setup paths, webhook runbook, and screenshots throughout.

---

## [1.0.0] — initial tagged release

Baseline SimpleMDM sync and reporting module for MunkiReport.

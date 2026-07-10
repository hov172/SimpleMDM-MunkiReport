# Changelog

All notable changes to the SimpleMDM MunkiReport module are documented here.

This module is in beta (see README). Versions before 2.0 may include schema
or route changes without a deprecation period.

---

## [Unreleased]
### Added
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
- Investigated a Safari-only elastic-bounce "shake" when scrolling the MCP Findings widget's findings list, and the SimpleMDM Devices Table widget's row list, past their top/bottom on trackpad. Two approaches that call `preventDefault()` on a gesture-related event to suppress the bounce — CSS `overscroll-behavior: contain`, and a `wheel` listener calling `preventDefault()` — were both tried and reverted after each one broke click-through on the affected widget's expand/collapse controls in Safari (though not in Chrome). Both widgets' scroll containers (`#simplemdm-mcp-findings-groups`, `.simplemdm-devices-table-scroll`) now have only a passive `scroll` listener that clamps `scrollTop` back in-bounds after the fact; this does not call `preventDefault()` so it should not trigger the same click-suppression, but it also does not fully eliminate the visible bounce — it corrects position after Safari's animation has already started rather than preventing it. Full suppression of Safari's native elastic bounce without breaking click-through remains unresolved.

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

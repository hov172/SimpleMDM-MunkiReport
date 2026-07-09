# Changelog

All notable changes to the SimpleMDM MunkiReport module are documented here.

This module is in beta (see README). Versions before 2.0 may include schema
or route changes without a deprecation period.

---

## [Unreleased]
### Added
- Three admin settings for MCP findings: `mcp_findings_enabled` (disable ingest/read/admin-action routes), `mcp_findings_metadata_max_bytes` (configurable `data` field truncation cap, now defaulting to 65536 instead of a hardcoded 4096, with a 1024-byte minimum floor), and `mcp_findings_auto_resolve` (global kill-switch overriding the per-request `replace` flag's auto-resolve behavior). Managed via the existing `save_config`/`get_config` routes and a new "MCP Findings Settings" panel in the admin UI.
- Four admin action routes — `acknowledge_mcp_finding`, `resolve_mcp_finding`, `ignore_mcp_finding`, `suppress_mcp_finding` — to manually change a finding's status by id (single or batch), independent of the automatic ingest lifecycle. Same sync-token auth as the existing ingest/read routes. `suppress_mcp_finding` only changes the named finding's status; it does not create a persistent suppression rule for future findings.

### Changed
- `ingest_mcp_findings` now upserts findings by a deterministic `(source, serial_number, finding_type)` fingerprint instead of deleting and replacing all findings for a source on every push. Findings persist `status`, `occurrence_count`, `first_seen_at`, `last_seen_at`, and `resolved_at`. A complete scan (`replace: true`, the default) auto-resolves findings from that source that were not present in the push; a resolved finding reopens if it reappears later.
- `get_mcp_findings` gains `status`, `since`, `offset`, and `scan_id` filters, and a new `status_totals` response field. Without an explicit `status` filter it now returns only active (`open`/`acknowledged`/`in_progress`) findings, matching what the dashboard widget always displayed.

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

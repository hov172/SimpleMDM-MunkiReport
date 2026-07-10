# SimpleMDM Developer Guide

This guide is for contributors who need to understand and modify the module safely.

Related background docs:

- `CLIENT_REPORTER_ADDON.md` summarizes the implemented client-reporter contract and the original Option A / Option B design rationale.

## Table of Contents

- [1) Purpose and Use Cases](#1-purpose-and-use-cases)
- [2) Key Objects (What, Why, How They Work)](#2-key-objects-what-why-how-they-work)
- [3) Module Layout](#3-module-layout)
- [4) High-Level Data Flow](#4-high-level-data-flow)
- [5) Entry Points and Responsibilities](#5-entry-points-and-responsibilities)
- [6) Database Model Map](#6-database-model-map)
- [7) UI and View Map](#7-ui-and-view-map)
- [8) Common Change Workflows](#8-common-change-workflows)
- [9) Security Boundaries](#9-security-boundaries)
- [10) Dev Checklist Before Commit](#10-dev-checklist-before-commit)
- [11) File-Level Quick Reference](#11-file-level-quick-reference)

## 1) Purpose and Use Cases

### Three Data Paths

When reasoning about this module, keep these three data paths separate:

- Core SimpleMDM sync
  - the external SimpleMDM service syncs into this MunkiReport module through `simplemdm_sync.py`
- Option A supplemental enrichment
  - this module reads data from other loaded MunkiReport modules and exposes it as supplemental context
- Option B client reporter ingestion
  - local client-side reporters post allowlisted facts directly into this module

These paths complement each other but do not replace each other:

- core sync remains authoritative for native SimpleMDM inventory and resource state
- Option A is the preferred enrichment path when another module already owns the data
- Option B is the fallback for narrow local-device facts that are not already available through core sync or Option A

Option A and Option B can operate simultaneously. The controller merges them as separate supplemental sources rather than forcing one path to replace the other.

Conflict guidance:

- if another loaded module already owns a fact, keep that module as the preferred source and use Option A
- use Option B only for endpoint-local facts or explicit drift-detection comparisons
- when both paths expose similar facts, preserve source labels so operators can see why values differ

### What this module does

- Syncs SimpleMDM device inventory into MunkiReport for centralized visibility.
- Syncs documented SimpleMDM resources (apps, profiles, scripts, enrollments, assignment groups, device groups, and related supported child objects) for reporting and drill-down.
- Exposes operational dashboards/widgets for compliance, security posture, command status, and sync health.
- Enriches SimpleMDM views with Option A supplemental local module data using explicit source labeling and summary-backed reporting.
- Provides per-device deep views (attributes, relationships, linked resources, synced subresources).
- Supports controlled mutating actions (restart/lock/wipe class actions) through authenticated passthrough routes.

### Why teams use it

- Replace context-switching between MDM and reporting tools for daily operations.
- Track fleet posture over time with snapshots/trends.
- Troubleshoot quickly from one place: "what is on this device, what is assigned to it, what failed?"
- Keep data fresh via scheduled sync plus optional webhook ingestion.

## 2) Key Objects (What, Why, How They Work)

### Dashboard Widgets

- What:
  - Reusable cards/charts shown on dashboard pages and in the SimpleMDM report.
- Why:
  - Fast fleet-level visibility (enrollment, DEP, FileVault, resource mix, compliance, sync health).
- How:
  - Registered in `provides.yml` under `widgets`.
  - Backed by controller JSON endpoints (stats/count/trend methods).
  - Rendered by files in `views/*_widget.php`.
  - Shared layout/interaction behavior comes from `views/simplemdm_widget_modern_assets.php`.

### SimpleMDM Report Page (`reports/simplemdm`)

- What:
  - A module-owned report page that renders a curated set of SimpleMDM widgets.
- Why:
  - Gives a stable "known-good" operations view independent of whichever widgets an admin places on default dashboards.
- How:
  - Route is registered in `provides.yml` under `reports`.
  - Main container view is `views/simplemdm_report.php`.
  - Uses same widget rendering stack as dashboard widgets.

### Dashboard Pages (MunkiReport dashboards)

- What:
  - User/admin-selected dashboard YAML pages that can include SimpleMDM widgets.
- Why:
  - Lets teams mix SimpleMDM with other MunkiReport modules on one operations board.
- How:
  - Widgets are added through normal dashboard widget configuration.
  - Module JS stores layout/order/collapse state per page in browser local storage.
  - Reset actions are used when layout state becomes stale after widget changes.

### Device Listing and Resource Listing

- What:
  - Tabular pages for searching/filtering devices and synced resources.
- Why:
  - High-volume exploration and triage (global filters, endpoint/type filters, quick drill-down).
- How:
  - Registered under `listings` in `provides.yml`.
  - Implemented in `views/simplemdm_listing.php` and `views/simplemdm_resources_listing.php`.
  - Data comes from controller listing endpoints.

### Device Detail Page (`module/simplemdm/device/{serial}`)

- What:
  - Single-device operational page with overview, attributes, relationships, connected resources, synced subresources, and action runner.
- Why:
  - Primary page for device-level troubleshooting and targeted operations.
- How:
  - Main view: `views/simplemdm_device.php`.
  - Reads from `simplemdm`, `simplemdm_resource`, `simplemdm_relationship_edge`, subresource-derived records, and supplemental local source tables.
  - Mutating actions flow through `api_devices` passthrough with global auth + action secret requirements.

### Supplemental Data Layer

- What:
  - Option A enrichment that reads local MunkiReport source tables without copying them into the main `simplemdm` device table.
- Why:
  - Add device-health, lifecycle, and software context where the SimpleMDM API alone is incomplete.
- How:
  - Built-in source definitions are allowlisted in `simplemdm_controller.php`.
  - Generic source discovery also scans other loaded module folders, inspects module PHP files for candidate table names, and verifies a usable join key before exposing them as generic supplemental sources.
  - Source definitions can still be extended or overridden with `supplemental_registry_json`.
  - Device pages use live source lookups through `get_supplemental_data/{serial}`.
  - Fleet-level filters and widgets use `simplemdm_supplemental_summary`.
  - Admin visibility comes from `get_supplemental_status` and `refresh_supplemental_summary`.
  - Admin opt-outs use `supplemental_disabled_sources_json`, which disables selected detected sources without uninstalling those modules.

### Client Reporter Layer

- What:
  - Option B ingestion path for allowlisted client-reported facts stored in module-owned tables.
- Why:
  - Capture local facts that the SimpleMDM API and other loaded modules do not already provide.
- How:
  - Facts are posted to `index?op=ingest_client_facts`.
  - Current values upsert into `simplemdm_client_fact`.
  - Optional history rows append into `simplemdm_client_fact_history`.
  - The resulting values render as the `Client Reporter` supplemental source on device views.
  - This path writes into the MunkiReport SimpleMDM module, not into the external SimpleMDM service.

### MCP Findings (Widget + Lifecycle)

- What:
  - A findings inbox for audit/compliance/health signals pushed by the companion
    SimpleMDM-MCP server, stored in `simplemdm_mcp_finding` and surfaced on the
    dashboard and via read/analytics/admin-action routes.
- Why:
  - Lets MCP-side analysis (stale devices, CVE exposure, audit deltas, compliance
    detections) show up in MunkiReport without duplicating that logic here.
- How:
  - Ingest: `op=ingest_mcp_findings` upserts by a deterministic
    `(source, serial_number, finding_type, category)` fingerprint
    (`simplemdm_mcp_finding_model::computeFingerprint()`) instead of
    delete-and-replace. A complete scan (`replace: true`, the default)
    auto-resolves findings from that source absent from the push; a resolved
    finding reopens if it reappears.
  - Normalization/validation of pushed findings happens in
    `simplemdm_mcp_finding_model::normalizeFinding()` (severity taxonomy,
    length caps, `data` truncated to `mcp_findings_metadata_max_bytes`).
  - Read: `get_mcp_findings[/serial]` supports `status`, `since`, `category`,
    `offset`, `scan_id` filters and returns `status_totals`; without an
    explicit `status` filter it returns only active
    (`open`/`acknowledged`/`in_progress`) findings.
  - Manual lifecycle transitions: `acknowledge_mcp_finding`,
    `resolve_mcp_finding`, `ignore_mcp_finding`, `suppress_mcp_finding` (single
    or batch by id), independent of the automatic ingest lifecycle.
    `suppress_mcp_finding` only changes the named finding's status — it is not
    a persistent suppression rule for future pushes.
  - Analytics/export: `get_mcp_finding_stats`, `export_mcp_findings` (CSV/JSON,
    10,000-row cap), `get_mcp_scan_status` (per-source last-scan summary).
  - Admin settings (via `save_config`/`get_config`, exposed in the "MCP
    Findings Settings" admin panel): `mcp_findings_enabled` (kill switch for
    ingest/read/admin-action routes), `mcp_findings_metadata_max_bytes`
    (default 65536, 1024-byte floor), `mcp_findings_auto_resolve` (global
    override for the per-request `replace` auto-resolve behavior).
  - Dashboard widget (`views/simplemdm_mcp_findings_widget.php`) groups
    findings by `category` into collapsible sections (danger-severity groups
    expand by default) — see the File-Level Quick Reference and
    `docs/TESTING.md` Section 9 for widget-specific QA steps.
  - Safari-specific scroll handling: Safari applies its own elastic bounce to
    `overflow: auto` elements on trackpad input, and this bounce is not fully
    suppressible without breaking other behavior. Two approaches were tried
    and reverted, in order:
    1. CSS `overscroll-behavior: contain` on `.simplemdm-list-scroll
       .list-group` — silently broke click events on the widget's
       expand/collapse controls in Safari (both the per-category toggle and
       the whole-widget minimize button).
    2. A `wheel` listener calling `preventDefault()` on boundary-exceeding
       deltas — same click-breaking symptom in Safari, even though it's a
       different mechanism than `overscroll-behavior`. The common factor
       across both: calling `preventDefault()` on a gesture-related event
       near the scroll container breaks Safari click-through nearby.
    Do not reintroduce either approach. The current state (both
    `#simplemdm-mcp-findings-groups` and `.simplemdm-devices-table-scroll` in
    `simplemdm_devices_table_widget.php`) is a passive `scroll` listener that
    clamps `scrollTop` back in-bounds after the fact — it never calls
    `preventDefault()`, so it should not break clicks, but it also does not
    fully prevent the visible bounce, only corrects position after Safari's
    animation has already started. Full suppression of the bounce without
    breaking click-through in Safari is an open problem.
  - Auth: ingest/read/analytics routes use sync-token (`X-SIMPLEMDM-API-KEY`)
    or session; admin-action routes use the same sync-token auth as
    ingest/read; only `save_config` (settings) requires a global-admin
    session — see Section 9 below.

### Admin Settings Page

- What:
  - Module configuration UI for API key, secrets, sync controls, scheduling, widget toggles, and manual script access.
- Why:
  - Keeps operational controls in one place without code edits.
- How:
  - Registered under `admin_pages` in `provides.yml`.
  - View: `views/simplemdm_admin.php`.
  - Persists settings to `simplemdm_config`.
  - Persists execution history to `simplemdm_sync_run`.
  - `Sync Status -> Queue Next Worker Run` queues a one-off run for the next worker pickup.
  - `In-Module Sync And Schedule -> Run Sync Now` executes an immediate one-off run when in-module execution is available.
  - `Enable Scheduled Sync` / `Disable Scheduled Sync` change module schedule state.
  - `Queue State`, `Last Queue Request`, and `Queue Pickup Time` are queue-only telemetry for the worker pickup path.
  - `Schedule Config` reflects whether scheduled sync is enabled in module settings.
  - `Recurring Sync Ready` reflects whether recurring sync is actually ready to run, including cron being installed.
  - `Last Run` reflects the latest completed sync of any kind.
  - `Last Run Source` distinguishes immediate in-module runs, queued admin requests, and scheduled runs.
  - `Last Completed Source`, `Last Completed Status`, and `Last Completed Time` in the `Sync Status` panel mirror the most recent completed run so queue telemetry and run history are not confused.
  - `Recent Runs` is backed by `simplemdm_sync_run`, which records queued, running, completed, and failed runs.
  - `Clear Run History` deletes recorded rows from `simplemdm_sync_run` and resets the last-completed UI state when no run is queued or active.
  - `Event Settings` exposes:
    - per-built-in event enable/disable toggles
    - a built-in stale threshold in hours
    - constrained custom event rules stored in `custom_event_rules_json`
  - Custom event rules are not arbitrary logic:
    - `Source Field` maps to a supported key from the synced device snapshot
    - `Trigger` is constrained by field type (`changed_to`, `became_disabled`, `older_than_hours`)
    - `Suffix` becomes `simplemdm_<suffix>` in the host `event.module`
    - `Suffix` is admin-defined; it is not read from the SimpleMDM API or from widget metadata
  - Queue creation uses a dedicated queued run row so a new queued request does not overwrite the source of the last completed run before pickup.
  - `simplemdm_sync.py` is still the real worker; recurring runs require cron to launch it.
  - Host/manual runs should use an explicit `--api-key` or `SIMPLEMDM_API_KEY`; they should not rely on an authenticated browser session to bootstrap secrets.
  - In-module action buttons use the same runner prerequisite checks as the schedule panel, and the controller re-validates those prerequisites server-side.
  - Optional in-module execution allows the UI to install/remove cron and run approved script actions for global admins.
  - Custom events are intentionally limited to supported fields and trigger types so the admin UI does not become a generic rules engine.

### Event Evaluation Data Flow

- Full sync writes SimpleMDM device data into `simplemdm`.
- Webhook ingestion can partially update the same device row when supported device attributes are present in the webhook payload.
- Before and after a relevant device write, the controller snapshots the device through `get_device_snapshot()`.
- `evaluate_device_regression_events()` handles built-in events.
- `evaluate_custom_device_events()` handles admin-defined custom rules from `custom_event_rules_json`.
- Custom rules only evaluate fields that were actually present in the incoming record path, which prevents unrelated partial webhook payloads from falsely triggering rules.

Practical implication for admins:
- the Custom Events UI does not invent data
- it reads already-synced SimpleMDM-backed fields
- when a rule uses `Changed To`, the `Target Value` must match the stored module value exactly, for example `unenrolled`

Duplicate rule behavior:
- `normalize_custom_event_rules()` rejects custom suffixes that collide with built-in event suffixes
- it also rejects duplicate custom suffixes inside the custom rule set
- it does not try to detect semantic duplicates
- this is deliberate so admins can create multiple event slots for the same underlying condition with different messages, severities, thresholds, or team-specific routing

Built-in event breakdown:
- `simplemdm_action`
  - source: `api_devices()` accepted mutating action response
  - purpose: audit/notice for successful operator action
  - custom-event equivalent: not applicable
- `simplemdm_action_failure`
  - source: `api_devices()` failed mutating action response
  - purpose: make failed operator actions visible without reading logs
  - custom-event equivalent: not applicable
- `simplemdm_command`
  - source: command ingest / webhook command upsert
  - purpose: current failed-command alert
  - custom-event equivalent: not applicable
- `simplemdm_recovery_lock`
  - source: failed recovery-lock command ingest
  - purpose: separate alert for recovery-lock failures
  - custom-event equivalent: not applicable
- `simplemdm_enrollment`
  - source: device snapshot comparison
  - purpose: enrolled-state regression
  - if represented as a custom rule:
    - `Source Field`: `Enrollment Status`
    - `Trigger`: `Changed To`
    - `Target Value`: `unenrolled`
- `simplemdm_dep`
  - source: device snapshot comparison
  - purpose: ADE / DEP regression
  - if represented as a custom rule:
    - `Source Field`: `ADE / DEP`
    - `Trigger`: `Became Disabled`
- `simplemdm_filevault`
  - source: device snapshot comparison
  - purpose: encryption regression
  - if represented as a custom rule:
    - `Source Field`: `FileVault`
    - `Trigger`: `Became Disabled`
- `simplemdm_supervision`
  - source: device snapshot comparison
  - purpose: supervision regression
  - if represented as a custom rule:
    - `Source Field`: `Supervision`
    - `Trigger`: `Became Disabled`
- `simplemdm_firewall`
  - source: device snapshot comparison
  - purpose: firewall regression
  - if represented as a custom rule:
    - `Source Field`: `Firewall`
    - `Trigger`: `Became Disabled`
- `simplemdm_sip`
  - source: device snapshot comparison
  - purpose: SIP regression
  - if represented as a custom rule:
    - `Source Field`: `SIP`
    - `Trigger`: `Became Disabled`
- `simplemdm_passcode`
  - source: device snapshot comparison
  - purpose: passcode compliance regression
  - if represented as a custom rule:
    - `Source Field`: `Passcode Compliance`
    - `Trigger`: `Became Disabled`
- `simplemdm_activation_lock`
  - source: device snapshot comparison
  - purpose: activation-lock regression
  - if represented as a custom rule:
    - `Source Field`: `Activation Lock`
    - `Trigger`: `Became Disabled`
- `simplemdm_stale`
  - source: device snapshot comparison against configured stale threshold
  - purpose: one shared built-in stale-device alert
  - if represented as a custom rule:
    - `Source Field`: `Last Seen`
    - `Trigger`: `Older Than Hours`
    - `Threshold Hours`: use the same value as `event_stale_threshold_hours`

Custom event patterns that are worth documenting and supporting:
- alternate status targets
  - example: `Enrollment Status` -> `Changed To` -> `awaiting_enrollment` or `retired`
  - useful because the built-in enrollment event is specifically about leaving the enrolled state, not every status value
- alternate stale thresholds
  - example: `Last Seen` -> `Older Than Hours` -> `48` or `12`
  - useful because the built-in stale event uses one shared module threshold, while custom rules can create separate thresholds and severities
- alternate workflow-specific messaging
  - example: `ADE / DEP` -> `Became Disabled` with a different message and module suffix
  - useful when one team wants a separate event slot or wording without changing the global built-in event

Examples of useful custom layouts:
- `awaiting_enrollment`
  - `Source Field`: `Enrollment Status`
  - `Trigger`: `Changed To`
  - `Target Value`: `awaiting_enrollment`
  - use case: staging queue visibility
- `retired_status`
  - `Source Field`: `Enrollment Status`
  - `Trigger`: `Changed To`
  - `Target Value`: `retired`
  - use case: retirement workflow visibility
- `stale_48h`
  - `Source Field`: `Last Seen`
  - `Trigger`: `Older Than Hours`
  - `Threshold Hours`: `48`
  - use case: stricter stale threshold than the built-in default
- `stale_12h_critical`
  - `Source Field`: `Last Seen`
  - `Trigger`: `Older Than Hours`
  - `Threshold Hours`: `12`
  - use case: aggressive alerting for priority devices
- `dep_disabled_ops`
  - `Source Field`: `ADE / DEP`
  - `Trigger`: `Became Disabled`
  - use case: separate event slot/message for operations escalation, even though it overlaps the built-in ADE/DEP condition

Admin UI behavior note:
- new custom rows auto-suggest a suffix from the selected field + trigger combination
- if the admin edits the suffix manually, the UI stops overwriting it

### Scheduling Workflow

- What:
  - A user-friendly schedule layer over the existing `simplemdm_sync.py` + cron model.
- Why:
  - Operators need a professional workflow that exposes `Run Sync Now`, status, presets, and schedule controls without requiring shell knowledge.
- How:
  - The admin UI stores schedule settings in `simplemdm_config`.
  - The worker lifecycle stores run history in `simplemdm_sync_run`.
  - Presets map to cron expressions.
  - `Sync Status -> Queue Next Worker Run` is queue-based and still depends on a worker pickup.
  - `In-Module Sync And Schedule -> Run Sync Now` is immediate and does not need cron, but it does require module-side Python execution.
  - The admin page uses background polling to refresh queue/run cards without a full page reload, with a faster cadence while runs are active.
  - Recurring scheduled sync still depends on cron.
  - Host/manual cron installs should include an explicit `--api-key` (or exported `SIMPLEMDM_API_KEY`) so the worker can read schedule/queue state without an authenticated browser session.
  - If module-side execution is enabled, the module can call `install_cron.sh` / `remove_cron.sh` on behalf of the admin.
  - `Runner MunkiReport URL` prefers canonical MunkiReport config (`WEBHOST` / `SUBDIRECTORY`) and falls back to the current request URL for local/placeholder deployments
  - The admin UI inspects whether Python exists in the module runtime and distinguishes in-module execution from host/manual execution.

### Settings Semantics

When documenting or extending the admin page, keep these meanings stable:

- `SimpleMDM API Key`
  - Required primary credential for sync and module ingest/update auth.
- `Webhook Secret`
  - Shared secret for webhook ingestion.
- `Action API Secret`
  - Shared secret for mutating `api_devices` actions.
- `Compliance Minimum OS`
  - Baseline OS target used by compliance widgets.
- `Enable Delta Sync Mode`
  - Allows cursor-based sync attempts where supported.
- `Enable Command Status Sync`
  - Includes command records when the tenant/API supports them.
- `Enable Scheduled Sync`
  - Enables module schedule intent for `--respect-schedule`.
- `Scheduled Sync Interval`
  - Defines the cadence used by the worker when schedule intent is enabled.
- `Enable Deep Per-Device Subresource Sync`
  - Fetches device-level child objects (`profiles`, `installed_apps`, `users`).
- `Per-Device Deep Sync Limit`
  - Caps the number of devices participating in deep child sync; `0` means unlimited.
- `Preset`
  - Convenience selector that writes a cron expression into `Schedule`.
- `Schedule`
  - Saved cron expression for recurring runs.
- `Runner MunkiReport URL`
  - Base URL used by the worker for posting data back into MunkiReport.
- `Configured Python Path`
  - Configured runner binary path; separate from proving Python exists in the module runtime.
- `Cron Log Path`
  - Target log file path for cron-driven runs.
- `Max Parent Resources`
  - Cap for deep parent-child resource traversal such as `apps/{id}/installs`; `0` means unlimited.
- `Allow In-Module Script Execution For Global Admins`
  - Enables approved runner actions from the UI, subject to runtime checks.
- `Enable Supplemental Module Data Enrichment`
  - Enables or disables Option A supplemental rendering and summary refresh.
- `Supplemental Stale Threshold`
  - Fallback freshness threshold in minutes for supplemental rows when source-specific timestamps are missing.
- `Supplemental Source Registry Overrides (JSON)`
  - Optional JSON override/extension map for allowlisted supplemental source definitions.
- `Disabled Supplemental Sources`
  - Stored as `supplemental_disabled_sources_json`; excludes selected detected sources from enrichment and summary generation without uninstalling the underlying module.
- `Schedule Config`
  - Reflects whether scheduled sync is enabled in settings.
- `Recurring Sync Ready`
  - Reflects whether recurring sync is truly operational, including cron being installed.
- `Last Run`
  - Reflects the most recent completed sync of any kind.
- `Last Run Source`
  - Distinguishes `Immediate (In-Module)`, `Queued Admin Request`, and `Scheduled`.
- `Next Expected Run`
  - Derived schedule preview for the next recurring run.

## 3) Module Layout

```text
local/modules/simplemdm/
|- simplemdm_controller.php           # routes, auth checks, ingest handlers, API passthrough
|- simplemdm_processor.php            # legacy processor-style upsert path for device rows
|- simplemdm_factory.php              # module metadata/helper bootstrap
|- simplemdm_*_model.php              # Eloquent models for each table
|- simplemdm_supplemental_summary_model.php  # summary cache/index for Option A supplemental data
|- provides.yml                       # MunkiReport registrations (report, listings, widgets, admin)
|- migrations/                        # schema creation and incremental updates
|- views/                             # report pages, listing pages, widgets, device page UI
|- scripts/simplemdm_sync.py          # server-side sync client (SimpleMDM -> module endpoints)
|- scripts/install_cron.sh            # helper to print/install/remove cron entries; host/manual installs require explicit API key input
|- scripts/remove_cron.sh             # simple cron cleanup helper
|- examples/dashboard.simplemdm.full.yml
`- docs/images/                       # screenshots used by README/docs
```

## 4) High-Level Data Flow

### Sync and Storage Flow

```text
SimpleMDM API
  -> request_sync (admin queue request)
  -> scripts/simplemdm_sync.py
    -> /module/simplemdm/index?op=get_config
    -> /module/simplemdm/index?op=begin_sync_run   -> simplemdm_config
    -> /module/simplemdm/index?op=ingest           -> simplemdm
    -> /module/simplemdm/index?op=ingest_resources -> simplemdm_resource
    -> /module/simplemdm/index?op=ingest_commands  -> simplemdm_command
    -> /module/simplemdm/index?op=update_sync_status -> simplemdm_config

Derived/related writes:
  simplemdm -> simplemdm_relationship_edge
  simplemdm -> simplemdm_device_history
  simplemdm -> simplemdm_dashboard_snapshot
  simplemdm_resource -> simplemdm_relationship_edge

Supplemental reads/index:
  local source tables -> get_supplemental_data/{serial}
  local source tables -> refresh_supplemental_summary -> simplemdm_supplemental_summary
  simplemdm_supplemental_summary -> listing filters/widgets/admin health
```

### Webhook Flow

```text
SimpleMDM Webhook
  -> /module/simplemdm/index?op=webhook
    -> simplemdm_webhook_event
    -> simplemdm
    -> simplemdm_command
    -> simplemdm_relationship_edge
    -> Event_model (current per-device operational alerts)
```

## 5) Entry Points and Responsibilities

### Controller

Primary file: `simplemdm_controller.php`

- Auth and secrets:
  - sync token / API key validation for ingest routes
  - webhook secret validation
  - action secret validation for mutating passthrough actions
- Ingest endpoints:
  - `op=ingest`
  - `op=ingest_resources`
  - `op=ingest_commands`
  - `op=webhook`
  - `op=update_sync_status`
  - `request_sync`
  - `op=begin_sync_run`
- Read endpoints for report/listings/widgets:
  - stats endpoints (enrollment, DEP, compliance, trend, sync telemetry, etc.)
  - device/resource listing data endpoints
  - supplemental device/detail endpoints
  - summary-backed supplemental widget endpoints
  - widget/report routes must respect the active MunkiReport `index_page` setting (`/index.php?/module/...` vs `/module/...`)
- Device API passthrough:
  - `api_devices/...` routes with method allowlists and secret enforcement for mutating requests
  - emits narrow current-device MunkiReport events for:
    - accepted admin actions
    - failed admin actions
    - command failures
    - recovery lock failures
    - enrollment regressions
    - ADE/DEP regressions
    - FileVault regressions
    - supervision regressions
    - firewall regressions
    - SIP regressions
    - passcode regressions
    - activation lock regressions
    - stale-device transitions based on `last_seen_at`

### Sync Script

Primary file: `scripts/simplemdm_sync.py`

- Pulls devices/resources/commands from SimpleMDM API
- For host/manual runs, expects an explicit `--api-key` or `SIMPLEMDM_API_KEY` so config bootstrap does not depend on browser-session auth
- Fetches worker config through `index?op=get_config` using sync-token or API-key auth
- Uses a 120-second request timeout with retry/backoff so large tenants and slower
  MunkiReport ingest responses are less likely to fail mid-run
- Restricts collection discovery to documented SimpleMDM GET endpoints so telemetry reflects real failures
- Flattens/normalizes fields for `simplemdm`
- Preserves raw payload fragments in JSON fields
- Submits batched payloads to module ingest endpoints
- Supports schedule-aware execution via `--respect-schedule`
- Claims queued/scheduled runs via `begin_sync_run`
- Supports deep sync flags:
  - `--delta`
  - `--sync-commands`
  - `--sync-device-subresources`
- Command sync uses the tenant-wide `/commands` collection only. If that endpoint is unavailable in the tenant/API version, the worker skips command sync instead of falling back to per-device command probes.

### Processor

Primary file: `simplemdm_processor.php`

- Legacy Processor class to upsert device rows from JSON payload
- Useful as compatibility/fallback path
- Main current operational sync path is script -> controller ingest endpoints

## 6) Database Model Map

| Table | Model | Purpose |
|---|---|---|
| `simplemdm` | `simplemdm_model.php` | Device-centric flattened inventory + stored JSON attributes/relationships |
| `simplemdm_config` | `simplemdm_config_model.php` | Settings and sync status fields |
| `simplemdm_resource` | `simplemdm_resource_model.php` | Non-device resources synced from SimpleMDM endpoints |
| `simplemdm_command` | `simplemdm_command_model.php` | Command execution states/history |
| `simplemdm_webhook_event` | `simplemdm_webhook_event_model.php` | Raw webhook event audit trail |
| `simplemdm_relationship_edge` | `simplemdm_relationship_edge_model.php` | Normalized resource relationships for linked-resource views |
| `simplemdm_dashboard_snapshot` | `simplemdm_dashboard_snapshot_model.php` | Time-series dashboard metrics |
| `simplemdm_device_history` | `simplemdm_device_history_model.php` | Daily per-device state snapshots |
| `simplemdm_supplemental_summary` | `simplemdm_supplemental_summary_model.php` | Summary/index layer for Option A filters, widgets, and freshness |
| `simplemdm_client_fact` | `simplemdm_client_fact_model.php` | Current-value table for Option B client-reported facts |
| `simplemdm_client_fact_history` | `simplemdm_client_fact_history_model.php` | Optional history trail for Option B facts |
| `simplemdm_mcp_finding` | `simplemdm_mcp_finding_model.php` | Findings pushed by the SimpleMDM-MCP server (`ingest_mcp_findings`), rendered by the MCP Findings widget |

Migration files are in `migrations/` and should normally be appended.
Treat already-deployed migrations as immutable; only correct a migration in-place before rollout if the shipped revision is not yet safe to deploy.
Do not delete or rename shipped migration files in an active release; use a planned baseline/squash only as a controlled future cleanup.

## 7) UI and View Map

### Registrations

Primary file: `provides.yml`

- Registers:
  - detail widget (`simplemdm_detail`)
  - client tab (`simplemdm-tab`)
  - listings (`simplemdm`, `simplemdm_resources`)
  - report (`simplemdm`)
  - all widgets (core + resource-type widgets)
  - admin page entry

### Main UI Files

- `views/simplemdm_report.php`: report page widget container
- `views/simplemdm_listing.php`: device listing page
- `views/simplemdm_resources_listing.php`: resource listing page
  - uses server-side DataTables pagination/filtering so large `simplemdm_resource` datasets do not load entirely into the browser or PHP response
- `views/simplemdm_admin.php`: admin settings page
  - includes schedule UX, queue-based and immediate `Run Sync Now`, schedule config/readiness, last run/source, next expected run, and manual access/downloads
- `views/simplemdm_device.php`: standalone device details and action runner
- `views/simplemdm_widget_modern_assets.php`: shared JS/CSS behavior for widget layout/interactions

### Visual References

Use existing screenshots while changing UI:

![SimpleMDM Report](images/dashboard_kpis.png)
![SimpleMDM Report (continued)](images/dashboard_security_enrollment.png)
![Device Detail Overview](images/simplemdm_device_detail.png)
![Device Actions Runner](images/device_action_runner.png)
![Admin Settings (representative admin layout)](images/admin_api_sync_status.png)

## 8) Common Change Workflows

### A) Add a New Dashboard Widget

1. Create a view under `views/` (or reuse `simplemdm_resource_type_base_widget.php` pattern).
2. Register widget key in `provides.yml`.
3. If needed, add/extend controller JSON endpoint that feeds widget data.
4. If needed, add localization string to `locales/en.json`.
5. Validate in:
   - `reports/simplemdm`
   - dashboards using widget picker

### B) Add a New Resource Type Sync

1. Update fetch scope in `scripts/simplemdm_sync.py` (endpoint/type map).
2. Ensure ingest path (`ingest_resources`) can store/normalize the type.
3. Add listing/report widget mapping if surfacing in UI.
4. Add indexes/migration only if query shape changes materially.

### C) Add a New Device Action

1. Extend action mapping and allowed-method rules in `simplemdm_controller.php` passthrough logic.
2. Add action option to UI in `views/simplemdm_device.php`.
3. Keep mutating action checks behind:
   - global auth
   - `action_api_secret` validation
4. Test both success and denied paths.

### D) Adding New SimpleMDM APIs

Expected difficulty depends on the kind of upstream API being added:

- New read-only collection endpoint:
  - usually low effort
  - add endpoint fetch scope in `scripts/simplemdm_sync.py`
  - confirm `ingest_resources` can store the normalized records
  - optionally add listing/widget/report surfacing

- New nested resource endpoint:
  - usually low to moderate effort
  - extend nested endpoint maps in `scripts/simplemdm_sync.py`
  - confirm relationship/resource linking still works in `simplemdm_controller.php`
  - optionally surface it in device/resource views

- New per-device subresource endpoint:
  - usually moderate effort
  - extend device deep-sync maps in `scripts/simplemdm_sync.py`
  - confirm `get_device_subresources` and related UI can expose it
  - watch API volume and runtime impact when enabling it broadly

- New mutating device action:
  - usually moderate effort, but more sensitive than read-only sync
  - update passthrough allowlists and action handling in `simplemdm_controller.php`
  - update action UI in `views/simplemdm_device.php`
  - keep auth and `action_api_secret` enforcement intact

- New API that needs a new schema/model/query shape:
  - moderate to high effort
  - may require a new migration, model, controller query, and UI/report wiring
  - usually only needed when the generic `simplemdm_resource` storage model is not sufficient

Primary files to evaluate first:
- `scripts/simplemdm_sync.py`
- `simplemdm_controller.php`
- `views/simplemdm_device.php`
- `views/simplemdm_resources_listing.php`
- `docs/API_REFERENCE.md`

Rule of thumb:
- collection resources are easiest
- deep-sync resources are incremental but need runtime caution
- device actions are straightforward structurally, but require the most care around auth/safety
- schema changes are the main point where work stops being incremental

## 9) Security Boundaries

- Ingest/write routes rely on sync auth token/API key checks.
- Token-readable module data routes allow sync-token auth for selected read-only
  dashboard/detail/MCP-readback endpoints.
- Admin-triggered queue requests rely on global admin session checks.
- Worker-side run claims rely on sync auth token/API key checks.
- Host/manual workers should use explicit API-key or sync-token auth; do not assume a browser session is available.
- Webhook route supports webhook secret and sync token fallback.
- Mutating passthrough requests require:
  - global admin session
  - valid action secret
- Secret values are masked in non-global config responses.
- Config write (`save_config`) requires a global-admin session only; sync-token
  (`X-SIMPLEMDM-API-KEY`) auth is not accepted for this route (fixed 2026-07-10 —
  it previously was, see `docs/SECURITY.md`).

## 10) Dev Checklist Before Commit

1. Run migrations in your target environment:
   - `php please migrate`
2. Run one manual sync:
   - `python3 local/modules/simplemdm/scripts/simplemdm_sync.py --api-key '...' --munkireport-url '...' --verbose`
3. If validating scheduled or queued host/manual runs, confirm the runner or cron command also includes `--api-key '...'` (or exports `SIMPLEMDM_API_KEY`) and can reach `index?op=get_config` without an interactive login.
4. Validate:
   - report renders
   - device/resource listings populate
   - device details load
   - no PHP errors in logs
5. For UI changes, verify both:
   - dashboard/report widget pages
   - standalone listing/admin/device pages
6. If you touched `simplemdm_mcp_finding_model.php` or the MCP findings routes in
   `simplemdm_controller.php`, run the PHPUnit suite:
   - `composer install` (once), then `vendor/bin/phpunit`
   - see `docs/TESTING.md` for what each test file covers

## 11) File-Level Quick Reference

- Routing/auth/ingest core: `simplemdm_controller.php`
- Sync client: `scripts/simplemdm_sync.py`
- Device table model: `simplemdm_model.php`
- Resource table model: `simplemdm_resource_model.php`
- Command table model: `simplemdm_command_model.php`
- MCP findings model: `simplemdm_mcp_finding_model.php`
- Registration map: `provides.yml`
- Shared widget assets: `views/simplemdm_widget_modern_assets.php`
- Device actions UI: `views/simplemdm_device.php`
- MCP findings dashboard widget: `views/simplemdm_mcp_findings_widget.php`
- PHPUnit tests: `tests/Unit/`, bootstrap in `tests/bootstrap.php`, config in `phpunit.xml`

# SimpleMDM Developer Guide

This guide is for contributors who need to understand and modify the module safely.

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

### What this module does

- Syncs SimpleMDM device inventory into MunkiReport for centralized visibility.
- Syncs SimpleMDM resources (apps/profiles/groups/scripts and related objects) for reporting and drill-down.
- Exposes operational dashboards/widgets for compliance, security posture, command status, and sync health.
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
  - Reads from `simplemdm`, `simplemdm_resource`, `simplemdm_relationship_edge`, and subresource-derived records.
  - Mutating actions flow through `api_devices` passthrough with global auth + action secret requirements.

### Admin Settings Page

- What:
  - Module configuration UI for API key, secrets, sync controls, widget toggles, and queued `Sync Now` state.
- Why:
  - Keeps operational controls in one place without code edits.
- How:
  - Registered under `admin_pages` in `provides.yml`.
  - View: `views/simplemdm_admin.php`.
  - Persists to `simplemdm_config`.
  - `Sync Now` queues work in config state; host cron/manual runner still executes `simplemdm_sync.py`.

## 3) Module Layout

```text
local/modules/simplemdm/
|- simplemdm_controller.php           # routes, auth checks, ingest handlers, API passthrough
|- simplemdm_processor.php            # legacy processor-style upsert path for device rows
|- simplemdm_factory.php              # module metadata/helper bootstrap
|- simplemdm_*_model.php              # Eloquent models for each table
|- provides.yml                       # MunkiReport registrations (report, listings, widgets, admin)
|- migrations/                        # schema creation and incremental updates
|- views/                             # report pages, listing pages, widgets, device page UI
|- scripts/simplemdm_sync.py          # server-side sync client (SimpleMDM -> module endpoints)
|- scripts/install_cron.sh            # helper to print/install/remove cron entries
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
    -> /module/simplemdm/get_config
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
```

### Webhook Flow

```text
SimpleMDM Webhook
  -> /module/simplemdm/index?op=webhook
    -> simplemdm_webhook_event
    -> simplemdm
    -> simplemdm_command
    -> simplemdm_relationship_edge
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
  - widget/report routes must respect the active MunkiReport `index_page` setting (`/index.php?/module/...` vs `/module/...`)
- Device API passthrough:
  - `api_devices/...` routes with method allowlists and secret enforcement for mutating requests

### Sync Script

Primary file: `scripts/simplemdm_sync.py`

- Pulls devices/resources/commands from SimpleMDM API
- Flattens/normalizes fields for `simplemdm`
- Preserves raw payload fragments in JSON fields
- Submits batched payloads to module ingest endpoints
- Supports schedule-aware execution via `--respect-schedule`
- Claims queued/scheduled runs via `begin_sync_run`
- Supports deep sync flags:
  - `--delta`
  - `--sync-commands`
  - `--sync-device-subresources`

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

Migration files are in `migrations/` and should normally be appended.
Treat already-deployed migrations as immutable; only correct a migration in-place before rollout if the shipped revision is not yet safe to deploy.

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
- `views/simplemdm_admin.php`: admin settings page
- `views/simplemdm_device.php`: standalone device details and action runner
- `views/simplemdm_widget_modern_assets.php`: shared JS/CSS behavior for widget layout/interactions

### Visual References

Use existing screenshots while changing UI:

![SimpleMDM Report](images/dashboard-overview-part1.png)
![SimpleMDM Report (continued)](images/dashboard-overview-part2.png)
![Device Detail Overview](images/device-detail-overview.png)
![Device Actions Runner](images/device-actions-runner.png)
![Admin Settings](images/admin-settings.png)

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

## 9) Security Boundaries

- Ingest/write routes rely on sync auth token/API key checks.
- Admin-triggered queue requests rely on global admin session checks.
- Worker-side run claims rely on sync auth token/API key checks.
- Webhook route supports webhook secret and sync token fallback.
- Mutating passthrough requests require:
  - global admin session
  - valid action secret
- Secret values are masked in non-global config responses.

## 10) Dev Checklist Before Commit

1. Run migrations in your target environment:
   - `php please migrate`
2. Run one manual sync:
   - `python3 local/modules/simplemdm/scripts/simplemdm_sync.py --api-key '...' --munkireport-url '...' --verbose`
3. Validate:
   - report renders
   - device/resource listings populate
   - device details load
   - no PHP errors in logs
4. For UI changes, verify both:
   - dashboard/report widget pages
   - standalone listing/admin/device pages

## 11) File-Level Quick Reference

- Routing/auth/ingest core: `simplemdm_controller.php`
- Sync client: `scripts/simplemdm_sync.py`
- Device table model: `simplemdm_model.php`
- Resource table model: `simplemdm_resource_model.php`
- Command table model: `simplemdm_command_model.php`
- Registration map: `provides.yml`
- Shared widget assets: `views/simplemdm_widget_modern_assets.php`
- Device actions UI: `views/simplemdm_device.php`

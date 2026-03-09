# SimpleMDM Module for MunkiReport

Module-only SimpleMDM integration for MunkiReport.

This module syncs devices and API resources from SimpleMDM server-side, stores them locally, and exposes listings, widgets, and per-device connected resource views.

## Key Points

- No MunkiReport core patch required.
- Sync auth and routing are handled inside this module.
- Supports API-key protected ingest routes and optional webhook secret protected route.
- Supports role + action-secret protected device API passthrough routes (`api_devices`).
- Supports deep API resource sync (apps/profiles/groups/etc. where available).
- Provides device-level connected resource mapping.
- Supports dashboard add/remove widgets for all SimpleMDM widgets, including per-resource-type widgets.
- All report widgets are modernized with chart/KPI dashboards (NVD3-based where applicable) and drill-down links.
- Widgets auto-adapt to layout density and active theme (including Bootswatch theme accent matching).
- Modern widget UI assets are loaded inline from `views/simplemdm_widget_modern_assets.php` (no separate module CSS/JS build step required).
- Optional delta-sync, command-status sync, and sync telemetry reporting are built into the sync script + module.

## Quick Start (5 Minutes)

1. Install and migrate:
   - Copy module to `local/modules/simplemdm`
   - Add `simplemdm` to `MODULES` in `.env`
   - Run `php /path/to/munkireport/please migrate`
2. Configure API/auth:
   - Open `Admin -> SimpleMDM Settings`
   - Save `api_key`
   - (Recommended) set `webhook_secret` and `action_api_secret`
3. Configure sync behavior (recommended defaults):
   - Enable `enable_scheduled_sync`
   - Set `sync_interval_minutes` to `15`
   - Enable `sync_delta_enabled`
   - Enable `sync_device_subresources_enabled`
   - Set `device_subresource_limit` to `100` (test) or `0` (all devices)
4. Add schedule runner (cron):
   - `* * * * * /usr/bin/python3 /path/to/.../simplemdm_sync.py --munkireport-url 'https://mr' --respect-schedule --max-parent-resources 25 >> /var/log/simplemdm_sync.log 2>&1`
5. Verify:
   - `reports/simplemdm` renders widgets
   - `show/listing/simplemdm/simplemdm` has devices
   - `module/simplemdm/device/{serial}` shows attributes, connected resources, subresources, and actions

## What This Module Does

SimpleMDM module is used to:

- Pull SimpleMDM device inventory into MunkiReport for centralized visibility.
- Pull SimpleMDM resource objects (apps, profiles, groups, scripts, and related objects) for reporting.
- Show connected resources per device so admins can see which profiles/apps/groups are tied to endpoints.
- Show synced per-device subresource tables (installed apps, users, profiles) on device detail pages.
- Provide a device action runner UI on device detail pages with action-secret enforcement.
- Provide dashboard widgets for enrollment, DEP, supervision, FileVault, resource mix, command status, compliance, and sync health.
- Track historical trends with snapshots and per-device history for change over time.
- Ingest webhooks for near-real-time updates and maintain event audit records.
- Normalize relationship data for deeper analysis and filtering.

Typical use cases:

- Fleet posture dashboard for security/compliance and OS baseline tracking.
- Operational monitoring of command execution outcomes and sync reliability.
- Helpdesk and engineering troubleshooting for “what is assigned to this device?” questions.
- Reporting on configuration policy spread (profiles, restrictions, apps) across the fleet.

## Architecture

- Sync script: `local/modules/simplemdm/scripts/simplemdm_sync.py`
- Module endpoints (module-only):
  - `/index.php?/module/simplemdm/index?op=ingest`
  - `/index.php?/module/simplemdm/index?op=ingest_resources`
  - `/index.php?/module/simplemdm/index?op=ingest_commands`
  - `/index.php?/module/simplemdm/index?op=webhook`
  - `/index.php?/module/simplemdm/index?op=update_sync_status`
  - `/index.php?/module/simplemdm/get_dashboard_trend`
  - `/index.php?/module/simplemdm/get_os_security_stats`
  - `/index.php?/module/simplemdm/get_command_status_stats`
  - `/index.php?/module/simplemdm/get_compliance_stats`
  - `/index.php?/module/simplemdm/get_sync_telemetry`
  - `/index.php?/module/simplemdm/get_resource_type_stats`
  - `/index.php?/module/simplemdm/get_resource_type_count/{type}`
- Tables:
  - `simplemdm` (device records)
  - `simplemdm_config` (settings + sync status)
  - `simplemdm_resource` (non-device API resources)
  - `simplemdm_dashboard_snapshot` (historical dashboard metrics)
  - `simplemdm_command` (command status history)
  - `simplemdm_webhook_event` (raw webhook events)
  - `simplemdm_relationship_edge` (normalized relationship edges)
  - `simplemdm_device_history` (daily per-device state snapshots)

## Features

- Device listing: `show/listing/simplemdm/simplemdm`
  - URL filter support: `status`, `dep`, `supervised`, `filevault`, `group`, `os`
  - On-page filter controls: status/DEP/supervised/FileVault/group/OS with apply/reset actions.
- API resources listing: `show/listing/simplemdm/simplemdm_resources`
  - Filter by resource type, resource ID, endpoint exact match, or endpoint contains match.
- SimpleMDM report: `reports/simplemdm`
- Admin page: `module/simplemdm/admin`
  - Appears in top navigation under `Admin -> SimpleMDM Settings` (module `admin_pages` registration).
- Client tab + standalone device view:
  - Client tab: `#tab_simplemdm-tab`
  - Standalone: `module/simplemdm/device/{serial}`
  - `simplemdm_device` is a standalone page view (not a dashboard widget).
- Connected Resources on device pages:
  - Shows linked apps/groups/profiles/resources.
  - Links into filtered API resources listing.

UI modernization scope:
- Module pages now use the same modern theme tokens/components as SimpleMDM widgets:
  - `module/simplemdm/admin`
  - `show/listing/simplemdm/simplemdm`
  - `show/listing/simplemdm/simplemdm_resources`
  - `module/simplemdm/device/{serial}`
  - `#tab_simplemdm-tab`
- Interactive widget grid behavior applies to:
  - Dashboard pages that contain SimpleMDM widgets
  - `show/report/simplemdm/simplemdm`
- Interactive widget grid behavior does not apply to listing/admin/device pages.

## Installation

1. Place module in local modules:

```bash
cp -R simplemdm /path/to/munkireport/local/modules/simplemdm
```

2. Enable module in MunkiReport `.env`:

```env
MODULES="...,simplemdm,..."
```

3. Run migrations:

```bash
php /path/to/munkireport/please migrate
```

## Configuration

1. Open `Admin -> SimpleMDM Settings`.
2. Enter SimpleMDM API key and save.
3. Optional: toggle SimpleMDM widgets on/off in the same admin page (applies on dashboard/report pages where those widgets are present).
4. Optional: in Advanced Sync & Compliance, set:
   - `webhook_secret`
   - `action_api_secret`
   - `compliance_min_os`
   - `enable_scheduled_sync`
   - `sync_interval_minutes`
   - `sync_delta_enabled`
   - `sync_commands_enabled`
   - `sync_device_subresources_enabled`
   - `device_subresource_limit`

Current admin scope:
- Admin currently manages API/auth, widget visibility, and advanced sync/compliance settings.
- Layout ordering, full-width spans, and expand/collapse behavior are module-driven defaults (not separate admin toggles).
- If the Admin menu item does not appear after module updates, refresh/restart MunkiReport so module `provides.yml` metadata is reloaded.

### Advanced Setting Behavior

- `webhook_secret`
  - Shared secret used by the webhook ingest route.
  - If set, webhook senders should include `X-SIMPLEMDM-WEBHOOK-SECRET: <secret>`.
- `action_api_secret`
  - Shared secret required for mutating device passthrough calls (`POST/PATCH/DELETE/PUT`).
  - Pass via header: `X-SIMPLEMDM-ACTION-SECRET: <secret>`.
- `compliance_min_os`
  - Minimum OS baseline used by compliance calculations.
  - Format should be dotted versions, for example `14.4` or `15.1.2`.
- `enable_scheduled_sync`
  - Master enable/disable for schedule-gated sync runs.
- `sync_interval_minutes`
  - Schedule cadence in minutes (minimum `1`).
- `sync_delta_enabled`
  - Enables cursor/delta attempt in the sync script.
  - If endpoint does not support delta parameters, script falls back to full for that scope.
- `sync_commands_enabled`
  - Enables command status sync during regular sync runs.
  - Can still be overridden manually by running script with `--sync-commands`.
- `sync_device_subresources_enabled`
  - Enables per-device deep subresource sync (`profiles`, `installed_apps`, `users`) during regular sync runs.
- `device_subresource_limit`
  - Caps per-device deep subresource sync scope (`0` means all devices).

Security behavior:
- `api_key` is only returned by `get_config` for global admins.
- Non-global callers receive `api_key_set` only.
- `webhook_secret` is not returned to non-global callers; only `webhook_secret_set` flag is exposed.
- `action_api_secret` is not returned to non-global callers; only `action_api_secret_set` flag is exposed.

## Connect New Features (End-to-End)

### 1) API Sync Connection

1. In SimpleMDM, generate or copy an API key with read access to devices and resources.
2. In MunkiReport `Admin -> SimpleMDM Settings`, save the API key.
3. Run one manual sync:

```bash
python3 /path/to/munkireport/local/modules/simplemdm/scripts/simplemdm_sync.py \
  --api-key 'YOUR_SIMPLEMDM_API_KEY' \
  --munkireport-url 'https://your-munkireport' \
  --verbose
```

4. Verify status in admin page:
   - `last_sync_status` should become `success`.
   - `last_sync_time` should update.
5. Verify data exists:
   - Device listing: `show/listing/simplemdm/simplemdm`
   - Resource listing: `show/listing/simplemdm/simplemdm_resources`

### 2) Webhook Connection

1. Set `webhook_secret` in module advanced settings.
2. In SimpleMDM webhook configuration, set target URL:
   - `https://<your-munkireport>/index.php?/module/simplemdm/index?op=webhook`
3. Configure webhook request header:
   - `X-SIMPLEMDM-WEBHOOK-SECRET: <same secret>`
4. Send a test event from SimpleMDM.
5. Confirm events are being stored (via module data/API checks) and widget data updates after next dashboard refresh.

Fallback auth option:
- Instead of webhook secret, webhook sender may use `X-SIMPLEMDM-API-KEY` matching stored module API key.

### 3) Delta Sync Connection

1. Enable `sync_delta_enabled` in admin advanced settings.
2. Keep regular scheduled sync running.
3. Script reads `last_sync_cursor` from module config, attempts delta, then writes updated cursor.
4. If unsupported by endpoint, script automatically runs full for that scope and records telemetry.

### 4) Command Status Connection

1. Enable `sync_commands_enabled` in admin advanced settings.
2. Run sync or scheduled sync.
3. Optionally cap API load with `--commands-limit`.
4. Add `simplemdm_command_status` widget to dashboard.
5. Command fetch strategy:
   - Primary: `GET /api/v1/commands` (tenant-wide).
   - Fallback: `GET /api/v1/devices/{device_id}/commands` (per-device) when tenant-wide endpoint is unavailable.
6. Validate by opening:
   - `module/simplemdm/get_command_status_stats`

### 5) Compliance + Sync Health Connection

1. Set `compliance_min_os` to your baseline (example: `14.6`).
2. Add these widgets:
   - `simplemdm_compliance`
   - `simplemdm_sync_health`
3. Validate endpoints:
   - `module/simplemdm/get_compliance_stats`
   - `module/simplemdm/get_sync_telemetry`

### 6) Device Action Passthrough Connection

1. In admin advanced settings, set `action_api_secret`.
2. Open a device detail page (`module/simplemdm/device/{serial}`).
3. In `Device Actions`, enter the same secret and run a safe action first (recommended: `refresh`).
4. Confirm success response in action output panel.
5. For API-only usage, send header:
   - `X-SIMPLEMDM-ACTION-SECRET: <action_api_secret>`
   - to `module/simplemdm/api_devices/...` for mutating methods.

## Sync Script

### Run manually

```bash
python3 /path/to/munkireport/local/modules/simplemdm/scripts/simplemdm_sync.py \
  --api-key 'YOUR_SIMPLEMDM_API_KEY' \
  --munkireport-url 'https://your-munkireport'
```

### Useful options

- `--verbose`: debug logging
- `--dry-run`: fetch only, no submit
- `--max-parent-resources N`: limit deep nested sync per parent endpoint (0 = all)
- `--delta`: attempt delta sync with last cursor
- `--last-sync-cursor`: override cursor used for delta sync
- `--sync-commands`: fetch/submit command status records
- `--commands-limit N`: cap command fetch count
- `--sync-device-subresources`: fetch `devices/{id}/profiles`, `devices/{id}/installed_apps`, and `devices/{id}/users`
- `--device-subresource-limit N`: cap deep per-device subresource fetch (0 = all)
- `--respect-schedule`: honor admin schedule controls (`enable_scheduled_sync` + `sync_interval_minutes`)
- `--force-run`: bypass `--respect-schedule` gate and run immediately
- `--sync-interval-minutes N`: override schedule interval for this run (`0` uses admin config value)

### Auto-config behavior

If `--api-key` is omitted, script reads API key from module config (`get_config`) when available.

Sync mode decisions:
- Manual `--delta` enables delta mode even if admin toggle is off.
- If admin toggle `sync_delta_enabled=1`, script uses delta mode for scheduled/default runs.
- Manual `--sync-commands` enables commands even if admin toggle is off.
- If admin toggle `sync_commands_enabled=1`, script includes commands for scheduled/default runs.
- Manual `--sync-device-subresources` enables per-device subresource sync even if admin toggle is off.
- If admin toggle `sync_device_subresources_enabled=1`, script includes per-device subresources for scheduled/default runs.
- If `device_subresource_limit` is set in admin config, script applies it unless CLI overrides it.
- `--respect-schedule` only runs when admin schedule is enabled and due by interval.
- `--force-run` overrides schedule gating.
- Scheduling enable/disable is controlled by `enable_scheduled_sync`; interval controls cadence when enabled.

Telemetry written back on sync status updates:
- API request count
- API error count
- Rate-limit hit count
- Last sync scope (`full` or `delta`)
- Delta cursor used/new cursor
- Whether command sync ran

Example (faster test run):

```bash
python3 .../simplemdm_sync.py --api-key 'KEY' --munkireport-url 'https://mr' --max-parent-resources 25 --verbose
```

Example with delta + commands:

```bash
python3 .../simplemdm_sync.py --api-key 'KEY' --munkireport-url 'https://mr' --delta --sync-commands --commands-limit 250
```

Example with per-device subresources:

```bash
python3 .../simplemdm_sync.py --api-key 'KEY' --munkireport-url 'https://mr' --sync-device-subresources --device-subresource-limit 200
```

### Scheduling

Recommended: run cron every minute with `--respect-schedule`, then control cadence from Admin settings:
- `enable_scheduled_sync`
- `sync_interval_minutes`

Example cron:

```cron
* * * * * /usr/bin/python3 /path/to/.../simplemdm_sync.py --munkireport-url 'https://mr' --respect-schedule --max-parent-resources 25 >> /var/log/simplemdm_sync.log 2>&1
```

Optional production additions:
- Keep the schedule-gated runner above as your default.
- Add explicit off-schedule deep jobs only if you want separate heavy windows (for example command backfill or larger per-device subresource sweeps).

## Widgets

### Core SimpleMDM widgets

- `simplemdm_enrollment`
- `simplemdm_dep`
- `simplemdm_filevault`
- `simplemdm_supervised`
- `simplemdm_group`
- `simplemdm_resource_types`
- `simplemdm_device_listing`
- `simplemdm_devices_table` (dashboard mini-table of devices with links to detail pages)
- `simplemdm_resources_listing`
- `simplemdm_trend` (historical trend line from sync snapshots)
- `simplemdm_os_security` (stacked enrollment/supervision/FileVault by OS)
- `simplemdm_group_top` (top assignment groups bar chart)
- `simplemdm_resource_mix` (resource type donut)
- `simplemdm_command_status` (command state distribution)
- `simplemdm_compliance` (compliant vs noncompliant + reasons)
- `simplemdm_sync_health` (latest sync telemetry + scope/delta/rate-limit stats)

Widget purpose note:
- `simplemdm_group` = full groups widget (top chart + expandable assignment group list + drilldown links)
- `simplemdm_group_top` = compact top-groups summary widget

### Per-resource-type widgets (individually add/remove)

- `simplemdm_rt_installed_app`
- `simplemdm_rt_app`
- `simplemdm_rt_assignment_group`
- `simplemdm_rt_custom_configuration_profile`
- `simplemdm_rt_device_group`
- `simplemdm_rt_enrollment`
- `simplemdm_rt_script`
- `simplemdm_rt_restrictions`
- `simplemdm_rt_privacy_preference`
- `simplemdm_rt_software_update_policyformac_os`
- `simplemdm_rt_home_screen_layout`
- `simplemdm_rt_lock_screen_message`
- `simplemdm_rt_managed_software_updates`
- `simplemdm_rt_notification_settings`
- `simplemdm_rt_disk_management_settings`
- `simplemdm_rt_gatekeeper_policy`
- `simplemdm_rt_kernel_extension_policy`
- `simplemdm_rt_login_window`
- `simplemdm_rt_system_extension_policy`
- `simplemdm_rt_wallpaper`

You can add/remove via Widget Gallery and dashboard layout controls.

Theme/Layout-aware styling:
- Widgets automatically switch between `compact` and `comfortable` density modes based on explicit layout mode classes/attributes (if present) or auto-detection from screen/widget width.
- Color tokens switch by mode/theme (surface, border, accent, chart palettes), including explicit variants for:
  - `light + comfortable`
  - `light + compact`
  - `dark + comfortable`
  - `dark + compact`
- You can force mode by setting `data-layout-mode="compact"` or `data-layout-mode="comfortable"` on `<body>`, or using matching body classes such as `layout-compact`.
- You can force theme with `data-theme="dark|light"` (or `data-bs-theme` / `data-color-mode`) and the widgets will live-update chart colors on mode/theme change.
- Bootswatch theme accents (Cerulean, Darkly, Cyborg, Slate, etc.) are detected from active stylesheet and applied to widget accents/charts.
- Runtime attributes set by the module:
  - `data-simplemdm-layout="compact|comfortable"`
  - `data-simplemdm-theme="light|dark"`
  - `data-simplemdm-theme-name="{bootswatch-name|auto}"`
- Widgets re-render on `simplemdm:modechange` to keep chart colors synchronized after theme/layout changes.

Interactive layout behavior (module-wide):
- On supported pages, SimpleMDM widgets are automatically grouped into a module-managed grid container (`#simplemdm-dashboard-grid`).
- Supported pages:
  - Dashboard pages containing SimpleMDM widgets
  - `show/report/simplemdm/simplemdm`
- Grid uses balanced masonry placement (shortest-column algorithm) to reduce empty vertical gaps.
- Widget width honors intended span:
  - Full-width for designated featured widgets.
  - Multi-column for regular widgets.
- Click a widget to select it; selection reveals move/order controls and resize affordances.
- Each widget can be minimized to title-only and expanded again from the header control.
- Selected widgets can be moved by dragging the move handle in the widget header.
- Drop behavior:
  - Drop near center of another widget: swap
  - Drop near edges/empty space: insert/reorder
  - Drop near top edge: force insert at top
- Drag auto-scroll is enabled when dragging near top/bottom viewport edges.
- Dragging to empty dashboard/report space inserts the widget at that visual position (not swap-only).
- Empty-space drops persist both column and vertical position so intentional blank gaps can be kept between widgets.
- Selected widgets can be resized smaller or larger using edge handles:
  - Right edge: width
  - Bottom edge: height
  - Bottom-right corner: width + height
- Featured widgets (such as `simplemdm_group` and `simplemdm_resource_types`) are also resizable.
- Non-featured widgets default to a uniform baseline footprint (single-column span + baseline min-height) while still expanding for larger content.
- Fallback ordering controls are included in each widget header (`top`, `up`, `down`) for precise keyboard/mouse operation.
- Custom dashboard widget order/size is persisted in browser `localStorage` per dashboard URL.
- You can reset custom layout state from browser console with `window.simplemdmResetDashboardLayout()`.
- A floating `Reset Layout` button is available on supported pages.
- `Reset Layout` restores defaults for the current page context only:
  - On `show/report/simplemdm/simplemdm`, it restores the report-specific default layout.
  - On dashboard pages, it restores dashboard defaults.
- Long list-heavy widgets automatically get internal list scrolling for readability and to avoid oversized columns.
- Current featured full-width widgets (within the SimpleMDM widget set) are ordered as:
  - `simplemdm_resource_types`
  - `simplemdm_group`
  - `simplemdm_devices_table`
  - `simplemdm_group_top`
- Report page default ordering keeps the full-width widgets above unchanged and applies a cleaner default sequence for small column widgets below them.

Scope notes:
- This behavior is module-only and applies to all users loading SimpleMDM widgets.
- Non-SimpleMDM widgets are not modified by this layout engine.

Resource/Group expand-collapse behavior:
- `simplemdm_resource_types` has two sections:
  - `Resource Type Chart`
  - `Resource Cards` (`+ Expand` / `- Collapse`)
- `simplemdm_group` has two sections:
  - `Top Groups Chart`
  - `Assignment Group List` (`+ Expand` / `- Collapse`)
- `simplemdm_devices_table` has a `Device Rows` section (`+ Expand` / `- Collapse`)
- In collapsed mode, list/card areas are intentionally scrollable.
- Collapsed section scrolling is handled by the section body (single scroll container) to avoid nested-scroll conflicts.
- Collapsed toggles show hidden-count labels when applicable:
  - `+ Expand (N more)` for Resource Cards
  - `+ Expand (N more)` for Assignment Group List
- Resource Cards collapsed state uses row-aligned height to avoid half-cut cards, plus fade/hint when more content is available.
- In expanded mode, each area grows to full height and triggers dashboard reflow so lower widgets are pushed down.
- Empty assignment group values are labeled as `No Assignment Group` in group stats.

### Dashboard Template (In Module)

For sharing/documentation, a full dashboard layout template is included in-module:

- `local/modules/simplemdm/examples/dashboard.simplemdm.full.yml`

Auto-install behavior:

- On module migration, the template is copied to:
  - `local/dashboards/simplemdm_full.yml`
- It is only copied if missing (existing dashboard files are never overwritten).
- `local/dashboards/default.yml` is not modified by this module.
- This keeps the module portable across MunkiReport instances without forcing dashboard changes.

Manual copy (optional):

```bash
cp local/modules/simplemdm/examples/dashboard.simplemdm.full.yml local/dashboards/simplemdm_full.yml
```

Then open:

- `show/dashboard/simplemdm_full`

If you want SimpleMDM as your main dashboard, update your own dashboard YAML manually (for example `local/dashboards/default.yml`) or select `SimpleMDM Full` in the dashboard switcher.

## Theme/Mode Integration Details

Widgets are theme-aware and layout-aware by design:

- Reads active theme/mode from body/html markers (`data-theme`, `data-bs-theme`, `data-color-mode`, and common dark classes).
- Reads layout density from `data-layout-mode` or layout classes (`layout-compact`, `layout-comfortable`), then falls back to width-based auto detection.
- Applies runtime attributes:
  - `data-simplemdm-theme`
  - `data-simplemdm-theme-name`
  - `data-simplemdm-layout`
- Emits `simplemdm:modechange` when theme/layout changes are detected so charts can rerender with correct axis/text/palette colors.
- Triggers repeated post-render grid reflow on supported pages so async-loaded widget content settles into balanced columns.

Expected behavior:
- Switching Bootswatch theme (for example Cerulean to Darkly) updates widget accent + chart colors.
- Switching layout mode updates spacing/typography/card density.
- Dark themes keep axis labels and legends readable.
- Featured widgets (`resource_types`, `groups`, `devices_table`, `group_top`) render as full-width rows for visibility.

## API/Endpoint Use

### Ingest endpoints (used by sync/webhooks)

- `POST /index.php?/module/simplemdm/index?op=ingest`
  - Device payload batch ingest.
- `POST /index.php?/module/simplemdm/index?op=ingest_resources`
  - API resource payload batch ingest.
- `POST /index.php?/module/simplemdm/index?op=ingest_commands`
  - Command payload batch ingest.
- `POST /index.php?/module/simplemdm/index?op=webhook`
  - Webhook event ingest with secret/API-key auth.
- `POST /index.php?/module/simplemdm/index?op=update_sync_status`
  - Sync status + telemetry updates from sync script.

### Read endpoints (widgets/report/listings)

- `GET /index.php?/module/simplemdm/get_dashboard_trend`
- `GET /index.php?/module/simplemdm/get_os_security_stats`
- `GET /index.php?/module/simplemdm/get_command_status_stats`
- `GET /index.php?/module/simplemdm/get_compliance_stats`
- `GET /index.php?/module/simplemdm/get_sync_telemetry`
- `GET /index.php?/module/simplemdm/get_resource_type_stats`
- `GET /index.php?/module/simplemdm/get_resource_type_count/{type}`
- `GET /index.php?/module/simplemdm/get_device_subresources/{serial_number}`

### Device API passthrough endpoints

The module also exposes authenticated passthrough routes to the SimpleMDM device API so admins can query and invoke device actions from MunkiReport without exposing the SimpleMDM API key to browsers/clients.

Auth requirement:
- Global MunkiReport admin session (`authorized('global')`).
- Mutating methods (`POST/PATCH/DELETE/PUT`) additionally require `X-SIMPLEMDM-ACTION-SECRET` matching `action_api_secret` in module admin settings.

Coverage:
- Device CRUD/list:
  - `GET /index.php?/module/simplemdm/api_devices`
  - `GET /index.php?/module/simplemdm/api_devices/{device_id}`
  - `POST /index.php?/module/simplemdm/api_devices`
  - `PATCH /index.php?/module/simplemdm/api_devices/{device_id}`
  - `DELETE /index.php?/module/simplemdm/api_devices/{device_id}`
- Device related lists:
  - `GET /index.php?/module/simplemdm/api_devices/{device_id}/profiles`
  - `GET /index.php?/module/simplemdm/api_devices/{device_id}/installed_apps`
  - `GET /index.php?/module/simplemdm/api_devices/{device_id}/users`
- Device user action:
  - `DELETE /index.php?/module/simplemdm/api_devices/{device_id}/users/{user_id}`
- Device actions:
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/push_apps`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/refresh`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/restart`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/shutdown`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/lock`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/clear_passcode`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/clear_firmware_password`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/rotate_firmware_password`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/clear_recovery_lock_password`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/clear_restrictions_password`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/rotate_recovery_lock_password`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/rotate_filevault_key`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/set_admin_password`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/rotate_admin_password`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/wipe`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/update_os`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/remote_desktop` (enable)
  - `DELETE /index.php?/module/simplemdm/api_devices/{device_id}/remote_desktop` (disable)
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/bluetooth` (enable)
  - `DELETE /index.php?/module/simplemdm/api_devices/{device_id}/bluetooth` (disable)
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/set_time_zone`
  - `POST /index.php?/module/simplemdm/api_devices/{device_id}/unenroll`

Query/body passthrough:
- Query parameters and request body are passed through to SimpleMDM.
- JSON and `application/x-www-form-urlencoded` payloads are supported.
- Security parameter `action_secret` is accepted for validation but stripped before upstream passthrough.

Notes:
- The module continues storing a curated flat subset in `simplemdm` plus full raw device payload in `attributes_json` and `relationships_json`.
- Per-device subresource sync (`devices/{id}/profiles`, `installed_apps`, `users`) can be enabled in sync script to persist these records in `simplemdm_resource`.
- Device detail page includes:
  - Synced per-device subresource tables (installed apps, users, profiles)
  - Action runner UI for supported device action routes (uses action secret header)

## Validation Checklist

After rollout, verify in this order:

1. Migrations applied with no errors.
2. Admin config saved and API key present.
3. Manual sync returns success.
4. Device and resource listings populate.
5. New widgets render data:
   - Trend, OS Security, Group Top, Resource Mix
   - Command Status, Compliance, Sync Health
6. Theme switch (light/dark and different Bootswatch theme) updates widget/chart styling.
7. Webhook test event is accepted and stored.
8. Scheduled cron runs update `last_sync_time` and telemetry counters.

## Data Model Notes

- `simplemdm_resource` uniqueness: `(resource_type, resource_id, source_endpoint)`
  - Prevents deep resource overwrite across different nested endpoints.
- `simplemdm_dashboard_snapshot` stores historical dashboard metrics captured on successful sync status updates (`last_sync_status=success`).
- `simplemdm_device_history` captures one row per device per day (status, OS, group, supervision, DEP, FileVault).
- `simplemdm_relationship_edge` stores normalized graph edges from device/resource relationship payloads.
- `simplemdm_command` stores command status records from sync script and webhook ingestion.
- `simplemdm_webhook_event` stores raw webhook envelope/payload for audit and replay diagnostics.
- Additional indexes exist for listing filters:
  - `resource_id`
  - `(resource_type, resource_id)`

## Troubleshooting

### Listing links open 404 / “Invalid method name: listing”

Use rewrite-safe URLs with `index.php?` in non-rewrite environments.
The module widgets already handle this fallback.

### Admin save hangs or does not complete

Check browser console/network and confirm module route resolves:
`/index.php?/module/simplemdm/save_config`

### Sync says success but no data in UI

- Confirm API key saved.
- Run manual sync with `--verbose`.
- Check `Admin -> SimpleMDM Settings` for `last_sync_status` and `last_sync_time`.
- Confirm `simplemdm` and `simplemdm_resource` rows exist.

### Widget is enabled but not visible

- `Widget Visibility` controls whether a widget may render.
- Dashboard/report pages only show widgets that exist in that page layout.
- Confirm the widget is present in your active dashboard YAML (`local/dashboards/*.yml`) or on the SimpleMDM report page.
- If needed, click `Reset Layout` to clear stale per-page localStorage layout state (report reset does not overwrite dashboard defaults, and dashboard reset does not overwrite report defaults).

### Command status widget is empty

- Enable `sync_commands_enabled` in admin advanced settings, or run script with `--sync-commands`.
- Script now attempts tenant-wide `commands` first, then falls back to per-device `devices/{id}/commands`.
- If both are unavailable in your tenant/API, command status data cannot be collected and widget remains empty.

### Trend widget shows only one day / no history

- Ensure migration for `simplemdm_dashboard_snapshot` has run.
- Ensure sync updates `last_sync_status` to `success` (snapshots are recorded on success status updates).
- Run at least 2 successful sync cycles across different times/days.

### Webhook route returns Unauthorized

- Configure `webhook_secret` in module admin advanced settings.
- Send header `X-SIMPLEMDM-WEBHOOK-SECRET: <secret>`.
- Or use `X-SIMPLEMDM-API-KEY` with stored module API key.

### Device action returns “Invalid or missing action secret”

- Set `action_api_secret` in `Admin -> SimpleMDM Settings -> Advanced Sync & Compliance`.
- For device detail action runner, ensure the same secret is entered in the `Action Secret` field.
- For API calls, send header `X-SIMPLEMDM-ACTION-SECRET`.
- Mutating methods (`POST/PATCH/DELETE/PUT`) require this secret even for global admins.

### Delta sync appears to do full sync

- Some endpoints may not support delta filter parameters.
- Script automatically falls back to full sync for unsupported endpoints and reports scope/telemetry.

### Theme switches but widget colors do not change

- Refresh the dashboard after switching theme/mode.
- If styles still appear stale, clear browser cache and reload.
- Verify dashboard theme actually changed in MunkiReport.
- Inspect `<body>` attributes/classes and confirm `data-simplemdm-theme` / `data-simplemdm-theme-name` update.
- Confirm no custom CSS overrides `--simplemdm-*` variables or NVD3 SVG text styles.

### Sync is too slow

Use `--max-parent-resources` for frequent runs and run full deep sync less often.

### Sync health widget has stale values

- Ensure sync script posts `op=update_sync_status` successfully.
- Confirm latest sync did not run with `--dry-run`.
- Check that scheduled job is using current script path.

### Compliance widget does not match expected baseline

- Confirm `compliance_min_os` is set in module settings.
- Check OS version formatting in source device payloads.
- Re-run a full sync after baseline changes.

### Webhook accepted but no visible UI change

- Webhook may affect command/device state not currently visible in active filters.
- Run an API sync cycle to reconcile full state after webhook events.
- Check command/compliance widgets and device detail page for updates.

## Files Added/Updated by This Module

- Controllers/models/views under `local/modules/simplemdm/`
- Migrations under `local/modules/simplemdm/migrations/`
- No required permanent changes in MunkiReport core files.

## License

MIT

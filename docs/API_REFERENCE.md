# API Reference

This reference documents module routes, expected auth, and common usage patterns.

Base path examples use MunkiReport's default front-controller format:
- `/index.php?/module/simplemdm/...`

If your MunkiReport deployment uses URL rewriting, the same routes may also resolve without `index.php?`, for example:
- `/module/simplemdm/...`

Use whichever form matches your configured MunkiReport `index_page`.

## 1) Auth Summary

Workflow note:
- `simplemdm_sync.py` is the sync worker.
- `Sync Status -> Queue Sync Request` is a queue-based trigger path.
- `In-Module Sync And Schedule -> Run Sync Now` is an immediate execution path when module-side execution is available.
- `simplemdm_sync_run` is the source of truth for queued, running, and completed run history.
- `clear_sync_runs` clears run history only when no sync is queued or running.
- recurring schedule still requires cron to launch `simplemdm_sync.py`
- host/manual runners should use an explicit `--api-key` or `SIMPLEMDM_API_KEY`
- `install_cron.sh` and `remove_cron.sh` are helpers for managing that cron entry
- when module-side execution is enabled, the admin UI can call those helpers for global admins
- `Runner MunkiReport URL` prefers configured app URL (`WEBHOST` / `SUBDIRECTORY`) and falls back to the current browser URL for local/placeholder setups
- Command sync uses the tenant-wide `/commands` collection only. If `/commands` is unavailable for the tenant/API version, the worker skips command sync instead of probing per-device command routes.

| Route Group | Auth |
|---|---|
| Report/listing/stats/data routes | Authenticated MunkiReport session |
| Config read (`get_config`) | Global admin session OR sync token header (non-global/sync-auth responses get masked secret flags) |
| Config write (`save_config`) | Global admin session OR sync token header |
| Admin sync queue (`request_sync`) | Global admin session |
| Ingest routes (`op=ingest*`, `op=update_sync_status`, `op=begin_sync_run`) | Sync token header |
| Webhook (`op=webhook`) | Webhook secret header OR sync token header |
| Device passthrough (`api_devices`) | Global admin session; mutating methods also require action secret |

Headers used by module:
- Sync token: `X-SIMPLEMDM-API-KEY`
- Webhook secret: `X-SIMPLEMDM-WEBHOOK-SECRET` (also accepts `X-WEBHOOK-SECRET`)
- Action secret (preferred): `X-SIMPLEMDM-ACTION-SECRET`

## 2) Ingest and Sync Status Endpoints

All are called via:
- `POST /index.php?/module/simplemdm/index?op=<operation>`

| Operation | Method | Purpose | Auth |
|---|---|---|---|
| `begin_sync_run` | POST | Claim a queued/scheduled sync run and mark it running | Sync token |
| `ingest` | POST | Upsert device rows into `simplemdm` via processor | Sync token |
| `ingest_resources` | POST | Upsert non-device resources into `simplemdm_resource` | Sync token |
| `ingest_commands` | POST | Upsert command status rows into `simplemdm_command` | Sync token |
| `update_sync_status` | POST | Update sync status and telemetry fields in `simplemdm_config` | Sync token |
| `webhook` | POST | Store webhook event; best-effort device/command updates | Webhook secret OR sync token |

## 3) Config Endpoints

| Route | Method | Purpose | Auth |
|---|---|---|---|
| `/module/simplemdm/get_config` | GET | Read module settings | Global admin session OR sync token header |
| `/module/simplemdm/index?op=get_config` | GET | Worker-friendly config bootstrap route | Global admin session OR sync token header |
| `/module/simplemdm/get_script_catalog` | GET | Read downloadable script metadata and external command templates | Global admin |
| `/module/simplemdm/get_runner_status` | GET | Read module runtime, cron, and runner readiness state | Global admin |
| `/module/simplemdm/save_config` | POST | Save module settings | Global admin OR sync token |
| `/module/simplemdm/request_sync` | POST | Queue a sync run from the admin UI | Global admin |
| `/module/simplemdm/run_script` | POST | Execute an approved module-side script action | Global admin and script runner enabled |
| `/module/simplemdm/download_script/{name}` | GET | Download an individual module script | Global admin |
| `/module/simplemdm/download_module` | GET | Download the module as a zip archive | Global admin |

`save_config` supports keys including:
- `api_key`, `webhook_secret`, `action_api_secret`
- `compliance_min_os`
- `enable_scheduled_sync`
- `sync_interval_minutes`
- `sync_delta_enabled`
- `sync_commands_enabled`
- `sync_device_subresources_enabled`
- `device_subresource_limit`
- `allow_module_script_execution`
- `script_runner_munkireport_url`
- `script_runner_python_bin`
- `script_runner_schedule`
- `script_runner_log_path`
- `script_runner_max_parent_resources`
- sync queue keys (`sync_request_state`, `sync_requested_at`, `sync_started_at`, `sync_request_source`)
- telemetry/status keys (`last_sync_status`, `last_sync_time`, `last_sync_cursor`, etc.)
- widget visibility config keys discovered from `provides.yml`

`get_config` returns full secret values only to global admins. Sync-token-auth callers receive masked secret behavior (`api_key_set`, `webhook_secret_set`, `action_api_secret_set`) plus the non-secret runner/schedule settings needed by the worker.

`request_sync` only queues the request. A host-side or manual `simplemdm_sync.py` run still claims and executes the sync.

`begin_sync_run` is a worker-only claim endpoint used by the sync script. It is not meant to be called from the browser UI.

`run_script` is used by the schedule panel for immediate module-side execution and module-managed cron helper actions.

UI note:
- `Schedule Config` is derived from `enable_scheduled_sync`
- `Recurring Sync Ready` depends on both config and cron/runtime state
- `Last Run Source` is derived from `sync_request_source`
It now enforces the saved runner prerequisites server-side as well, so `sync_now` and `install_cron` require a configured runner plus verified module-runtime Python.

## 4) Listing and Data Endpoints

| Route | Method | Purpose |
|---|---|---|
| `/module/simplemdm/get_data` | GET | Device listing data feed |
| `/module/simplemdm/resources` | GET | Resource listing page entry point |
| `/module/simplemdm/get_resources_data` | GET | Resource listing data feed |
| `/module/simplemdm/get_simplemdm_data/{serial}` | GET | Device row detail data |
| `/module/simplemdm/get_device_resources/{serial}` | GET | Connected/derived resource mapping for device |
| `/module/simplemdm/get_device_subresources/{serial}` | GET | Synced per-device subresource tables |

## 5) Stats/Widget Endpoints

| Route | Method | Purpose |
|---|---|---|
| `/module/simplemdm/get_enrollment_stats` | GET | Enrolled vs unenrolled |
| `/module/simplemdm/get_dep_stats` | GET | DEP enrolled breakdown |
| `/module/simplemdm/get_filevault_stats` | GET | FileVault enabled/disabled |
| `/module/simplemdm/get_supervised_stats` | GET | Supervised/unsupervised |
| `/module/simplemdm/get_assignment_group_stats` | GET | Assignment group distribution |
| `/module/simplemdm/get_resource_type_stats` | GET | Resource type breakdown |
| `/module/simplemdm/get_resource_type_count/{type}` | GET | Single resource type count |
| `/module/simplemdm/get_command_status_stats` | GET | Command status distribution |
| `/module/simplemdm/get_compliance_stats` | GET | Compliance breakdown |
| `/module/simplemdm/get_sync_telemetry` | GET | Last sync telemetry |
| `/module/simplemdm/get_dashboard_trend` | GET | Snapshot trend data |
| `/module/simplemdm/get_os_security_stats` | GET | OS/security aggregate stats |

## 6) UI Page Routes

| Route | Method | Purpose |
|---|---|---|
| `/module/simplemdm/admin` | GET | Admin settings page |
| `/module/simplemdm/device/{serial}` | GET | Standalone device detail page |
| `/show/listing/simplemdm/simplemdm` | GET | Device listing page |
| `/show/listing/simplemdm/simplemdm_resources` | GET | Resource listing page |
| `/show/report/simplemdm/simplemdm` | GET | Module report page |

The admin page now exposes both modes:
- outside the module: copy/download the scripts and run them on the host
- within the module: run approved actions from the UI when `allow_module_script_execution=1`

Sync API scope note:
- the sync script is aligned to documented SimpleMDM GET endpoints only
- undocumented collection probes are intentionally excluded so `sync_last_api_errors` reflects real failures

## 7) Upstream SimpleMDM API Endpoints Used

Base upstream API:
- `https://a.simplemdm.com/api/v1`

The module currently uses these upstream SimpleMDM endpoints directly.

### 7.1 Sync Worker Collection Endpoints

These are fetched by `scripts/simplemdm_sync.py` when available for the tenant/API version:
- `GET /devices`
- `GET /device_groups`
- `GET /assignment_groups`
- `GET /profiles`
- `GET /apps`
- `GET /custom_attributes`
- `GET /scripts`
- `GET /enrollments`
- `GET /commands`

Notes:
- Collection fetches are paginated with `limit=100` and `starting_after=<id>`.
- Delta mode appends the saved cursor when supported by the upstream endpoint.
- If an endpoint returns `404`, the worker treats it as unsupported for that tenant/API version and skips it.
- Command sync uses tenant-wide `GET /commands` only. If it is unavailable, command sync is skipped.

### 7.2 Nested Resource Endpoints

These are probed and fetched opportunistically:
- `GET /apps/{id}/installs`
- `GET /apps/{id}/managed_configs`

### 7.3 Per-Device Deep Sync Endpoints

These are used only when `sync_device_subresources_enabled=1` or `--sync-device-subresources` is set:
- `GET /devices/{id}/profiles`
- `GET /devices/{id}/installed_apps`
- `GET /devices/{id}/users`

### 7.4 Upstream Passthrough Endpoints Exposed Via `api_devices`

These routes are proxied by the module controller to upstream `/devices` endpoints.

Collection/device:
- `GET /devices`
- `POST /devices`
- `GET /devices/{id}`
- `PATCH /devices/{id}`
- `DELETE /devices/{id}`

Read-only subresources:
- `GET /devices/{id}/profiles`
- `GET /devices/{id}/installed_apps`
- `GET /devices/{id}/users`

Mutating subresources/actions:
- `DELETE /devices/{id}/users/{user_id}`
- `POST /devices/{id}/push_apps`
- `POST /devices/{id}/refresh`
- `POST /devices/{id}/restart`
- `POST /devices/{id}/shutdown`
- `POST /devices/{id}/lock`
- `POST /devices/{id}/clear_passcode`
- `POST /devices/{id}/clear_firmware_password`
- `POST /devices/{id}/rotate_firmware_password`
- `POST /devices/{id}/clear_recovery_lock_password`
- `POST /devices/{id}/clear_restrictions_password`
- `POST /devices/{id}/rotate_recovery_lock_password`
- `POST /devices/{id}/rotate_filevault_key`
- `POST /devices/{id}/set_admin_password`
- `POST /devices/{id}/rotate_admin_password`
- `POST /devices/{id}/wipe`
- `POST /devices/{id}/update_os`
- `POST /devices/{id}/set_time_zone`
- `POST /devices/{id}/unenroll`
- `POST /devices/{id}/remote_desktop`
- `DELETE /devices/{id}/remote_desktop`
- `POST /devices/{id}/bluetooth`
- `DELETE /devices/{id}/bluetooth`

## 8) Webhook Coverage

Module webhook route:
- `POST /module/simplemdm/index?op=webhook`

Auth:
- `X-SIMPLEMDM-WEBHOOK-SECRET`
- or sync token fallback: `X-SIMPLEMDM-API-KEY`

Accepted top-level event keys:
- `type`
- `event_type`
- `event`

Stored for every accepted webhook:
- raw payload in `simplemdm_webhook_event.payload_json`
- event id from `id` when present, otherwise a hash-derived anonymous id
- event type when present
- source IP and receipt timestamp

Best-effort device upsert is attempted when webhook payload attributes include any of:
- `serial_number`
- `device_name`
- `status`

Best-effort command upsert is attempted when either condition matches:
- event type contains `command` case-insensitively
- payload data includes `command_uuid`

Command webhook fields recognized by the controller:
- `command_uuid`, `uuid`, or `id` for command identity
- `device_id`
- `command_type` or `type`
- `status`
- `resource_id`
- `error`
- `created_at`
- `completed_at`
- `updated_at`

Current webhook behavior boundaries:
- webhook ingestion always acknowledges success after auth and JSON parsing, even if best-effort device/command parsing partially fails
- webhook handling is additive and partial; it does not replace a full sync
- webhook docs here describe what the module currently recognizes, not every webhook event type SimpleMDM may emit upstream

## 9) Widget and Data View API Matrix

This section maps dashboard widgets and data views to the module API commands they call and the synced tables they depend on.

### 9.1 Dashboard Widgets

| Widget ID | Primary API command(s) | Backing data | Upstream dependency |
|---|---|---|---|
| `simplemdm_enrollment` | `GET /module/simplemdm/get_enrollment_stats` | `simplemdm` device rows | `GET /devices` |
| `simplemdm_dep` | `GET /module/simplemdm/get_dep_stats` | `simplemdm.is_dep_enrollment` | `GET /devices` |
| `simplemdm_filevault` | `GET /module/simplemdm/get_filevault_stats` | `simplemdm.filevault_enabled` | `GET /devices` |
| `simplemdm_supervised` | `GET /module/simplemdm/get_supervised_stats` | `simplemdm.is_supervised` | `GET /devices` |
| `simplemdm_group` | `GET /module/simplemdm/get_assignment_group_stats` | `simplemdm.assignment_group` | `GET /devices` and assignment-group data present in synced device payloads |
| `simplemdm_group_top` | `GET /module/simplemdm/get_assignment_group_stats` | `simplemdm.assignment_group` | `GET /devices` and assignment-group data present in synced device payloads |
| `simplemdm_resource_types` | `GET /module/simplemdm/get_resource_type_stats` | `simplemdm_resource` | resource endpoint sync such as `GET /device_groups`, `GET /assignment_groups`, `GET /profiles`, `GET /apps`, `GET /custom_attributes`, `GET /scripts`, `GET /enrollments`, plus nested resources when available |
| `simplemdm_resource_mix` | `GET /module/simplemdm/get_resource_type_stats` | `simplemdm_resource` | same as `simplemdm_resource_types` |
| `simplemdm_trend` | `GET /module/simplemdm/get_dashboard_trend?days=30` | `simplemdm_dashboard_snapshot` | successful sync runs that recorded snapshots |
| `simplemdm_os_security` | `GET /module/simplemdm/get_os_security_stats` | `simplemdm` device rows | `GET /devices` |
| `simplemdm_command_status` | `GET /module/simplemdm/get_command_status_stats` | `simplemdm_command` | `GET /commands` and/or command-related webhook upserts |
| `simplemdm_compliance` | `GET /module/simplemdm/get_compliance_stats` | `simplemdm.os_version` plus `simplemdm_config.compliance_min_os` | `GET /devices` |
| `simplemdm_sync_health` | `GET /module/simplemdm/get_sync_telemetry` | `simplemdm_config` sync telemetry fields and `simplemdm_sync_run` metadata | completed sync runs and `update_sync_status` posts |
| `simplemdm_device_listing` | `GET /module/simplemdm/get_enrollment_stats`, `GET /module/simplemdm/get_dep_stats`, `GET /module/simplemdm/get_supervised_stats`, `GET /module/simplemdm/get_filevault_stats` | `simplemdm` device rows | `GET /devices` |
| `simplemdm_devices_table` | `GET /module/simplemdm/get_data` | `simplemdm` device rows | `GET /devices` |
| `simplemdm_resources_listing` | `GET /module/simplemdm/get_resource_type_stats` | `simplemdm_resource` | resource endpoint sync |

### 9.2 Per-Resource-Type Widget Family

These widgets all share the same API command and differ only by the resource type they highlight:
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

Shared API command:
- `GET /module/simplemdm/get_resource_type_stats`

Backing data:
- `simplemdm_resource`

Upstream dependency:
- resource sync endpoints and nested resource sync where the specific resource type is produced

### 9.3 Detail, Tab, and Listing Views

| View | Primary API command(s) | Backing data | Upstream dependency |
|---|---|---|---|
| detail widget `simplemdm_detail` | `GET /module/simplemdm/get_simplemdm_data/{serial}` | `simplemdm` | `GET /devices` |
| client tab `simplemdm-tab` | `GET /module/simplemdm/get_simplemdm_data/{serial}`, `GET /module/simplemdm/get_device_resources/{serial}` | `simplemdm`, `simplemdm_resource`, `simplemdm_relationship_edge` | `GET /devices` plus resource sync |
| standalone device page `/module/simplemdm/device/{serial}` | `GET /module/simplemdm/get_simplemdm_data/{serial}`, `GET /module/simplemdm/get_device_resources/{serial}`, `GET /module/simplemdm/get_device_subresources/{serial}` | `simplemdm`, `simplemdm_resource`, `simplemdm_relationship_edge` | `GET /devices`, resource sync, and optional `GET /devices/{id}/profiles`, `GET /devices/{id}/installed_apps`, `GET /devices/{id}/users` |
| device listing page `/show/listing/simplemdm/simplemdm` | `GET /module/simplemdm/get_data` | `simplemdm` | `GET /devices` |
| resource listing page `/show/listing/simplemdm/simplemdm_resources` | `GET /module/simplemdm/get_resources_data` | `simplemdm_resource` | resource endpoint sync |

Notes:
- `get_data` is the main API command for device-table style views and dashboard mini-tables.
- `get_resources_data` is the main API command for resource listing pages.
- `get_device_resources/{serial}` relies on normalized relationships built during ingest into `simplemdm_relationship_edge`.
- `get_device_subresources/{serial}` only returns meaningful data when per-device deep sync is enabled.

## 10) Device Passthrough API (`api_devices`)

Base:
- `/module/simplemdm/api_devices`

Examples:
- `GET /module/simplemdm/api_devices`
- `GET /module/simplemdm/api_devices/{id}`
- `POST /module/simplemdm/api_devices/{id}/restart`
- `DELETE /module/simplemdm/api_devices/{id}/users/{user_id}`

Rules:
1. Global admin session required for all calls.
2. Method/path must be in controller allowlist.
3. Mutating methods (`POST`, `PATCH`, `PUT`, `DELETE`) require valid action secret.
4. `action_secret` is stripped before forwarding upstream.

Allowed subpaths (high level):
- Read:
  - `profiles`, `installed_apps`, `users` (`GET`)
- User deletion:
  - `users/{id}` (`DELETE`)
- Actions (`POST` unless noted):
  - `push_apps`, `refresh`, `restart`, `shutdown`, `lock`
  - `clear_passcode`, `clear_firmware_password`, `rotate_firmware_password`
  - `clear_recovery_lock_password`, `clear_restrictions_password`
  - `rotate_recovery_lock_password`, `rotate_filevault_key`
  - `set_admin_password`, `rotate_admin_password`, `wipe`, `update_os`, `set_time_zone`, `unenroll`
  - `remote_desktop` (`POST`, `DELETE`)
  - `bluetooth` (`POST`, `DELETE`)

## 11) Request Examples

## Ingest devices

```bash
curl -X POST "https://<mr>/index.php?/module/simplemdm/index?op=ingest" \
  -H "Content-Type: application/json" \
  -H "X-SIMPLEMDM-API-KEY: <api_key>" \
  -d '[{"serial_number":"C02ABC123","device_name":"MacBook-01"}]'
```

## Webhook

```bash
curl -X POST "https://<mr>/index.php?/module/simplemdm/index?op=webhook" \
  -H "Content-Type: application/json" \
  -H "X-SIMPLEMDM-WEBHOOK-SECRET: <webhook_secret>" \
  -d '{"id":"evt-1","type":"device.updated","data":{"attributes":{"serial_number":"C02ABC123"}}}'
```

## Mutating device action

```bash
curl -X POST "https://<mr>/index.php?/module/simplemdm/api_devices/12345/restart" \
  -H "X-SIMPLEMDM-ACTION-SECRET: <action_secret>"
```

## 12) Error Patterns

Common error payloads:
- `401 Unauthorized`:
  - missing/invalid sync token, webhook secret, or action secret
- `405 Method/path not allowed for device passthrough`:
  - disallowed method/subpath combination
- `400 Invalid JSON data`:
  - malformed payload on ingest/webhook operations
- generic `{"error":"Something failed, turn on DEBUG for more information."}`:
  - indicates a server-side controller/database error; check PHP/app logs and confirm the module is upgraded and migrated cleanly

# API Reference

This reference documents module routes, expected auth, and common usage patterns.

Base path examples use MunkiReport default front controller format:
- `/index.php?/module/simplemdm/...`

## 1) Auth Summary

| Route Group | Auth |
|---|---|
| Report/listing/stats/data routes | Authenticated MunkiReport session |
| Config read (`get_config`) | Auth session (global gets full values; non-global gets masked secret flags) |
| Config write (`save_config`) | Global admin session OR sync token header |
| Ingest routes (`op=ingest*`, `op=update_sync_status`) | Sync token header |
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
| `ingest` | POST | Upsert device rows into `simplemdm` via processor | Sync token |
| `ingest_resources` | POST | Upsert non-device resources into `simplemdm_resource` | Sync token |
| `ingest_commands` | POST | Upsert command status rows into `simplemdm_command` | Sync token |
| `update_sync_status` | POST | Update sync status and telemetry fields in `simplemdm_config` | Sync token |
| `webhook` | POST | Store webhook event; best-effort device/command updates | Webhook secret OR sync token |

## 3) Config Endpoints

| Route | Method | Purpose | Auth |
|---|---|---|---|
| `/module/simplemdm/get_config` | GET | Read module settings | Auth session |
| `/module/simplemdm/save_config` | POST | Save module settings | Global admin OR sync token |

`save_config` supports keys including:
- `api_key`, `webhook_secret`, `action_api_secret`
- `compliance_min_os`
- `enable_scheduled_sync`
- `sync_interval_minutes`
- `sync_delta_enabled`
- `sync_commands_enabled`
- `sync_device_subresources_enabled`
- `device_subresource_limit`
- telemetry/status keys (`last_sync_status`, `last_sync_time`, `last_sync_cursor`, etc.)
- widget visibility config keys discovered from `provides.yml`

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

## 7) Device Passthrough API (`api_devices`)

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

## 8) Request Examples

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

## 9) Error Patterns

Common error payloads:
- `401 Unauthorized`:
  - missing/invalid sync token, webhook secret, or action secret
- `405 Method/path not allowed for device passthrough`:
  - disallowed method/subpath combination
- `400 Invalid JSON data`:
  - malformed payload on ingest/webhook operations

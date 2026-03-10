# Testing Guide

This guide provides practical validation steps for local development, QA, and release checks.

## 1) Test Scope

Recommended minimum scope before merge/release:

1. Setup and migration health
2. Sync and ingest path validation
3. Widget/report/listing rendering checks
4. Device detail and action runner checks
5. Security/auth negative tests

## 2) Environment Prerequisites

1. Module enabled in `MODULES`.
2. Migrations applied:

```bash
php please migrate
```

3. `api_key` configured in `Admin -> SimpleMDM Settings`.
4. For webhook tests: `webhook_secret` configured.
5. For mutating action tests: `action_api_secret` configured.
6. If validating scheduled sync behavior, add a real cron entry or install one with `local/modules/simplemdm/scripts/install_cron.sh --munkireport-url '<url>' --install`.

## 3) Hosted / VM Smoke Test

Run from MunkiReport root.

1. Manual sync:

```bash
python3 local/modules/simplemdm/scripts/simplemdm_sync.py \
  --munkireport-url 'http://127.0.0.1' \
  --respect-schedule \
  --force-run \
  --verbose
```

2. Validate UI:
   - `show/report/simplemdm/simplemdm`
   - `show/listing/simplemdm/simplemdm`
   - `show/listing/simplemdm/simplemdm_resources`
   - `Top Assignment Groups` and `Enrollment/Security by OS` widgets render data if enabled
3. Validate admin telemetry:
   - `last_sync_status = success`
   - `last_sync_time` recent
4. Open one device:
   - `module/simplemdm/device/{serial}`
   - confirm overview + attributes + connected resources render

## 4) Docker Smoke Test

Run from MunkiReport root.
If you are not in repo root, switch first:

```bash
cd "$(git rev-parse --show-toplevel)"
```

1. Ensure containers up:

```bash
docker compose up -d --build
docker compose exec munkireport php please migrate
```

If you want to validate queued `Sync Now` behavior, install a real cron entry first or use:

```bash
local/modules/simplemdm/scripts/install_cron.sh --munkireport-url 'http://localhost:8888' --install
```

2. Manual sync from host:

```bash
python3 local/modules/simplemdm/scripts/simplemdm_sync.py \
  --munkireport-url 'http://localhost:8888' \
  --respect-schedule \
  --force-run \
  --verbose
```

3. Validate in browser:
   - `http://localhost:8888/show/report/simplemdm/simplemdm`
   - `http://localhost:8888/show/listing/simplemdm/simplemdm`
   - `http://localhost:8888/show/listing/simplemdm/simplemdm_resources`
   - `Top Assignment Groups` and `Enrollment/Security by OS` widgets render data if enabled

4. Queued Sync Now smoke test:
   - Open `Admin -> SimpleMDM Settings`
   - Click `Sync Now`
   - Confirm `Queue State = queued`
   - Wait for cron pickup or run:

```bash
python3 local/modules/simplemdm/scripts/simplemdm_sync.py \
  --munkireport-url 'http://localhost:8888' \
  --respect-schedule \
  --force-run \
  --verbose
```

   - Confirm `Queue State` moves through `running` to `idle`
   - Confirm `Last Sync Time` updates

## 5) API/Auth Negative Tests

## Ingest should reject missing sync token

```bash
curl -i -X POST "http://localhost:8888/index.php?/module/simplemdm/index?op=ingest" \
  -H "Content-Type: application/json" \
  -d '[]'
```

Expected:
- HTTP 401

## Webhook should reject invalid secret/token

```bash
curl -i -X POST "http://localhost:8888/index.php?/module/simplemdm/index?op=webhook" \
  -H "Content-Type: application/json" \
  -d '{}'
```

Expected:
- HTTP 401

## Mutating action should reject missing action secret

```bash
curl -i -X POST "http://localhost:8888/index.php?/module/simplemdm/api_devices/12345/restart"
```

Expected:
- HTTP 401
- message about invalid/missing action secret

## 6) Functional Test Matrix

| Area | Test | Expected |
|---|---|---|
| Config | Save API key/secrets/toggles | Save success, values persist |
| Sync | Device ingest | Device table count increases/updates |
| Sync | Resource ingest | Resource listing populated |
| Sync | Commands ingest (if enabled) | Command status widget shows data |
| Widgets | Assignment groups | `get_assignment_group_stats` returns JSON and group widgets render |
| Widgets | OS security | `get_os_security_stats` returns JSON and widget renders |
| Webhook | Test event ingestion | `simplemdm_webhook_event` receives record |
| Report | Core widgets render | no JS/API errors |
| Listing | Device filters | filtered results correct |
| Listing | Resource type/endpoint filters | filtered results correct |
| Device page | Connected resources | per-type rows visible |
| Device page | Subresources | apps/users/profiles rows visible (if enabled) |
| Device actions | Safe action (`refresh`) | request accepted with valid secret |

## 7) Regression Focus for UI Changes

When changing views/assets:

1. Check report page and dashboard page behavior separately.
2. Verify widget ordering/collapse behavior on refresh.
3. Validate listing tables on both narrow and wide viewport widths.
4. Validate device detail sections:
   - overview
   - attributes
   - relationships
   - connected resources
   - synced device subresources
   - actions panel

Useful visual references:
- `docs/images/dashboard-overview-part1.png`
- `docs/images/dashboard-overview-part2.png`
- `docs/images/device-detail-overview.png`
- `docs/images/device-actions-runner.png`

## 8) Data/Schema Validation

After migration/sync:

1. Confirm expected tables exist:
   - `simplemdm`
   - `simplemdm_config`
   - `simplemdm_resource`
   - `simplemdm_command`
   - `simplemdm_webhook_event`
   - `simplemdm_relationship_edge`
   - `simplemdm_dashboard_snapshot`
   - `simplemdm_device_history`
2. Confirm indexes/uniqueness work as expected for resource lookup patterns.
3. Confirm no migration errors in logs.

## 9) Release Sign-Off Checklist

1. Hosted smoke test passed.
2. Docker smoke test passed.
3. Auth negative tests passed.
4. No blocking UI regressions.
5. README/docs updated for new options/routes/widgets.

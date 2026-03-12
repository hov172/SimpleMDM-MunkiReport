# Testing Guide

This guide provides practical validation steps for local development, QA, and release checks.

For step-by-step Option B client deployment examples, see:
- `docs/CLIENT_REPORTER_DEPLOYMENT.md`

## 1) Test Scope

Recommended minimum scope before merge/release:

1. Setup and migration health
2. Sync and ingest path validation
3. Widget/report/listing rendering checks
4. Device detail and action runner checks
5. Supplemental Option A checks
6. Option B client-reporter checks
7. Security/auth negative tests

## 2) Environment Prerequisites

1. Module enabled in `MODULES`.
2. Migrations applied:

```bash
php please migrate
```

3. `api_key` configured in `Admin -> SimpleMDM Settings`.
4. For webhook tests: `webhook_secret` configured.
5. For mutating action tests: `action_api_secret` configured.
6. If validating scheduled sync behavior, ensure cron is actually installed on the host.
   - If in-module script execution is enabled, use `Enable Scheduled Sync` in the admin UI.
   - Otherwise install it manually with `local/modules/simplemdm/scripts/install_cron.sh --munkireport-url '<url>' --api-key 'YOUR_SIMPLEMDM_API_KEY' --install`.
7. If you are validating shell helpers directly, confirm the execute bit is present.
   - If needed: `chmod +x local/modules/simplemdm/scripts/install_cron.sh local/modules/simplemdm/scripts/remove_cron.sh`

## 2.1) Workflow Expectations

Use these rules during testing:

1. `Sync Status -> Queue Next Worker Run` is queue-based and should change queue state for the next worker pickup.
2. `Last Queue Request` and `Queue Pickup Time` are queue-only fields; they should not be interpreted as the timestamp of the most recent scheduled run.
3. `Last Completed Source`, `Last Completed Status`, and `Last Completed Time` plus the schedule panel `Last Run` / `Last Run Source` are the source of truth for the most recent completed sync.
4. `Recent Runs` should show the latest queued/running/completed rows from `simplemdm_sync_run`.
5. The admin page should update queue/run cards automatically without a full browser refresh.
6. `Clear Run History` should remove `Recent Runs` cards and reset last-completed sync UI when no sync is queued or running.
7. `In-Module Sync And Schedule -> Run Sync Now` is the immediate one-off run path.
8. `Enable Scheduled Sync` / `Disable Scheduled Sync` control recurring schedule intent in the module.
9. recurring schedule execution still requires cron to launch `simplemdm_sync.py`.
10. `simplemdm_sync.py` is the worker; `install_cron.sh` is only a helper for installing its schedule.
11. `sync_last_api_errors` should reflect real API failures only; expected unsupported endpoint probes should not inflate it.

## 3) Hosted / VM Smoke Test

Run from MunkiReport root.

1. Manual sync:

```bash
python3 local/modules/simplemdm/scripts/simplemdm_sync.py \
  --api-key 'YOUR_SIMPLEMDM_API_KEY' \
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
   - confirm supplemental sections render with source labels and freshness state

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

If you want to validate recurring schedule behavior, install a real cron entry first or use:

```bash
local/modules/simplemdm/scripts/install_cron.sh --munkireport-url 'http://localhost:8888' --api-key 'YOUR_SIMPLEMDM_API_KEY' --install
```

2. Manual sync from host:

```bash
python3 local/modules/simplemdm/scripts/simplemdm_sync.py \
  --api-key 'YOUR_SIMPLEMDM_API_KEY' \
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
   - `Supplemental Overview` and `Supplemental AppleCare` widgets render if summary data exists

4. Schedule and one-off sync smoke test:
   - Open `Admin -> SimpleMDM Settings`
   - In `Sync Status`, click `Queue Next Worker Run`
   - Confirm queue state changes and `Last Completed Time` updates after the worker runs
   - If module execution is available, use `In-Module Sync And Schedule -> Run Sync Now`
   - Confirm the immediate run completes without waiting for cron
   - Set `Schedule` to `Every 15 Minutes` and click `Enable Scheduled Sync`
   - Confirm `Schedule Config = Enabled`
   - Confirm `Recurring Sync Ready = Yes` only after cron is actually installed
   - Confirm `Last Run` and `Last Run Source` update after successful immediate or queued runs
   - Confirm `Next Expected Run` is populated
   - Wait for cron pickup or run:

```bash
python3 local/modules/simplemdm/scripts/simplemdm_sync.py \
  --api-key 'YOUR_SIMPLEMDM_API_KEY' \
  --munkireport-url 'http://localhost:8888' \
  --respect-schedule \
  --force-run \
  --verbose
```

   - Confirm `Last Completed Time` updates
   - Click `Disable Scheduled Sync`
   - Confirm `Schedule Config = Disabled`

## 5) Supplemental Option A Checks

1. Open `Admin -> SimpleMDM Settings`.
2. Confirm `Supplemental Data` shows detected source tables.
3. Confirm the `Supplemental And Client Reporter Settings` card shows:
   - `Supplemental Module Enrichment (Option A)`
   - `Client Reporter Ingestion (Option B)`
4. Confirm detected sources show whether they are:
   - built-in supplemental mappings
   - auto-discovered loaded modules
5. Confirm detected sources can be unchecked without uninstalling the source module.
6. Click `Refresh Supplemental Summary`.
7. Confirm `Summary Rows` increases above `0` when `simplemdm` has devices.
8. Confirm freshness counters populate.
9. Open `show/listing/simplemdm/simplemdm`.
10. Test supplemental filters:
   - `Supplemental FileVault`
   - `Supplemental AppleCare`
   - `Supplemental Profiles`
   - `ManagedInstalls`
11. Open one device and confirm:
   - supplemental sections render
   - stale/fresh states appear
   - fields are labeled by source
12. Open one client detail tab and confirm the supplemental summary table renders.
13. Open the report page and confirm:
   - `Supplemental Overview`
   - `Supplemental AppleCare`
   widgets render without JS errors.
14. Backend validation option:
   - run `php local/modules/simplemdm/scripts/option_a_backend_check.php SERIAL_NUMBER`
   - confirm the returned summary row reflects the expected built-in supplemental modules for that serial

## 6) Option B Client-Reporter Checks

1. In `Admin -> SimpleMDM Settings`, enable `Client Reporter Ingestion`.
2. Set `Client Reporter Secret`.
3. Confirm the admin copy clearly describes Option B as posting client facts into this MunkiReport module, not into the external SimpleMDM service.
4. Confirm the `Client Reporter Requirements` panel accurately lists:
   - current required headers
   - whether shared-secret-only is still valid
   - whether HMAC, replay protection, device tokens, or trusted proxy rules are active
   - the current IP allowlist and trusted proxy settings
5. Post an allowlisted payload:

```bash
curl -i -X POST "http://localhost:8888/index.php?/module/simplemdm/index?op=ingest_client_facts" \
  -H "Content-Type: application/json" \
  -H "X-SIMPLEMDM-CLIENT-SECRET: YOUR_CLIENT_SECRET" \
  -d '{
    "serial_number": "C02C72ANLVDL",
    "reported_at": "2026-03-12T12:00:00Z",
    "client_version": "1.0.0",
    "facts": {
      "mdm_profile_present": true,
      "console_user": "jdoe",
      "uptime_seconds": 86400,
      "munki_last_run_result": "success",
      "local_filevault_enabled": true
    }
  }'
```

6. Confirm `simplemdm_client_fact` contains current rows for the serial.
7. Confirm `simplemdm_client_fact_history` receives rows when history is enabled.
8. Open the SimpleMDM device page and confirm a `Client Reporter` supplemental section appears.
9. Open the client tab and confirm client-reported facts appear in the supplemental summary.
10. Post an unknown key and confirm it is rejected in the response.
11. If a similar fact already exists in another loaded module, confirm the team documents which source is authoritative instead of treating Option B as a replacement for Option A.
12. If both Option A and Option B are enabled, confirm both sources render side by side without overwriting each other.
13. If overlapping facts are intentionally collected for drift detection, confirm the source labels make the difference visible to the operator.
14. If Option B hardening is enabled, confirm:
   - HMAC-signed requests succeed only with valid `timestamp + nonce + signature`
   - reused nonce requests are rejected
   - invalid per-device tokens are rejected when device-token enforcement is enabled
   - requests outside the configured proxy/IP rules are rejected

## 7) API/Auth Negative Tests

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

## 8) Functional Test Matrix

| Area | Test | Expected |
|---|---|---|
| Config | Save API key/secrets/toggles | Save success, values persist |
| Sync | Device ingest | Device table count increases/updates |
| Sync | Resource ingest | Resource listing populated |
| Sync | Commands ingest (if enabled) | Command status widget shows data |
| Sync | API telemetry | `sync_last_api_errors` stays at `0` for a healthy run using supported endpoints |
| Widgets | Assignment groups | `get_assignment_group_stats` returns JSON and group widgets render |
| Widgets | OS security | `get_os_security_stats` returns JSON and widget renders |
| Webhook | Test event ingestion | `simplemdm_webhook_event` receives record |
| Report | Core widgets render | no JS/API errors |
| Listing | Device filters | filtered results correct |
| Listing | Supplemental filters | summary-backed filters return expected devices |
| Listing | Resource type/endpoint filters | filtered results correct |
| Device page | Connected resources | per-type rows visible |
| Device page | Subresources | apps/users/profiles rows visible (if enabled) |
| Device page | Supplemental sections | source labels + freshness visible |
| Admin | Supplemental summary refresh | summary rows and health counts update |
| Widgets | Supplemental overview/applecare | summary-backed widgets render |
| Client reporter | Allowed ingest | current facts upsert and render |
| Client reporter | Unknown fact reject | rejected keys returned, no unsafe write |
| Device actions | Safe action (`refresh`) | request accepted with valid secret |

## 9) Regression Focus for UI Changes

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
   - supplemental sections
   - client reporter section
   - actions panel

Useful visual references:
- `docs/images/dashboard-overview-part1.png`
- `docs/images/dashboard-overview-part2.png`
- `docs/images/device-detail-overview.png`
- `docs/images/device-actions-runner.png`

## 10) Data/Schema Validation

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
   - `simplemdm_supplemental_summary`
   - `simplemdm_client_fact`
   - `simplemdm_client_fact_history`
2. Confirm indexes/uniqueness work as expected for resource lookup patterns.
3. Confirm no migration errors in logs.

## 11) Release Sign-Off Checklist

1. Hosted smoke test passed.
2. Docker smoke test passed.
3. Supplemental Option A checks passed.
4. Option B client-reporter checks passed.
5. Auth negative tests passed.
6. No blocking UI regressions.
7. README/docs updated for new options/routes/widgets.

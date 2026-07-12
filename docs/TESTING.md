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
8. MCP Findings lifecycle, admin-action, and analytics checks (Section 14)

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
12. `devices/{id}/users` should be skipped for unsupported device/platform combinations without removing those devices from local inventory.
13. stale `simplemdm_sync_run` rows older than 2 hours should auto-clear to `failed` on later worker/status checks.
14. Headless clients may use `X-SIMPLEMDM-API-KEY` on the documented token-readable
    module data routes without a browser session.

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
5. Validate one token-readable route without a browser session:

```bash
curl -H "X-SIMPLEMDM-API-KEY: YOUR_SIMPLEMDM_API_KEY" \
  "http://127.0.0.1/module/simplemdm/get_sync_telemetry"
```

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
   - `SimpleMDM Devices Table` renders device rows and does not fall back to `Failed to load devices`

If the devices table widget fails after the supplemental-data migrations:
- confirm the latest module migrations ran successfully
- confirm the collation-repair migration [2026_03_16_000000_simplemdm_supplemental_collation_fix.php](/Users/jay/Developer/Github/GitHub/SimpleMDM/munkireport-php/local/modules/simplemdm/migrations/2026_03_16_000000_simplemdm_supplemental_collation_fix.php) has been applied
- symptom: `/module/simplemdm/get_data` fails because new supplemental tables were created with a different collation than the existing `simplemdm` tables

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

## save_config should reject sync-token-only auth

```bash
curl -i -X POST "http://localhost:8888/index.php?/module/simplemdm/save_config" \
  -H "Content-Type: application/json" \
  -H "X-SIMPLEMDM-API-KEY: YOUR_SIMPLEMDM_API_KEY" \
  -d '{"api_key":"should-not-save"}'
```

Expected:
- HTTP 401 (no session) or a global-admin-required rejection if called from a
  non-global authenticated session that also carries the sync token
- config values are unchanged (fixed 2026-07-10 — this previously succeeded
  for a non-global session carrying a valid sync token; see `docs/SECURITY.md`)

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
| MunkiReport events | Accepted admin action | `simplemdm_action` visible in Events UI |
| MunkiReport events | Command failed | `simplemdm_command` visible in Events UI |
| MunkiReport events | Unenrollment regression | `simplemdm_enrollment` visible in Events UI |
| MunkiReport events | ADE/DEP regression | `simplemdm_dep` visible in Events UI |
| MunkiReport events | FileVault regression | `simplemdm_filevault` visible in Events UI |
| MunkiReport events | Supervision regression | `simplemdm_supervision` visible in Events UI |
| MunkiReport events | Failed admin action | `simplemdm_action_failure` visible in Events UI |
| MunkiReport events | Firewall regression | `simplemdm_firewall` visible in Events UI |
| MunkiReport events | SIP regression | `simplemdm_sip` visible in Events UI |
| MunkiReport events | Passcode regression | `simplemdm_passcode` visible in Events UI |
| MunkiReport events | Activation lock regression | `simplemdm_activation_lock` visible in Events UI |
| MunkiReport events | Stale-device transition | `simplemdm_stale` visible in Events UI |
| MunkiReport events | Recovery lock failure | `simplemdm_recovery_lock` visible in Events UI |
| Device page | MCP findings section | Renders for a serial with findings (severity badges, `data` disclosure, admin action buttons for global-admin sessions); hidden entirely for a serial with none — see Section 15 |
| Findings page | Load, filter, paginate, bulk-act, export, deep link | `module/simplemdm/findings` renders rows, filters narrow results (incl. `finding_type`), pagination advances at 50/page, admin bulk actions update status, CSV/JSON export carries filters, deep-link query params pre-fill — see Section 15 |
| Widgets | `simplemdm_mcp_severity` / `simplemdm_mcp_source` | Donut + list render from `get_mcp_finding_stats`, no console errors |
| Widgets | `simplemdm_mcp_critical` | Open danger findings list renders from `get_mcp_findings?severity=danger`, wheel-scroll works |
| Widgets | `simplemdm_mcp_timeline` | New/Resolved 30-day lines render from `get_mcp_finding_timeline?days=30` |
| Widgets | `simplemdm_mcp_top_devices` | Ranked device list renders from `get_mcp_finding_stats` `top_devices` |
| Routes | `get_mcp_finding_timeline` | Returns daily New/Resolved counts, `days` defaults to 30 (values below 1 fall back to 30, values above 90 cap at 90) |
| Routes | `finding_type` filter | `get_mcp_findings`/`get_mcp_finding_stats?finding_type=` narrows results, comma-separated, case-sensitive — see Section 14 |
| Events | Fleet findings summary opt-in | `mcp_findings_event_enabled=0` (default) writes no `simplemdm_mcp_findings_summary` row; enabled, one deduplicated row appears anchored to the worst device — see Section 14 |

## 9) Regression Focus for UI Changes

When changing views/assets:

1. Check report page and dashboard page behavior separately.
2. Verify widget ordering/collapse behavior on refresh.
3. Validate listing tables on both narrow and wide viewport widths.
   - MCP Findings widget specifically:
     - confirm findings render grouped by `category`, sorted with any group
       containing a `danger`-severity finding first
     - inside an expanded category with multiple finding types (or more than
       25 findings), confirm findings are sub-grouped under `finding_type`
       headers with per-type counts, at most 25 rows render per type, and a
       "+N more — view all ... findings" link appears for capped types and
       navigates to `module/simplemdm/findings` pre-filtered by `finding_type`
       (and `category`, unless "Uncategorized")
     - with more findings than the 500-row fetch cap, confirm category
       headers show an "N total" badge with the true count from
       `get_mcp_finding_stats`, and every severity counted in the top totals
       badges is reachable somewhere in the list (e.g. `info` findings are
       not silently hidden by the fetch limit)
     - confirm a group with a `danger`-severity finding starts expanded and
       other groups start collapsed
     - click a group's toggle button and confirm it expands/collapses and the
       button label switches between `+ Expand` / `- Collapse`
     - confirm the widget body scrolls internally once findings overflow the
       panel instead of growing the dashboard grid, and that it keeps the
       shared `simplemdm-list-scroll` behavior (it is exempted from the
       dynamic scroll-class removal that applies to other list widgets)
     - confirm the "Fetched the N most recent of M findings" note reflects the
       fetched count (up to 500) against total findings when truncated, and
       includes an "Open findings browser" link to `module/simplemdm/findings`
     - test scrolling in **both Chrome and Safari**, and if possible with a
       plain mouse wheel or remote-control session as well as a trackpad —
       wheel scrolling of this list (and the Devices Table rows) is
       JS-driven (`bindWheelScroll` in the shared widget assets) because
       Safari does not natively scroll `overflow: auto` sub-scrollers from
       phase-less wheel input; a fix that looks correct in Chrome or with a
       trackpad can still be broken for other input paths (see the Safari
       sub-scroller wheel postmortem in `docs/DEVELOPER_GUIDE.md`)
     - confirm the list scrolls 1:1 with wheel input, stops hard at its
       top/bottom without visible bounce/shake, and hands the scroll off to
       the page when wheeled past a boundary
     - confirm rows do not lift/shadow-animate on hover while scrolling with
       a stationary cursor
     - after scrolling, confirm both the per-category `+Expand`/`-Collapse`
       toggle and the whole-widget minimize button (panel heading, requires
       selecting the widget first) still respond to clicks
     - repeat the wheel/trackpad checks on every other sub-scroller: the
       collapsed bodies of the Groups, Resource Types, and Assignment Group
       Apps widgets (each self-binds the shared fix at render), any widget
       list auto-marked scrollable past 12 items, and the device page's
       finding `data` disclosures. `tests/Unit/SafariScrollFixGuardTest.php`
       mechanically guards the full fix inventory — if it fails after a
       change, read both Safari postmortems before weakening it
4. Validate device detail sections:
   - overview
   - attributes
   - relationships
   - connected resources
   - synced device subresources
   - supplemental sections
   - client reporter section
   - MCP findings section (badges, disclosure, admin actions, hidden when empty)
   - actions panel

Useful visual references:
- `docs/images/dashboard_kpis.png`
- `docs/images/dashboard_security_enrollment.png`
- `docs/images/simplemdm_device_detail.png`
- `docs/images/device_action_runner.png`

## 10) MunkiReport Event Verification

Minimal event scope currently implemented by this module:

- `simplemdm_action`
- `simplemdm_action_failure`
- `simplemdm_command`
- `simplemdm_enrollment`
- `simplemdm_dep`
- `simplemdm_filevault`
- `simplemdm_supervision`
- `simplemdm_firewall`
- `simplemdm_sip`
- `simplemdm_passcode`
- `simplemdm_activation_lock`
- `simplemdm_stale`
- `simplemdm_recovery_lock`

Recommended verification flow:

1. Confirm the target device has normal host rows:
   - `machine.serial_number = <serial>`
   - `reportdata.serial_number = <serial>`
2. Confirm the `Event Settings` card loads:
   - built-in toggle list renders
   - stale threshold input renders current value
   - custom event rows load from `custom_event_rules_json`
3. Save a small event settings change:
   - toggle one built-in event off, save, reload, confirm it stays off
   - restore the original value
4. Add one constrained custom rule and save:
   - example: `firewall_enabled` + `became_disabled`
   - for `changed_to` rules, use the exact stored module value such as `unenrolled`
   - confirm the rule persists after reload
   - remove or disable the test rule after verification
5. Trigger or simulate one event of each type:
   - accepted mutating admin action
   - failed mutating admin action
   - failed command status
   - failed recovery lock command status
   - transition from `enrolled` to non-enrolled
   - transition from ADE/DEP enabled to disabled
   - transition from FileVault enabled to disabled
   - transition from supervised to unsupervised
   - transition from firewall enabled to disabled
   - transition from SIP enabled to disabled
   - transition from passcode compliant to non-compliant
   - transition from activation lock enabled to disabled
   - transition from fresh `last_seen_at` to stale `last_seen_at`
6. Confirm rows exist in the host `event` table for the expected `simplemdm_*` module keys.
7. Confirm the same rows appear in:
   - `/show/listing/event/event`
   - the `Events` widget
8. Confirm recovery/clear behavior where applicable:
   - `simplemdm_action_failure` clears on later accepted admin action
   - `simplemdm_command` clears on later non-failed command state
   - `simplemdm_recovery_lock` clears on later non-failed recovery lock state
   - `simplemdm_enrollment` clears on return to `enrolled`
   - `simplemdm_dep` clears on return to enabled
   - `simplemdm_filevault` clears on return to enabled
   - `simplemdm_supervision` clears on return to enabled
   - `simplemdm_firewall` clears on return to enabled
   - `simplemdm_sip` clears on return to enabled
   - `simplemdm_passcode` clears on return to compliant
   - `simplemdm_activation_lock` clears on return to enabled
   - `simplemdm_stale` clears when `last_seen_at` returns within threshold
9. If custom rules were added for testing, verify:
   - disabled custom rules stop writing new rows
   - enabled custom rules write under their own `simplemdm_<suffix>` module keys
   - invalid custom rule combinations are rejected by `save_config`

Important UI note:

- visible MunkiReport Events UI rows depend on the host listing join, not only the `event` table
- if `event` rows exist but the UI still shows `0` or `No messages`, check that both `machine` and `reportdata` contain the same serial number
- the global listing is effectively driven through `reportdata` and joined to `machine` and `event`

## 11) Data/Schema Validation

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
   - `simplemdm_mcp_finding`
2. Confirm indexes/uniqueness work as expected for resource lookup patterns.
3. Confirm no migration errors in logs.

## 12) Release Sign-Off Checklist

1. Hosted smoke test passed.
2. Docker smoke test passed.
3. Supplemental Option A checks passed.
4. Option B client-reporter checks passed.
5. Auth negative tests passed.
6. No blocking UI regressions.
7. README/docs updated for new options/routes/widgets/events.
8. PHPUnit suite passes locally (`vendor/bin/phpunit`) — see Section 13.

## 13) Unit Tests (PHPUnit)

The module ships a PHPUnit suite (`tests/Unit/`) that runs against a real
in-memory SQLite database with the module's actual migrations applied, not
mocks. This is intentional: it catches schema/logic mismatches that mocked
tests would miss.

Setup (once):

```bash
cd local/modules/simplemdm
composer install
```

Run the suite:

```bash
vendor/bin/phpunit
```

Current coverage:

| Test file | Covers |
|---|---|
| `tests/Unit/McpFindingModelTest.php` | Pure static helpers on `simplemdm_mcp_finding_model.php` — `normalizeFinding()`, `computeUpsertUpdate()`, `parseFindingIds()`, `buildStatusUpdate()`, `parseMultiValueParam()`. No DB required. |
| `tests/Unit/McpFindingUpsertDbTest.php` | `simplemdm_mcp_finding_model.php` upsert/dedup/reopen/auto-resolve behavior against a real (in-memory) `simplemdm_mcp_finding` table |
| `tests/bootstrap.php` | Shared bootstrap: spins up an in-memory SQLite DB and applies real migrations before the suite runs |

`phpunit.xml` at the module root points the `Unit` test suite at `./tests/Unit`
and boots through `tests/bootstrap.php`.

When adding a new finding-lifecycle behavior (new status transition, new
filter, new fingerprint field), add or extend a test in this suite before
relying on manual QA alone — manual QA in Sections 1-12 should stay focused on
things PHPUnit can't reach (real HTTP routes, session/token auth, browser
rendering).

## 14) MCP Findings Lifecycle, Admin-Action, and Analytics Checks

Covers the ingest-lifecycle/category/admin-action/analytics surface added to
`ingest_mcp_findings` and the related routes. See `docs/API_REFERENCE.md`
for full request/response shapes.

### Ingest lifecycle (upsert/dedup/reopen/auto-resolve)

1. Push a finding with a given `(source, serial_number, finding_type,
   category)` combination twice; confirm the second push updates the same row
   (`occurrence_count` increments, `last_seen_at` advances) rather than
   creating a duplicate.
2. Push the same finding again with a different `category` (including
   omitted/empty `category`); confirm it creates a distinct row rather than
   colliding with the first.
3. Push a complete scan (`replace: true`, the default) for a source that omits
   a previously-active finding; confirm that finding auto-resolves
   (`status = resolved`, `resolved_at` set).
4. Push the same fingerprint again after auto-resolve; confirm it reopens
   (`status` returns to `open`, `resolved_at` clears).
5. Push with `replace: false`; confirm untouched findings from that source do
   NOT auto-resolve.
6. Confirm `mcp_findings_auto_resolve = 0` in admin settings prevents the
   auto-resolve sweep even when a push sends `replace: true`.

### Read filters

1. `get_mcp_findings` with no `status` filter returns only
   `open`/`acknowledged`/`in_progress` findings.
2. `get_mcp_findings?status=resolved` (or any explicit status) returns
   findings in that status, including ones the default view hides.
3. `get_mcp_findings?category=FileVault` returns only that category
   (comma-separated multi-category also works).
4. `get_mcp_findings?since=<ISO8601>` returns only findings updated at/after
   that timestamp.
5. `get_mcp_findings?scan_id=<id>` returns only findings from that ingest.
6. Confirm `status_totals` and the legacy `totals` field are unaffected by
   every filter above — both always reflect the whole table (`status_totals`)
   or all-active rows (`totals`), never the filtered result set.

### Admin action routes

1. `acknowledge_mcp_finding`, `resolve_mcp_finding`, `ignore_mcp_finding`,
   `suppress_mcp_finding` each accept a single `id` or a batch `ids` array and
   set the finding(s) to the expected status (see `docs/API_REFERENCE.md`
   Section 12 for the exact status/side-effect table).
2. Confirm an unconditional transition works — e.g. move a `resolved` finding
   straight to `acknowledged` without error.
3. Confirm a request naming a mix of existing and non-existent ids returns
   `not_found` for the missing ones and still updates the existing ones.
4. Confirm all four routes reject requests without a valid sync token (401).
5. Confirm all four routes (plus `ingest_mcp_findings`/`get_mcp_findings`)
   return `403 {"status":"error","message":"MCP findings are disabled"}` when
   `mcp_findings_enabled = 0`, and that the dashboard widget keeps rendering
   with its existing "Failed to load MCP findings." fallback rather than
   erroring.

### Analytics/export routes

1. `get_mcp_finding_stats` returns severity/status/category/source count
   breakdowns.
2. `export_mcp_findings` returns CSV and JSON output on request and enforces
   the 10,000-row export cap.
3. `get_mcp_scan_status` returns a per-source last-scan summary. With
   SimpleMDM-MCP v0.34.0+ auto-publish enabled, expect one row per
   publishing tool (`mcp_auto_<tool>`, `mcp_auto_action_<tool>`,
   `sofa_audit`), not a single `mcp` source — see `docs/API_REFERENCE.md`
   §11 for the source-namespace conventions.
4. Confirm all three are readable via `X-SIMPLEMDM-API-KEY` without a browser
   session, same as `get_mcp_findings`.

### Admin settings panel

1. Open `Admin -> SimpleMDM Settings` and confirm the "MCP Findings Settings"
   panel shows `mcp_findings_enabled`, `mcp_findings_metadata_max_bytes`,
   `mcp_findings_auto_resolve`, `mcp_findings_event_enabled`,
   `mcp_findings_event_warning_threshold`, and `mcp_findings_retention_days`
   (labeled "Retention Days").
2. Save a `mcp_findings_metadata_max_bytes` value below 1024 and confirm it is
   clamped up to the 1024-byte floor rather than saved as-is.
3. Save a `mcp_findings_event_warning_threshold` value below 1 (e.g. `0` or a
   negative number) and confirm it is clamped up to `1` rather than saved
   as-is.
4. Retention Days: enter `-5`, save, reload — field shows `0` (server
   clamps). Set `1`, push a finding, backdate it to `status='resolved'`
   with `last_seen_at` 2+ days old (see the PDO one-liner in the retention
   plan), then push a **different** finding (any other `finding_type`) from
   the same source — the ingest response reports `"purged": 1` and the
   backdated row is gone. (Re-pushing the *same* finding instead matches
   its fingerprint in the upsert loop, which runs before the purge: the row
   is reopened with a fresh last-seen and reports `"reopened": 1,
   "purged": 0`.) Reset to `0` afterwards.
5. Confirm these settings save/read through the normal `save_config`/
   `get_config` routes — `save_config` requires a global-admin session (see
   Section 7 and `docs/SECURITY.md`).

### Fleet findings summary event (PRD section 13)

1. With `mcp_findings_event_enabled = 0` (the default), push a finding via
   `ingest_mcp_findings` and confirm no row is written under module
   `simplemdm_mcp_findings_summary` in the `event` table — built-in
   `simplemdm_*` events are untouched either way.
2. Enable `mcp_findings_event_enabled` (via the admin panel `save_config`, or
   directly through the module's settings mechanism). Push one danger-severity
   finding for a real device serial (one present in `machine`) and confirm a
   single event row appears under module `simplemdm_mcp_findings_summary`,
   `serial_number` equal to that device, `type = 'danger'`, and `msg` matching
   `Simplemdm_mcp_finding_model::summarizeFindingsForEvent()`'s exact wording
   for a danger count of 1: `SimpleMDM MCP: 1 danger finding requires
   immediate attention.`
3. Resolve that finding via `resolve_mcp_finding` and confirm the event row
   updates or clears per `summarizeFindingsForEvent()`'s logic (e.g. it drops
   to a `warning`/`info` message, or disappears entirely, depending on what
   other active findings remain fleet-wide) — the sync always deletes the
   previous row before conditionally rewriting it, so no stale row is ever
   left on the old anchor serial.
4. With findings on two or more devices, confirm the event's `serial_number`
   anchors to the worst device (highest danger count, then warning count,
   then total active findings, then lowest serial), and that the anchor
   moves to a different device's serial if that device's findings become
   worse than the current anchor's on a later push.
5. Confirm the event renders at `/show/listing/event/event` for an anchor
   serial that has corresponding rows in both `machine` and `reportdata` (see
   the "Important UI note" above — a serial missing from either table will
   not render even though the `event` row is correct).
6. Confirm the built-in `simplemdm_*` per-device events (Section 14 above)
   are unaffected by any of the above — the summary event only ever writes to
   its own `simplemdm_mcp_findings_summary` module key.

## 15) Findings Browser Page (`module/simplemdm/findings`)

`views/simplemdm_findings_page.php` is the full-set companion to the MCP
Findings widget — reachable directly, via the widget's "+N more" links, and
via its truncation-note "Open findings browser" link.

1. **Load and default state**: open `module/simplemdm/findings` directly.
   Confirm it renders inside the normal MunkiReport page chrome (nav header,
   footer — it is a standalone page, not an ajax fragment) and the table
   populates with up to 50 rows using the default status filter
   (the `open`, `acknowledged`, and `in_progress` status chips render
   active). In dark mode, confirm the toolbar controls follow the theme
   (no light browser-default boxes) and active chips show readable
   inverted-contrast labels.
2. **Filters narrow results**: set `severity` to `info`, click `Apply`, and
   confirm every visible row's severity column reads `info`. Toggle the
   `resolved` status chip on, `Apply`, and confirm resolved rows appear
   (chip toggles take effect on `Apply`, like every other filter). Repeat spot
   checks for `category`, `source`, and `finding_type` (comma-separated is
   accepted, matching Task 2's `get_mcp_findings`/`get_mcp_finding_stats`
   filter). Category/source dropdown options are populated from
   `get_mcp_finding_stats` (`by_category`/`by_source`).
3. **Pagination**: with more than 50 matching findings, confirm `Next`
   advances the row range (e.g. "rows 51-100") and is disabled once a page
   returns fewer than 50 rows; confirm `Prev` returns to the previous page
   and is disabled at offset 0.
4. **Bulk actions require admin**: as a global-admin session, select two or
   more checkboxes, confirm the bulk action bar appears with a selection
   count, click `Acknowledge`, and confirm both rows' `Status` column update
   in place (the batch POST hits `acknowledge_mcp_finding` with
   `{"ids":[...]}`, per Task 1). Repeat spot checks for `Resolve`, `Ignore`,
   `Suppress` as needed. As a non-admin session, confirm the select-all
   checkbox and bulk action bar are hidden entirely (`data-admin="0"` on the
   page root).
5. **Export links carry filters**: with a non-default filter set (e.g.
   `severity=info`), confirm the `Export CSV` and `Export JSON` link hrefs
   are `export_mcp_findings?format=csv&...`/`format=json&...` with the same
   filter values, URL-encoded, appended as query params — not just the
   default filters.
6. **Deep links arrive pre-filtered**: open
   `module/simplemdm/findings?finding_type=stale_device` directly and confirm
   the `finding_type` input is pre-filled and every rendered row's `Type`
   column reads `stale_device` without an extra `Apply` click. Repeat for
   `?severity=`, `?category=`, `?source=`, and `?status=` (comma-separated).
7. **Widget integration**: from the dashboard, expand an MCP Findings widget
   category with a capped `finding_type` sub-group (>25 findings of one
   type), click its "+N more — view all ... findings" link, and confirm it
   navigates to `module/simplemdm/findings` with `finding_type` (and
   `category`, when not "Uncategorized") pre-filled and matching rows.
8. **No console errors** in any of the above states.

# SimpleMDM Supplemental Data Options

This document is now a historical design note for the Option A / Option B supplemental strategy.

It is no longer accurate to treat this as a pure proposal. Core parts of both paths are implemented in the current module.

Use this document for background and design intent.

For current module behavior, use:

- `README.md`
- `docs/API_REFERENCE.md`
- `docs/TESTING.md`
- `docs/DEVELOPER_GUIDE.md`
- `docs/CLIENT_REPORTER_DEPLOYMENT.md`

For the broader historical plan and rationale, see:

- `docs/SUPPLEMENTAL_DATA_IMPLEMENTATION_PLAN.md`

Current module behavior now includes:

- `simplemdm_sync.py` for core SimpleMDM API sync
- Option A cross-module supplemental enrichment
- Option B client-reporter ingestion into this MunkiReport module

## Current Option B Summary

Option B is now implemented as a narrow allowlisted ingest path for client-reported facts.

Option A and Option B can be used together.

Combined operating model:

- core sync remains the source of truth for native SimpleMDM API data
- Option A enriches this module with data from other loaded MunkiReport modules
- Option B enriches this module with local endpoint facts posted into this module

Recommended trust boundaries:

- do not let Option B replace authoritative core-sync fields
- do not use Option B to duplicate facts already owned by another module unless the goal is explicit drift detection
- when overlapping facts exist, document which source is authoritative for operations

Use Option B when:

- the fact comes from the endpoint itself
- the fact is not already owned by another loaded MunkiReport module
- the fact does not belong in core SimpleMDM API sync

Prefer Option A instead when:

- another loaded MunkiReport module already collects the data
- you want cross-module enrichment from an existing source table

Current implementation:

- endpoint:
  - `POST /module/simplemdm/index?op=ingest_client_facts`
- auth:
  - `X-SIMPLEMDM-CLIENT-SECRET`
- storage:
  - `simplemdm_client_fact`
  - `simplemdm_client_fact_history`
- rendering:
  - `Client Reporter` supplemental source on the standalone device page and client tab
- admin controls:
  - enable/disable
  - history toggle
  - payload size limit
  - allowlisted fact keys

Typical Mac-side use cases:

- current console user for shared-device troubleshooting
- local uptime for endpoint-health checks
- local FileVault reality checks when you want a device-side comparison
- small custom checks collected by a shell script, LaunchDaemon, or Munki workflow

Typical Mac-side flow:

1. a local reporter script runs on the Mac
2. it collects the serial number and a narrow set of allowlisted facts
3. it posts JSON to `POST /module/simplemdm/index?op=ingest_client_facts`
4. it authenticates with `X-SIMPLEMDM-CLIENT-SECRET`
5. the module validates and stores current values
6. the device page and client tab show those facts as `Client Reporter`

Example files included in this module:

- basic shared-secret reporter:
  - `scripts/simplemdm_client_reporter_example.sh`
- hardened reporter:
  - `scripts/simplemdm_client_reporter_hardened.py`
- installer helper:
  - `scripts/install_client_reporter.sh`
- `launchd` example:
  - `scripts/com.googlecode.munkireport-simplemdm-client-reporter.plist.example`
- Munki postflight wrapper:
  - `scripts/postflight_simplemdm_client_reporter_example.sh`

Minimal Mac-side example:

```bash
SERIAL="$(system_profiler SPHardwareDataType | awk -F': ' '/Serial Number/ {print $2; exit}')"
CONSOLE_USER="$(stat -f %Su /dev/console)"
UPTIME_SECONDS="$(python3 -c 'import subprocess,time; out=subprocess.check_output([\"sysctl\",\"-n\",\"kern.boottime\"], text=True); sec=int(out.split(\"sec = \")[1].split(\",\")[0]); print(int(time.time())-sec)')"

curl -X POST "https://YOUR_MUNKIREPORT/index.php?/module/simplemdm/index?op=ingest_client_facts" \
  -H "Content-Type: application/json" \
  -H "X-SIMPLEMDM-CLIENT-SECRET: YOUR_CLIENT_SECRET" \
  -d "{
    \"serial_number\": \"${SERIAL}\",
    \"client_version\": \"1.0.0\",
    \"source\": \"client_reporter\",
    \"facts\": {
      \"console_user\": \"${CONSOLE_USER}\",
      \"uptime_seconds\": ${UPTIME_SECONDS}
    }
  }"
```

Suggested deployment patterns:

- LaunchDaemon for periodic local reporting
- Munki postflight if the fact should report after managed software activity
- another local management framework if it already deploys scripts reliably

If you want a runnable starting point instead of adapting the inline curl example:

- use `simplemdm_client_reporter_example.sh` for the original shared-secret flow
- use `simplemdm_client_reporter_hardened.py` when HMAC, nonce replay protection, and device tokens are enabled
- use the included plist example as a template for `launchd`
- use `postflight_simplemdm_client_reporter_example.sh` when you want Munki to trigger reporting after managed software activity

What to avoid sending:

- large app inventories
- facts another loaded MunkiReport module already stores cleanly
- fields that should remain authoritative from the external SimpleMDM API
- secrets or raw sensitive local data not needed for operations

## Optional Security Hardening

The current Option B implementation still supports the original shared-secret model in `X-SIMPLEMDM-CLIENT-SECRET`.

It now also supports optional hardening layers:

1. HMAC-signed requests
2. timestamp and nonce replay protection
3. per-device or scoped reporter tokens
4. trusted-proxy enforcement
5. IP allowlist filtering
6. mTLS remains a future enhancement if the environment requires a stronger client-identity model

Recommended product position:

- current Option B is secure enough for controlled HTTPS-based internal reporting with a narrow allowlist
- the original shared-secret flow remains available for backward compatibility
- the implemented hardening layers can be enabled incrementally without changing the Option B data model or UI behavior

Important boundary:

- Option B stores data in this MunkiReport module
- it does not write back into the external SimpleMDM service

## Recommended Direction

The best-fit design is:

- keep SimpleMDM API sync as the source of truth for MDM data
- keep other MunkiReport modules as the source of truth for their own collected data
- let `simplemdm` read supplemental data from those other module tables when rendering device details, tabs, reports, or new widgets

This means the preferred model is not a second sync pipeline.

It is a read-only enrichment layer built on top of existing module data.

Latest design direction:

- Option 1 remains the default path
- supported modules should be auto-detected from schema/table presence
- Option 1 should support both live per-device detail lookup and an optional cached summary/index layer
- Option 2 remains the fallback for facts existing modules do not provide
- external systems are possible later, but only through a separate cache/import model

## Option 1: Cross-Module Supplemental Data

### Description

In this model, `simplemdm` supplements its device detail and reporting views with data already collected by other MunkiReport modules.

Examples:

- `filevault_status`
- `findmymac`
- `applecare`
- `profile`
- `managedinstalls`
- `adobe`
- `ms_office`
- `speedtest`

The normal pattern would be:

1. load the `simplemdm` device row
2. get the device `serial_number`
3. check whether supported module tables exist
4. query those module tables by `serial_number`
5. return the extra data as supplemental fields or sections

For a broader product use case, this option should not be limited to detail-page-only lookup.

The stronger version is a hybrid model:

- live per-device lookup for deep detail sections
- optional cached supplemental summary/index for widgets, listings, filters, and stale-data visibility

### Best Use Case

This is the best use case when:

- another module already collects the missing information
- the data should remain owned by that source module
- you want richer device details without creating a new reporting agent
- you want to avoid overwriting `simplemdm` API fields

This is the best fit for filling areas that SimpleMDM does not expose in its API.

It is also the best fit when you want:

- supplemental dashboard widgets
- list filtering based on external module state
- cross-module summary views
- stale-data awareness without copying source-module ownership

Examples:

- warranty/coverage from `applecare`
- FileVault detail from `filevault_status`
- Find My Mac state from `findmymac`
- profile inventory from `profile`
- software/install context from `managedinstalls`

### How It Would Show In The UI

Supplemental data should be additive and clearly labeled.

Examples:

- new sections on the standalone device page
- new sections on the client tab
- optional cross-module widgets
- cross-module filtered reports
- listing/table indicators sourced from a supplemental summary layer

Recommended labeling:

- keep SimpleMDM-native fields visually primary
- show external values in a `Supplemental Data` area
- tag each field or section with the source module name

Example:

- `Warranty Coverage End` -> source: `applecare`
- `Find My Mac Enabled` -> source: `findmymac`
- `FileVault Escrowed` -> source: `filevault_status`

### Data Model

Recommended storage model:

- do not copy external module data into the main `simplemdm` table
- do not overwrite `simplemdm_resource`
- do not rewrite other module tables

Preferred implementation:

- query other module tables live by `serial_number`
- optionally build merged JSON responses in `simplemdm_controller.php`
- optionally cache capability checks for performance
- optionally maintain a lightweight supplemental summary/index keyed by `serial_number`

Recommended hybrid implementation:

- live lookup for device detail sections where full source data is needed
- cached summary/index for dashboard widgets, listings, filters, and at-a-glance status

The cached summary/index should store normalized summary facts only, not copied source records.

Suggested implementation note:

- the detailed plan proposes a dedicated summary table for Option 1 rather than using `simplemdm_sync.py` or main `simplemdm` tables as the integration point

Example summary fields:

- `serial_number`
- `supp_filevault_present`
- `supp_filevault_enabled`
- `supp_findmymac_present`
- `supp_findmymac_enabled`
- `supp_applecare_present`
- `supp_applecare_coverage_end`
- `supp_profile_count`
- `supp_managedinstalls_present`
- `supp_adobe_present`
- `supp_source_modules_json`
- `supp_last_refresh`

This preserves source-module ownership while still allowing:

- faster widgets and listings
- filtering and reporting
- stale-data visibility
- presence/absence indicators before a device detail page is opened

### Pros

- no new client agent required
- no new ingest path required
- no duplication of ownership between modules
- easier to maintain module boundaries
- low risk of overwriting authoritative SimpleMDM data
- simplest upgrade story for the `simplemdm` module
- easiest way to fill gaps in the SimpleMDM device page
- supports richer widgets and reports without turning SimpleMDM sync into a multi-module sync job
- allows stale-data awareness and supplemental status indicators

### Cons

- depends on other modules already being installed and collecting data
- schemas vary by module, so each integration is module-specific
- live joins can become expensive if used heavily in dashboards
- availability of supplemental data depends on the health of the source module
- not useful for facts that no existing module collects
- cached summary/index design adds some implementation complexity even though it does not replace source-module ownership

### Recommended Files To Change

- `simplemdm_controller.php`
- `views/simplemdm_device.php`
- `views/simplemdm_tab.php`
- optional new widget views under `views/`
- optional supplemental summary model/migration if cached indexing is added
- `docs/API_REFERENCE.md` if new merged endpoints are added

Operational note:

- this option should degrade gracefully if a supported module is not installed, has no table, or has no matching row for a given serial number

## Option 2: Client-Side Reporter Add-On

### Description

In this model, a small reporter runs on the Mac and sends supplemental local facts into the `simplemdm` module.

This would be:

- separate from the server-side SimpleMDM API sync
- separate from existing module tables unless intentionally joined in the UI
- useful for collecting facts that other modules do not already provide

Typical flow:

1. reporter runs on the Mac
2. collects a limited set of local facts
3. sends those facts to a dedicated `simplemdm` ingest endpoint
4. module stores them in a separate supplemental table
5. UI compares local facts against SimpleMDM-reported facts

### Best Use Case

This is the better option when:

- no existing MunkiReport module already collects the needed data
- the missing fact is device-local and time-sensitive
- you specifically want drift detection between MDM state and device reality

Examples:

- local MDM profile presence
- current console user
- current uptime
- a very small allowlisted set of app versions
- local health signals not available from SimpleMDM or other modules

## Implementation Status

This client-reporter path is now implemented in the module as Option B.

Implemented server-side pieces:

- `POST /module/simplemdm/index?op=ingest_client_facts`
- `simplemdm_client_fact`
- `simplemdm_client_fact_history`
- admin settings for enable/secret/allowlist/payload size/history
- device-page and client-tab rendering as `Client Reporter`

### Suggested Design Rules

- keep the fact set small and explicit
- always key records by `serial_number`
- do not allow the client reporter to overwrite authoritative SimpleMDM API fields
- store reporter data in a separate supplemental table
- clearly label it as client-reported in the UI

Recommended trust model:

- SimpleMDM API sync remains source of truth for MDM/control-plane data
- client-side reporter remains source of truth only for local device-reality facts

### Suggested Data Model

Do not merge client data into the main `simplemdm` table.

Use a separate supplemental table keyed by serial number and fact identity.

Example shape:

- `serial_number`
- `fact_type`
- `fact_key`
- `fact_value`
- `reported_at`
- `source`
- `raw_json`

Latest design note:

- the detailed plan now recommends a typed current-value table for Option 2 and an optional separate history table if auditability is needed later

### Suggested Endpoint Model

The cleanest shape would be a dedicated endpoint such as:

- `ingest_client_facts`

That endpoint should:

- require explicit authentication
- validate an allowlist of accepted fields
- upsert by serial number and fact identity
- reject attempts to write authoritative MDM state

### Pros

- can collect facts that SimpleMDM and other modules do not expose
- useful for drift detection and local reality checks
- can provide fresher local state than remote API inventory
- does not depend on other modules being installed

### Cons

- requires a new client-side component to build, deploy, and support
- adds a new ingest path and trust boundary
- increases operational complexity
- introduces new authentication and validation requirements
- can duplicate data already available from existing modules if not scoped carefully
- harder to justify when MunkiReport already has modules for the same facts

## Choosing Between The Two

Preferred default:

- use Option 1 first whenever another MunkiReport module already collects the needed data

Use Option 2 only when:

- the fact does not already exist in a usable module
- you need direct local-device truth
- the operational value justifies a new reporting path

Also consider:

- whether freshness matters enough to justify Option 2
- whether the same need could be solved by an Option 1 summary/index instead
- whether the data belongs in a future external-system integration instead of a client reporter

## Recommended Product Position

For this module, the best overall path is:

1. implement cross-module supplemental device views first
2. keep data source attribution explicit
3. preserve source-module ownership of collected data
4. consider a client-side reporter only for clearly missing device-local facts

That keeps the module aligned with MunkiReport’s strengths:

- multiple focused modules
- per-device joins by `serial_number`
- additive reporting without forcing everything into one collector

It also keeps the long-term roadmap cleaner:

- Option 1 for internal MunkiReport module enrichment
- Option 2 for narrow local-device gaps
- external cache/import integrations later for systems outside MunkiReport

## Summary

There are two valid paths for supplemental data:

- Option 1: cross-module enrichment
- Option 2: client-side reporter add-on

For the current `simplemdm` module, Option 1 is the better default.

It is simpler, safer, more maintainable, and better aligned with the use case of filling missing detail areas from existing MunkiReport modules without overwriting SimpleMDM or other module collections.

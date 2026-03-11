# SimpleMDM Client Reporter Add-On Proposal

This document describes a proposed future add-on for the `simplemdm` module: a client-side reporter that could run on each Mac and send supplemental facts into the module.

This is a proposal and design note only.

It is not implemented in the current module.

Current module behavior remains server-side:

- `simplemdm_sync.py` performs the actual SimpleMDM API sync
- the module does not currently ship a client-side `simplemdm` reporter
- no client-side `simplemdm` ingest path is currently implemented for this proposal

## Proposal Purpose

If this add-on is ever implemented, the client-side reporter would be a small script that runs on the Mac and sends supplemental facts to the `simplemdm` module.

Think of it as:

- not the main SimpleMDM sync
- not a replacement for `simplemdm_sync.py`
- just a local fact collector on each Mac

The existing server-side workflow would remain the primary integration:

- `simplemdm_sync.py` would still sync authoritative SimpleMDM API data into MunkiReport
- the client-side reporter would only add device-reality facts that are useful to compare against MDM data

## Why Consider It

The SimpleMDM API shows server-side MDM and inventory state.

A local reporter can add what the device sees right now.

That is useful when you need to compare:

- `SimpleMDM reported`
- `Client reported`
- `Mismatch / drift`

Examples:

- SimpleMDM says FileVault is enabled, but the Mac says FileVault is off
- SimpleMDM says the device is enrolled, but the Mac no longer has the expected MDM profile
- SimpleMDM inventory is stale, but the Mac checked in with Munki recently
- SimpleMDM shows software assigned, but the Mac does not have the expected version installed

## Proposed Behavior

The proposed client-side reporter would:

1. run on the Mac
2. collect a limited set of local facts
3. package them as JSON
4. POST them to a new `simplemdm` endpoint
5. let the module store them as client-reported supplemental data

## Typical Data It Could Collect

Good candidates:

- serial number
- current console user
- FileVault state
- local MDM profile presence
- Munki last run result
- uptime
- key app versions

High-value categories:

- local MDM health
- local security state
- current user/session context
- Munki health
- critical app/version facts

## What It Could Look Like Operationally

The client-side reporter could be:

- a shell script
- a Python script
- a LaunchDaemon or LaunchAgent-backed script
- a script invoked through the normal MunkiReport reporting workflow

Most likely deployment models:

- installed alongside normal MunkiReport client reporting
- run on a schedule
- run during postflight/reporting

In MunkiReport terms, it would likely feel like an additional client report item:

- same general idea as other client modules reporting inventory
- but scoped specifically to `simplemdm` supplemental facts

## Example Payload

If this proposal is implemented, the payload could look like this:

```json
{
  "serial_number": "C02XXXXXXX",
  "reported_at": "2026-03-11T15:20:00Z",
  "facts": {
    "console_user": "jdoe",
    "filevault_enabled": true,
    "mdm_profile_present": true,
    "munki_last_run_result": "success",
    "uptime_seconds": 86400
  }
}
```

## Proposed Reporter Requirements

The proposed reporter would need:

- where the MunkiReport server is
- how to authenticate
- how to get the device serial number
- which facts are allowed to send

## Suggested Design Rules

If this add-on is built, keep the design narrow and explicit:

- only send a small allowlisted set of facts
- always include serial number
- keep payloads simple
- do not send secrets
- do not let the client write authoritative MDM state
- do not silently overwrite SimpleMDM API fields with client-submitted values

Recommended trust model:

- SimpleMDM API sync remains the source of truth for MDM/control-plane data
- client-side reporting is supplemental device-reality data

## Suggested Data Model

Do not merge client data directly into the main `simplemdm` table.

Instead, use a separate supplemental table keyed by serial number.

Example shape:

- `serial_number`
- `fact_type`
- `fact_key`
- `fact_value`
- `reported_at`
- `source`
- `raw_json`

This keeps:

- API-synced data separate
- client-reported data separate
- comparison logic explicit in the UI

## Suggested Endpoint Model

The cleanest shape would likely be a dedicated endpoint such as:

- `ingest_client_facts`

That endpoint should:

- require authentication
- validate an allowlist of accepted fields
- upsert by serial number and fact identity
- reject attempts to write authoritative MDM state

## Authentication Options

If this is built, the client reporter should not be anonymous.

Reasonable options:

- reuse MunkiReport client auth/passphrase behavior
- use a dedicated shared secret for the supplemental endpoint
- require a signed header or signed payload

Whatever option is chosen, it should be:

- explicit
- documented
- separate from browser-only admin session assumptions

## Best Initial Scope

If this add-on is built, start small.

Recommended first scope:

1. local MDM profile presence
2. FileVault enabled state
3. current console user
4. Munki last run result
5. uptime

That gives useful operational value without creating a second inventory platform.

## UI Recommendations

If surfaced in the module UI, keep the data clearly labeled:

- `SimpleMDM Reported`
- `Client Reported`
- `Mismatch / Drift`

Do not blur the sources together in a single unlabeled field.

## Relationship To Current Module

Current module behavior today:

- server-side worker: `scripts/simplemdm_sync.py`
- server-side ingest and sync state handled by `simplemdm_controller.php`
- no current client-side `simplemdm` report item is registered in `provides.yml`

So this proposed add-on would be:

- optional
- supplemental
- separate from the existing SimpleMDM API sync workflow

## Summary

If implemented, the client-side reporter would be:

- a local probe on the Mac
- that feeds the module a supplemental device-reality record
- separate from the server-side SimpleMDM API sync

It would be useful for:

- local MDM health checks
- security state validation
- Munki/SimpleMDM cross-checks
- app/version reality checks

It should not replace the current server-side API sync model.

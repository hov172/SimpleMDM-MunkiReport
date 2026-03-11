# SimpleMDM Supplemental Data Options

This document describes two possible ways to add supplemental device information to the `simplemdm` module.

This is a proposal and design note only.

It is not implemented in the current module.

Current module behavior remains:

- `simplemdm_sync.py` performs the actual SimpleMDM API sync
- the module stores authoritative SimpleMDM API data in its own tables
- no client-side `simplemdm` reporter is currently implemented
- no cross-module supplemental device view is currently implemented

## Recommended Direction

The best-fit design is:

- keep SimpleMDM API sync as the source of truth for MDM data
- keep other MunkiReport modules as the source of truth for their own collected data
- let `simplemdm` read supplemental data from those other module tables when rendering device details, tabs, reports, or new widgets

This means the preferred model is not a second sync pipeline.

It is a read-only enrichment layer built on top of existing module data.

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

The normal pattern would be:

1. load the `simplemdm` device row
2. get the device `serial_number`
3. check whether supported module tables exist
4. query those module tables by `serial_number`
5. return the extra data as supplemental fields or sections

### Best Use Case

This is the best use case when:

- another module already collects the missing information
- the data should remain owned by that source module
- you want richer device details without creating a new reporting agent
- you want to avoid overwriting `simplemdm` API fields

This is the best fit for filling areas that SimpleMDM does not expose in its API.

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
- optionally cache capability checks or summary responses for performance

### Pros

- no new client agent required
- no new ingest path required
- no duplication of ownership between modules
- easier to maintain module boundaries
- low risk of overwriting authoritative SimpleMDM data
- simplest upgrade story for the `simplemdm` module
- easiest way to fill gaps in the SimpleMDM device page

### Cons

- depends on other modules already being installed and collecting data
- schemas vary by module, so each integration is module-specific
- live joins can become expensive if used heavily in dashboards
- availability of supplemental data depends on the health of the source module
- not useful for facts that no existing module collects

### Recommended Files To Change

- `simplemdm_controller.php`
- `views/simplemdm_device.php`
- `views/simplemdm_tab.php`
- optional new widget views under `views/`
- `docs/API_REFERENCE.md` if new merged endpoints are added

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

## Summary

There are two valid paths for supplemental data:

- Option 1: cross-module enrichment
- Option 2: client-side reporter add-on

For the current `simplemdm` module, Option 1 is the better default.

It is simpler, safer, more maintainable, and better aligned with the use case of filling missing detail areas from existing MunkiReport modules without overwriting SimpleMDM or other module collections.

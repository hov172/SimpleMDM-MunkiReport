# Security Guide

This document explains authentication, secrets, trust boundaries, and hardening recommendations for the SimpleMDM module.

## 1) Security Model

The module has four primary write paths:

1. Sync ingest (`ingest`, `ingest_resources`, `ingest_commands`, `update_sync_status`)
2. Sync queue control (`request_sync`, `begin_sync_run`)
3. Webhook ingest (`webhook`)
4. Device API passthrough (`api_devices`, mutating operations)

Read/report/listing routes require a normal authenticated MunkiReport session.

## 2) Auth and Secret Matrix

| Route/Area | Auth Requirement | Secret/Header |
|---|---|---|
| `index?op=ingest` | Sync auth required | `X-SIMPLEMDM-API-KEY` |
| `index?op=ingest_resources` | Sync auth required | `X-SIMPLEMDM-API-KEY` |
| `index?op=ingest_commands` | Sync auth required | `X-SIMPLEMDM-API-KEY` |
| `index?op=update_sync_status` | Sync auth required | `X-SIMPLEMDM-API-KEY` |
| `index?op=begin_sync_run` | Sync auth required | `X-SIMPLEMDM-API-KEY` |
| `index?op=get_config` | Global admin OR sync auth | Session auth or `X-SIMPLEMDM-API-KEY` |
| `index?op=webhook` | Webhook secret OR sync auth | `X-SIMPLEMDM-WEBHOOK-SECRET` or `X-SIMPLEMDM-API-KEY` |
| `save_config` | Global admin OR sync auth | Session auth or `X-SIMPLEMDM-API-KEY` |
| `request_sync` | Global admin session | Session auth |
| `api_devices` `GET` | Global admin session | Session auth |
| `api_devices` mutating methods (`POST/PATCH/PUT/DELETE`) | Global admin + action secret | `X-SIMPLEMDM-ACTION-SECRET` (or supported aliases/body/query key) |

Notes:
- `api_key` is not returned to non-global users.
- Sync-auth `get_config` callers receive non-secret settings plus `*_set` flags, not raw secret values.
- `webhook_secret` and `action_api_secret` are masked for non-global users (`*_set` flags only).

## 3) Secrets and Purpose

## `api_key`

- Purpose:
  - Authorizes sync ingest/update endpoints.
  - Authorizes worker-side sync claim calls (`begin_sync_run`).
  - Used by controller passthrough to authenticate to SimpleMDM upstream API.
- Storage:
  - `simplemdm_config` (`name=api_key`).
- Exposure:
  - Hidden from non-global config responses.

## `webhook_secret`

- Purpose:
  - Validates webhook sender authenticity.
- Storage:
  - `simplemdm_config` (`name=webhook_secret`).
- Header:
  - Preferred: `X-SIMPLEMDM-WEBHOOK-SECRET`.

## `action_api_secret`

- Purpose:
  - Additional safety gate for mutating passthrough actions.
- Storage:
  - `simplemdm_config` (`name=action_api_secret`).
- Accepted inputs:
  - Header: `X-SIMPLEMDM-ACTION-SECRET` (preferred)
  - Also supported: legacy aliases and `action_secret` in body/query for compatibility.

## 4) Secret Rotation Procedure

1. Generate new secret material (API key/secret) in your secret manager.
2. Update value in `Admin -> SimpleMDM Settings`.
3. Update all callers:
   - Sync runner env/headers
   - Webhook sender header
   - Any automation using mutating `api_devices` operations
4. Run immediate smoke checks:
   - Sync script manual run
   - Queued `Sync Now` request picked up by cron/manual runner
   - Webhook test POST
   - One safe device action (`refresh`)
5. Remove/disable old secret in upstream systems.

## 5) Hardening Recommendations

1. Keep MunkiReport behind HTTPS and trusted reverse proxy/TLS termination.
2. Restrict access to admin/global accounts; do not use shared operator accounts.
3. Set all three secrets in production:
   - `api_key`
   - `webhook_secret`
   - `action_api_secret`
4. Restrict network ingress to MunkiReport where possible (WAF, IP ACL, VPN).
5. Run sync from trusted host only; do not expose sync runner credentials broadly.
6. Treat queued sync controls as privileged operations:
   - `request_sync` should remain global-admin only
   - `begin_sync_run` should remain sync-token only
   - do not invoke Python directly from PHP/web requests
7. Monitor logs for repeated unauthorized attempts on:
   - `index?op=webhook`
   - `request_sync`
   - `index?op=begin_sync_run`
   - `api_devices` mutating calls
8. Rotate secrets on staff turnover or suspected exposure.

## 6) Logging and Sensitive Data

1. Do not print API key or action secret in plain logs.
2. Be careful with verbose debugging output and captured request payloads.
3. Treat webhook payload storage (`simplemdm_webhook_event.payload_json`) as sensitive operational data.
4. If exporting logs to SIEM, redact secret-like headers and tokens.

## 7) Security Validation Checklist

1. Non-global user cannot retrieve raw `api_key`, `webhook_secret`, or `action_api_secret`.
2. Ingest endpoints reject requests missing `X-SIMPLEMDM-API-KEY`.
3. `begin_sync_run` rejects requests missing `X-SIMPLEMDM-API-KEY`.
4. `request_sync` rejects non-global sessions.
5. Webhook endpoint rejects invalid secret and invalid sync token.
6. Mutating `api_devices` call fails without valid action secret.
7. Read-only `api_devices` calls still require global admin session.

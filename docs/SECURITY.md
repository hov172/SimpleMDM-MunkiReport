# Security Guide

This document explains authentication, secrets, trust boundaries, and hardening recommendations for the SimpleMDM module.

## 1) Security Model

The module has six primary write paths:

1. Sync ingest (`ingest`, `ingest_resources`, `ingest_commands`, `update_sync_status`)
2. Sync queue control (`request_sync`, `begin_sync_run`)
3. Webhook ingest (`webhook`)
4. Device API passthrough (`api_devices`, mutating operations)
5. Client reporter ingest (`ingest_client_facts`)
6. MCP findings ingest (`ingest_mcp_findings`) plus the four finding admin-action
   routes (`acknowledge/resolve/ignore/suppress_mcp_finding`) — sync-token
   authenticated. As of SimpleMDM-MCP v0.34.0 this is a potentially
   **high-frequency automated** write path (the MCP's auto-publish middleware, when
   enabled, pushes after eligible tool calls, not just when a human runs a push).
   `replace: true` auto-resolve is scoped to the pushing `source` namespace, so a
   misbehaving or compromised publisher can only mass-resolve findings in its own
   namespace, never another source's. As of 2026-07-11, the four admin-action routes also
   accept a global-admin session, so the module's own device page and findings list page
   can drive the lifecycle without exposing the sync token to the browser. Also as of
   2026-07-11, both `ingest_mcp_findings` and the four admin-action routes conditionally
   write one deduplicated fleet findings summary event — the write is gated by the
   `mcp_findings_event_enabled` setting, which is **off by default** (existing installs'
   Events UI does not change without explicit opt-in), and when on, the write is confined
   to a single module key, `simplemdm_mcp_findings_summary` — it never writes to any
   built-in or custom `simplemdm_*` event key.

Most read/report/listing routes require a normal authenticated MunkiReport session.
A narrow allowlist of read-only module data routes also accepts the sync token header
for headless clients.

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
| `index?op=ingest_client_facts` | Client reporter secret | `X-SIMPLEMDM-CLIENT-SECRET` |
| `index?op=ingest_mcp_findings` | Sync auth required | `X-SIMPLEMDM-API-KEY` |
| `index?op=acknowledge/resolve/ignore/suppress_mcp_finding` | Sync auth OR global-admin session | `X-SIMPLEMDM-API-KEY` or session auth |
| Token-readable module data routes | Session auth OR sync auth | Session auth or `X-SIMPLEMDM-API-KEY` |
| `save_config` | Global admin only | Session auth |
| `request_sync` | Global admin session | Session auth |
| `api_devices` `GET` | Global admin session | Session auth |
| `api_devices` mutating methods (`POST/PATCH/PUT/DELETE`) | Global admin + action secret | `X-SIMPLEMDM-ACTION-SECRET` (or supported aliases/body/query key) |

Notes:
- `api_key` is not returned to non-global users.
- Sync-auth `get_config` callers receive non-secret settings plus `*_set` flags, not raw secret values.
- `webhook_secret` and `action_api_secret` are masked for non-global users (`*_set` flags only).
- Token-readable module data routes are limited to read-only dashboard/detail/MCP-readback
  endpoints; mutating operations still require their route-specific auth checks.
- The token list includes `get_client_facts/{serial}` (since 2026-07-08): per-device
  endpoint facts (console user, uptime, local FileVault state) are readable by anyone
  holding the SimpleMDM API key. Serve the module over HTTPS only and rotate the key if it
  may have leaked.
- `save_config` requires a global-admin session; the sync token is no longer accepted
  as an alternative (fixed 2026-07-10). No legitimate caller ever used the sync-token
  branch, but it previously let a non-global authenticated session that also carried a
  valid `X-SIMPLEMDM-API-KEY` header bypass the `authorized('global')` scope check
  inside `save_config()` and rewrite `client_reporter_secret` and other admin-only
  secrets/toggles. Requests carrying only the sync token and no session were never
  reachable — the controller's constructor already blocks those before `save_config()`
  runs.

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

## `client_reporter_secret`

- Purpose:
  - Authenticates Option B client-reported fact submissions.
- Storage:
  - `simplemdm_config` (`name=client_reporter_secret`).
- Header:
  - `X-SIMPLEMDM-CLIENT-SECRET`.
- Current trust model:
  - shared secret plus HTTPS, allowlisted fact keys, payload-size limits, and type validation.
  - optional hardening can also require HMAC signing, replay protection, per-device tokens, and trusted-proxy/IP controls.
- Boundary:
  - suitable for controlled supplemental reporting
  - stronger per-device assurance requires the optional token or future certificate-based paths

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
4. If Option B is enabled, also set and protect `client_reporter_secret`.
5. Restrict network ingress to MunkiReport where possible (WAF, IP ACL, VPN).
6. Run sync from trusted host only; do not expose sync runner credentials broadly.
7. Treat queued sync controls as privileged operations:
   - `request_sync` should remain global-admin only
   - `begin_sync_run` should remain sync-token only
   - do not invoke Python directly from PHP/web requests
8. Keep the Option B allowlist narrow and avoid accepting high-impact or sensitive fields.
9. Monitor logs for repeated unauthorized attempts on:
   - `index?op=webhook`
   - `index?op=ingest_client_facts`
   - `request_sync`
   - `index?op=begin_sync_run`
   - `api_devices` mutating calls
   - `index?op=ingest_mcp_findings` (now an automated write endpoint when the
     MCP's auto-publish middleware is enabled — a spike from an unexpected
     `source` slug is a signal worth investigating)
10. Rotate secrets on staff turnover or suspected exposure.

## 6) Optional Hardening For Option B

The current module now supports these optional Option B hardening layers:

1. HMAC-signed requests
   - each client request includes a signature over the body plus timestamp
   - server verifies integrity and sender knowledge of the shared secret
2. Timestamp and nonce replay protection
   - reject stale or repeated client submissions
   - reduces replay risk if a request is captured
3. Per-device or per-group reporter tokens
   - limits blast radius compared with one global client secret
   - allows selective revocation
4. Reverse-proxy or IP allowlist enforcement
   - restricts where Option B ingest can be called from
5. Optional mTLS or certificate-bound client identity
   - strongest path for hostile or highly regulated environments

Implemented optional controls:

1. HMAC signatures
2. timestamp + nonce replay checks
3. per-device or scoped reporter tokens
4. trusted-proxy enforcement
5. IP allowlist filtering

Still future-only:

1. mTLS or certificate-bound client identity

These controls improve client submission trust without changing the current supplemental data model.

## 7) Logging and Sensitive Data

1. Do not print API key or action secret in plain logs.
2. Be careful with verbose debugging output and captured request payloads.
3. Treat webhook payload storage (`simplemdm_webhook_event.payload_json`) as sensitive operational data.
4. If exporting logs to SIEM, redact secret-like headers and tokens.

## 8) Security Validation Checklist

1. Non-global user cannot retrieve raw `api_key`, `webhook_secret`, or `action_api_secret`.
2. Ingest endpoints reject requests missing `X-SIMPLEMDM-API-KEY`.
3. `begin_sync_run` rejects requests missing `X-SIMPLEMDM-API-KEY`.
4. Token-readable module data routes accept a valid `X-SIMPLEMDM-API-KEY`.
5. Token-readable module data routes reject a missing or invalid `X-SIMPLEMDM-API-KEY`
   when no MunkiReport session is present.
6. `request_sync` rejects non-global sessions.
7. Webhook endpoint rejects invalid secret and invalid sync token.
8. `ingest_client_facts` rejects requests missing or invalid `X-SIMPLEMDM-CLIENT-SECRET`.
9. If HMAC is enabled, `ingest_client_facts` rejects missing or invalid signature/timestamp headers.
10. If replay protection is enabled, `ingest_client_facts` rejects reused nonces.
11. If per-device tokens are enabled, `ingest_client_facts` rejects missing or invalid device tokens.
12. If proxy-only or IP allowlist controls are enabled, `ingest_client_facts` rejects requests outside those network rules.
13. Mutating `api_devices` call fails without valid action secret.
14. Read-only `api_devices` calls still require global admin session.
15. `save_config` rejects a non-global authenticated session that carries a valid
    `X-SIMPLEMDM-API-KEY` header but lacks global-admin scope.

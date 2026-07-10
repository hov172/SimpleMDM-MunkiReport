# `save_config` — Require Admin Session Only — Design

**Status:** Approved (self-directed under full-auto-mode PRD-completion authorization, 2026-07-10 — see project memory)
**Date:** 2026-07-10
**Reference:** Flagged as an open security question during the 2026-07-09 admin-settings slice ("save_config accepts the sync token, not just admin sessions"); investigated and resolved this session.

## Problem

`save_config()` (`simplemdm_controller.php:3744`) accepts a POST from either a global-admin session OR a valid `X-SIMPLEMDM-API-KEY` sync token, and its `$config_keys` allowlist covers ~45 settings uniformly under that same auth check — including `api_key`, `webhook_secret`, `action_api_secret`, `client_reporter_secret`, and every feature toggle (`mcp_findings_enabled`, `allow_module_script_execution`, etc.). Anyone holding just the sync token can rewrite any of these.

This matters concretely because `docs/SECURITY.md`'s own trust matrix treats `ingest_client_facts` (Option B client-reported facts) as a *separate* trust tier gated by its own `client_reporter_secret` — not reachable via sync-token auth. A sync-token holder can defeat that boundary today by calling `save_config` to set `client_reporter_secret` to a value of their choosing, then calling `ingest_client_facts` with it — escalating from "holds the sync token" to "also holds Option B client-reporter access," which the security doc's own model treats as a distinct, narrower-trust capability.

## Investigation (why this is safe to fix, not a deliberate design choice worth preserving)

Traced every real caller of `save_config`:
- `views/simplemdm_admin.php`'s six `$.post(... 'save_config' ...)` call sites all run inside the authenticated admin UI — session auth, never a sync-token header.
- `scripts/simplemdm_sync.py` and `scripts/simplemdm_client_reporter_hardened.py` — grepped for `save_config` across both: zero matches. The sync worker never calls this route.
- The `sync_last_*`/`last_sync_cursor` telemetry keys that DO appear in `save_config`'s `$config_keys` array are a red herring: `scripts/simplemdm_sync.py` actually posts those fields to `update_sync_status` (`simplemdm_controller.php:4758`), a **separate**, already-correctly-sync-token-scoped endpoint with its own narrow field-list — not to `save_config` at all.

Conclusion: `save_config`'s sync-token auth branch has no legitimate caller anywhere in this codebase today. It is pure attack surface with zero functional purpose — not a considered trust decision that trades convenience for risk.

## Fix

Remove the sync-token branch from `save_config()`'s auth check; require `$this->authorized('global')` unconditionally, matching how `request_sync` (an equally privileged action) is already gated.

No change to `get_config()` — its sync-token-readable path stays as documented (masked secrets for non-global callers), that's a genuinely used, correctly-scoped read path (`SECURITY.md` §2's masking notes describe real, exercised behavior).

## Non-goals

- No change to any other route's auth model.
- No change to `save_config`'s `$config_keys` allowlist content or per-key validation — only the auth gate at the top of the function.
- No new tests added to the PHPUnit model-layer suite from the earlier slice — this is controller/HTTP-layer auth logic, same category explicitly deferred to a future "Phase B" in that plan. Verification here is a live `curl` check against the running Docker container (same class of verification the 2026-07-09 admin-settings work already used for `mcp_findings_enabled`).

## Docs to update

`docs/SECURITY.md` (matrix row + §8 checklist item), `docs/API_REFERENCE.md` (two references), `CHANGELOG.md` (`[Unreleased]` → security fix entry).

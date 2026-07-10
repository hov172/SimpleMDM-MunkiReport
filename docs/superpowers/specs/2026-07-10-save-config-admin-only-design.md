# `save_config` — Require Admin Session Only — Design

**Status:** Approved (self-directed under full-auto-mode PRD-completion authorization, 2026-07-10 — see project memory)
**Date:** 2026-07-10
**Reference:** Flagged as an open security question during the 2026-07-09 admin-settings slice ("save_config accepts the sync token, not just admin sessions"); investigated and resolved this session.

## Problem

`save_config()` (`simplemdm_controller.php:3744`) accepts a POST from either a global-admin session OR a valid `X-SIMPLEMDM-API-KEY` sync token, and its `$config_keys` allowlist covers ~45 settings uniformly under that same auth check — including `api_key`, `webhook_secret`, `action_api_secret`, `client_reporter_secret`, and every feature toggle (`mcp_findings_enabled`, `allow_module_script_execution`, etc.).

**Correction (found during implementation, 2026-07-10):** the constructor (`simplemdm_controller.php:20-69`) gates every action except those listed in `$sync_actions`/`$token_read_actions` behind `$this->authorized()` — a *session* check — before any request reaches a method body at all. `save_config` is in neither list, so a request carrying *only* the sync-token header and no session never reaches `save_config()`'s own auth check; it's rejected earlier with `die('Authenticate first.')`. Verified live: a sync-token-only curl request against the real route returns `Authenticate first.`, both before and after this fix (confirmed via `git stash`).

So the reachable risk is narrower than "any sync-token holder": it requires an authenticated MunkiReport session (any scope — `authorized()` with no argument, which the constructor requires, does not itself require global/admin scope) that *also* carries a valid sync-token header. Such a session passes the constructor gate normally, then previously could use `$is_sync_auth` inside `save_config()` itself to bypass the `authorized('global')` scope check specifically — letting a non-global authenticated user who also knows the sync token write global-admin-only settings, including `client_reporter_secret`. `docs/SECURITY.md`'s own trust matrix treats `ingest_client_facts` (gated by `client_reporter_secret`) as a separate, narrower trust tier — this path let such a user grant themselves that tier.

This is a real, worthwhile fix (removing a redundant OR-branch that only ever matters in this narrower, still-legitimate scenario), just not the broader "anonymous sync-token holder" framing originally written here — corrected for accuracy in the docs updates below.

## Investigation (why this is safe to fix, not a deliberate design choice worth preserving)

Traced every real caller of `save_config`:
- `views/simplemdm_admin.php`'s eight `$.post(... 'save_config' ...)` call sites all run inside the authenticated admin UI — session auth, never a sync-token header.
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

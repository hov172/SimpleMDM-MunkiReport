# save_config Admin-Only Auth Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close a real privilege-escalation path: `save_config` currently accepts either an admin session OR the sync token, letting a sync-token holder rewrite `client_reporter_secret` (and other secrets/toggles) and defeat that route's separately-documented trust boundary. Investigation confirmed zero legitimate callers use the sync-token path. Require admin session only.

**Architecture:** One-line auth-check change in `simplemdm_controller.php:save_config()`, verified live against the running Docker container (no unit-test harness covers controller/HTTP auth yet — this is explicitly deferred "Phase B" scope from the earlier PHP test-coverage slice), plus three doc updates to keep `SECURITY.md`/`API_REFERENCE.md`/`CHANGELOG.md` accurate.

**Tech Stack:** PHP, verified via `curl` against the live `docker compose` container at `http://localhost:8888`.

## Global Constraints

- No change to `get_config()`'s auth model (its sync-token-readable path is real, used, and correctly masks secrets already — out of scope).
- No change to `save_config`'s `$config_keys` allowlist content or per-key validation logic — only the auth gate.
- Verification is live-request-based (curl against the real container), not a new PHPUnit test — this is controller/HTTP-layer logic, the same category already deferred to a future phase in the prior PHP test-coverage plan.

---

### Task 1: Restrict `save_config` to admin-session auth, verify live

**Files:**
- Modify: `simplemdm_controller.php:3744-3751` (`save_config()`'s auth check)

**Interfaces:** None — this is a leaf auth check inside one controller method, no other code calls into it.

- [ ] **Step 1: Confirm current content**

Run: `sed -n '3744,3752p' simplemdm_controller.php`

Confirm it still reads:

```php
    public function save_config()
    {
        $this->connectDB();
        $is_sync_auth = $this->is_valid_sync_token();
        if (! $is_sync_auth && ! $this->authorized('global')) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
```

If it has drifted, stop and report rather than blindly editing.

- [ ] **Step 2: Make the change**

Replace with:

```php
    public function save_config()
    {
        $this->connectDB();
        if (! $this->authorized('global')) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
```

- [ ] **Step 3: Syntax check**

Run (from the host repo root, `<repo-root>`): `docker compose run --rm munkireport php -l local/modules/simplemdm/simplemdm_controller.php`

Expected: `No syntax errors detected`.

- [ ] **Step 4: Run the existing PHPUnit suite to confirm no regression**

Run: `docker compose run --rm munkireport local/modules/simplemdm/vendor/bin/phpunit -c local/modules/simplemdm/phpunit.xml`

Expected: `OK (41 tests, 74 assertions)` — this change doesn't touch any of the extracted model methods those tests cover, so this is a pure regression check, not expected to exercise the new behavior.

- [ ] **Step 5: Live-verify the fix actually rejects a sync-token-only request**

Ensure the container is up (`docker compose up -d munkireport` from the host repo root if needed). Discover the currently-configured `api_key` the same way prior verification passes in this session did — do NOT print/echo the raw key value anywhere in your report or tool output; only confirm you found and used one (e.g. query it directly in a script without echoing it to stdout, or reference `<repo-root>/app/db/db.sqlite`'s `simplemdm_config` table `WHERE name='api_key'` from the host, since that file is bind-mounted and host-readable).

Send a POST to `save_config` using ONLY the sync-token header (no session cookie), e.g.:

```bash
curl -s -o /dev/null -w "%{http_code}\n" -X POST http://localhost:8888/module/simplemdm/save_config \
  -H "X-SimpleMDM-API-Key: <the configured key>" \
  -d "mcp_findings_enabled=0"
```

Expected AFTER this fix: NOT `200` with a success JSON body — the response should indicate unauthorized (check the actual body/status the route returns for its `jsonView(['status' => 'error', 'message' => 'Unauthorized'])` call; note the route may return HTTP 200 with an error `status` field in the JSON body rather than a non-200 HTTP status, since `jsonView` here isn't passed an explicit status code in this branch — confirm which is actually the case and report exactly what you observed, don't assume either).

If you have access to trigger a real authenticated admin-session POST (e.g. via `/browse` logging into the admin UI and using its existing "MCP Findings Settings" panel save button, or by re-using a session cookie obtained via `/browse`), do a second check confirming an authenticated admin request to the SAME route still succeeds — this confirms the fix didn't also break the legitimate path. If establishing an authenticated session is impractical in this pass, it's acceptable to reason about this from the code alone (the `authorized('global')` check is unchanged, unmodified by this diff, and is exercised identically to how every other admin-only route in this controller already uses it) — state which approach you took.

- [ ] **Step 6: Commit**

```bash
git add simplemdm_controller.php
git commit -m "fix(simplemdm): require admin session for save_config, remove sync-token auth path"
```

---

### Task 2: Update docs

**Files:**
- Modify: `docs/SECURITY.md` (matrix row at line 32, checklist — add one item confirming the new restriction)
- Modify: `docs/API_REFERENCE.md` (two references at lines ~38 and ~107)
- Modify: `CHANGELOG.md` (`[Unreleased]` section)

**Interfaces:** None — documentation only.

- [ ] **Step 1: Update `docs/SECURITY.md`**

Change the matrix row (currently line 32):

```
| `save_config` | Global admin OR sync auth | Session auth or `X-SIMPLEMDM-API-KEY` |
```

to:

```
| `save_config` | Global admin only | Session auth |
```

Add a note under the existing "Notes" bullet list (after the `webhook_secret`/`action_api_secret` masking note) explaining the change:

```
- `save_config` requires a global-admin session; the sync token alone is not
  sufficient (fixed 2026-07-10 — the sync worker never used this path, and
  it previously let a sync-token holder rewrite `client_reporter_secret` and
  other admin-only secrets/toggles).
```

Add a new item to the "Security Validation Checklist" (§8, currently ending at item 14):

```
15. `save_config` rejects a request carrying only a valid `X-SIMPLEMDM-API-KEY`
    header and no admin session.
```

- [ ] **Step 2: Update `docs/API_REFERENCE.md`**

Run: `grep -n "save_config" docs/API_REFERENCE.md` to find the exact current lines (approximately 38, 50, 107, 114 per earlier investigation — confirm before editing, this plan's line numbers may have drifted). Update each reference describing `save_config`'s auth requirement from "Global admin OR sync auth" / "Global admin OR sync token" (or however it's phrased at each site) to "Global admin session (session auth only)" — read enough surrounding context at each site to match the file's existing phrasing style, don't guess a format.

- [ ] **Step 3: Update `CHANGELOG.md`**

Read the current `[Unreleased]` section's structure (it currently has `### Added` and `### Changed` subsections per earlier reads this session). Add a `### Security` subsection (or `### Fixed` if the file has no established `### Security` convention — check for one first) with one entry:

```
- `save_config` now requires a global-admin session; previously it also accepted
  the sync token, which had no legitimate caller and allowed a sync-token holder
  to rewrite `client_reporter_secret` and other admin-only secrets/settings.
```

- [ ] **Step 4: Commit**

```bash
git add docs/SECURITY.md docs/API_REFERENCE.md CHANGELOG.md
git commit -m "docs(simplemdm): document save_config admin-only auth restriction"
```

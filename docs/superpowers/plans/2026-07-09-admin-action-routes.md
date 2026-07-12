# Admin Action Routes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add four admin action routes (`acknowledge_mcp_finding`, `resolve_mcp_finding`, `ignore_mcp_finding`, `suppress_mcp_finding`) to the `simplemdm` MunkiReport module so findings can have their `status` changed manually (by id, single or batch) outside of the automatic ingest lifecycle.

**Architecture:** One controller change plus one docs change inside the existing `simplemdm` MunkiReport module. `simplemdm_controller.php` gains a private helper `applyFindingStatusAction($targetStatus)` that does token auth, request parsing, a bulk existence check, a bulk status update, and response building — and four thin public route methods (one per PRD-named route) that each call it with a fixed target status. No new migration, no model changes (`Simplemdm_mcp_finding_model::STATUS_*` constants from the prior slice already cover every target status this plan needs), no UI, no suppression-rule table, no comments table.

**Tech Stack:** PHP 8.1, Illuminate/Database (Eloquent) with SQLite (local dev, `app/db/db.sqlite`, bind-mounted into the `munkireport-local` Docker container) and MySQL (production).

**Verification approach — deviates from strict PHPUnit TDD:** Matches the prior slice's established pattern (no PHPUnit coverage exists for this module's controllers): every task's "test" step is a live HTTP request (curl) against the running `munkireport-local` Docker container plus a direct `sqlite3` read of `app/db/db.sqlite` to assert row state.

## Global Constraints

- Design spec: `docs/superpowers/specs/2026-07-09-admin-action-routes-design.md` — this plan implements that spec; if any step here appears to contradict it, the spec governs and should be flagged.
- Auth: reuse the existing sync-token model (`is_valid_sync_token()` / `X-SIMPLEMDM-API-KEY`), identical to `ingest_mcp_findings` and `get_mcp_findings`. Do not add a new auth mechanism.
- Lookup key: global row `id` on `simplemdm_mcp_finding`, not scoped by `source`. Request body accepts `{"id": N}` or `{"ids": [N, ...]}` — exactly one of the two, `ids` non-empty.
- Transitions are unconditional: no state-machine validation, no rejection for "already in that status." Any of the four actions can be applied to a finding in any current status.
- `resolve_mcp_finding` sets `resolved_at` to now (UTC ISO-8601 via `gmdate('c')`, matching the format used elsewhere in this controller); the other three actions clear `resolved_at` to `null` if it was previously set, so `resolved_at` is only ever non-null when `status = resolved`.
- No audit fields (no actor, no reason, no timestamp beyond `resolved_at`) — explicitly deferred per the design spec.
- No new database migration — every status value these routes need (`STATUS_ACKNOWLEDGED`, `STATUS_RESOLVED`, `STATUS_IGNORED`, `STATUS_SUPPRESSED`) already exists as a `Simplemdm_mcp_finding_model` constant from the prior slice (`simplemdm_mcp_finding_model.php:29-33`).
- Do not implement: persistent suppression-rule matching, comment threads, admin UI, `get_mcp_finding_stats`/`export_mcp_findings`/`get_mcp_scan_status` — all explicitly out of scope per the design spec.

---

### Task 1: Controller — shared status-action helper and four admin routes

**Files:**
- Modify: `simplemdm_controller.php` — insert new methods after `get_mcp_findings()` (currently ends at line 6673, immediately before `get_events()` at line 6675 — confirm these line numbers haven't drifted before inserting; locate by method name if they have)
- Test: manual (`curl` against the live container + `sqlite3` row assertions)

**Interfaces:**
- Consumes: `Simplemdm_mcp_finding_model::STATUS_ACKNOWLEDGED`, `::STATUS_RESOLVED`, `::STATUS_IGNORED`, `::STATUS_SUPPRESSED` (all already defined in `simplemdm_mcp_finding_model.php:29-33` — no changes to that file), `$this->is_valid_sync_token()` (defined at `simplemdm_controller.php:2304`), `$this->connectDB()`, `jsonView()`.
- Produces: four new public routes — `POST acknowledge_mcp_finding`, `POST resolve_mcp_finding`, `POST ignore_mcp_finding`, `POST suppress_mcp_finding` — each accepting `{"id": N}` or `{"ids": [N, ...]}` and returning `{"status": "success", "requested": N, "updated": N, "not_found": [...]}`. No later task in this plan depends on this response shape (Task 2 is documentation-only).

- [ ] **Step 1: Insert the helper and four route methods**

Insert the following immediately after the closing `}` of `get_mcp_findings()` (currently line 6673) and before `public function get_events(...)` (currently line 6675):

```php
    private function applyFindingStatusAction($targetStatus)
    {
        $this->connectDB();
        if (! $this->is_valid_sync_token()) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        if (! is_array($data)) {
            jsonView(['status' => 'error', 'message' => 'Invalid JSON data'], 400);
            return;
        }

        $rawIds = [];
        if (isset($data['ids']) && is_array($data['ids'])) {
            $rawIds = $data['ids'];
        } elseif (isset($data['id'])) {
            $rawIds = [$data['id']];
        }

        $ids = [];
        foreach ($rawIds as $rawId) {
            if (is_numeric($rawId) && (int) $rawId > 0) {
                $ids[] = (int) $rawId;
            }
        }
        $ids = array_values(array_unique($ids));

        if (empty($ids)) {
            jsonView(['status' => 'error', 'message' => 'id or non-empty ids array is required (positive integers)'], 400);
            return;
        }

        $existingIds = Simplemdm_mcp_finding_model::whereIn('id', $ids)->pluck('id')->all();
        $existingIds = array_map('intval', $existingIds);
        $notFound = array_values(array_diff($ids, $existingIds));

        $update = ['status' => $targetStatus];
        if ($targetStatus === Simplemdm_mcp_finding_model::STATUS_RESOLVED) {
            $update['resolved_at'] = gmdate('c');
        } else {
            $update['resolved_at'] = null;
        }

        $updated = 0;
        if (! empty($existingIds)) {
            $updated = Simplemdm_mcp_finding_model::whereIn('id', $existingIds)->update($update);
        }

        jsonView([
            'status'    => 'success',
            'requested' => count($ids),
            'updated'   => $updated,
            'not_found' => $notFound,
        ]);
    }

    public function acknowledge_mcp_finding()
    {
        $this->applyFindingStatusAction(Simplemdm_mcp_finding_model::STATUS_ACKNOWLEDGED);
    }

    public function resolve_mcp_finding()
    {
        $this->applyFindingStatusAction(Simplemdm_mcp_finding_model::STATUS_RESOLVED);
    }

    public function ignore_mcp_finding()
    {
        $this->applyFindingStatusAction(Simplemdm_mcp_finding_model::STATUS_IGNORED);
    }

    public function suppress_mcp_finding()
    {
        $this->applyFindingStatusAction(Simplemdm_mcp_finding_model::STATUS_SUPPRESSED);
    }
```

- [ ] **Step 2: Verify PHP syntax**

```bash
docker compose -f <repo-root>/docker-compose.yml exec munkireport php -l /var/munkireport/local/modules/simplemdm/simplemdm_controller.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Seed two test findings to act on**

The dev container mounts `local/` and `app/db/` live, so no rebuild is needed.

```bash
TOKEN="<paste your simplemdm sync token here>"
BASE="http://localhost:8888/index.php?/module/simplemdm"

curl -s -X POST "$BASE/ingest_mcp_findings" \
  -H "Content-Type: application/json" \
  -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"admin_route_test","replace":false,
       "findings":[
         {"serial_number":"C02ADMINTEST1","finding_type":"admin_route_check_1","severity":"warning","message":"finding one"},
         {"serial_number":"C02ADMINTEST2","finding_type":"admin_route_check_2","severity":"info","message":"finding two"}
       ]}'
```

Expected JSON: `"inserted":2`.

```bash
sqlite3 <repo-root>/app/db/db.sqlite \
  "SELECT id, status, resolved_at FROM simplemdm_mcp_finding WHERE source='admin_route_test' ORDER BY id;"
```

Note the two `id` values returned — call them `$ID1` and `$ID2` for the remaining steps.

- [ ] **Step 4: Verify `acknowledge_mcp_finding` (single id)**

```bash
curl -s -X POST "$BASE/acknowledge_mcp_finding" \
  -H "Content-Type: application/json" \
  -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d "{\"id\": $ID1}"
```

Expected JSON: `{"status":"success","requested":1,"updated":1,"not_found":[]}`.

```bash
sqlite3 <repo-root>/app/db/db.sqlite \
  "SELECT status, resolved_at FROM simplemdm_mcp_finding WHERE id=$ID1;"
```

Expected: `status=acknowledged`, `resolved_at` empty.

- [ ] **Step 5: Verify `resolve_mcp_finding` sets `resolved_at`, then `acknowledge_mcp_finding` clears it**

```bash
curl -s -X POST "$BASE/resolve_mcp_finding" \
  -H "Content-Type: application/json" \
  -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d "{\"id\": $ID1}"
```

Expected JSON: `{"status":"success","requested":1,"updated":1,"not_found":[]}`.

```bash
sqlite3 <repo-root>/app/db/db.sqlite \
  "SELECT status, resolved_at FROM simplemdm_mcp_finding WHERE id=$ID1;"
```

Expected: `status=resolved`, `resolved_at` populated (non-empty).

```bash
curl -s -X POST "$BASE/acknowledge_mcp_finding" \
  -H "Content-Type: application/json" \
  -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d "{\"id\": $ID1}"
```

```bash
sqlite3 <repo-root>/app/db/db.sqlite \
  "SELECT status, resolved_at FROM simplemdm_mcp_finding WHERE id=$ID1;"
```

Expected: `status=acknowledged`, `resolved_at` empty again (cleared).

- [ ] **Step 6: Verify `ignore_mcp_finding` and `suppress_mcp_finding` (batch `ids`, including a non-existent id)**

```bash
curl -s -X POST "$BASE/ignore_mcp_finding" \
  -H "Content-Type: application/json" \
  -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d "{\"ids\": [$ID2, 999999999]}"
```

Expected JSON: `{"status":"success","requested":2,"updated":1,"not_found":[999999999]}`.

```bash
sqlite3 <repo-root>/app/db/db.sqlite \
  "SELECT status FROM simplemdm_mcp_finding WHERE id=$ID2;"
```

Expected: `status=ignored`.

```bash
curl -s -X POST "$BASE/suppress_mcp_finding" \
  -H "Content-Type: application/json" \
  -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d "{\"ids\": [$ID1, $ID2]}"
```

Expected JSON: `{"status":"success","requested":2,"updated":2,"not_found":[]}`.

```bash
sqlite3 <repo-root>/app/db/db.sqlite \
  "SELECT id, status, resolved_at FROM simplemdm_mcp_finding WHERE id IN ($ID1, $ID2);"
```

Expected: both rows `status=suppressed`, `resolved_at` empty (cleared, since suppress is not resolve).

- [ ] **Step 7: Verify malformed-request and auth-failure error paths**

```bash
# Missing id/ids entirely.
curl -s -X POST "$BASE/acknowledge_mcp_finding" \
  -H "Content-Type: application/json" \
  -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{}'
```

Expected JSON: `{"status":"error","message":"id or non-empty ids array is required (positive integers)"}` with HTTP 400.

```bash
# Missing/invalid token.
curl -s -X POST "$BASE/acknowledge_mcp_finding" \
  -H "Content-Type: application/json" \
  -d "{\"id\": $ID1}"
```

Expected JSON: `{"status":"error","message":"Unauthorized"}` with HTTP 401.

- [ ] **Step 8: Clean up test rows**

```bash
sqlite3 <repo-root>/app/db/db.sqlite \
  "DELETE FROM simplemdm_mcp_finding WHERE source='admin_route_test';"
```

- [ ] **Step 9: Commit**

```bash
cd <repo-root>
git add local/modules/simplemdm/simplemdm_controller.php
git commit -m "feat(simplemdm): add acknowledge/resolve/ignore/suppress admin action routes"
```

---

### Task 2: Documentation update

**Files:**
- Modify: `docs/API_REFERENCE.md` (new section documenting the four routes; append after the existing MCP Findings Lifecycle section, which currently ends at line 958)
- Modify: `CHANGELOG.md` (add new entry)
- Modify: `README.md` (one-paragraph mention with a link to the CHANGELOG entry, matching the style of the prior slice's README update)

**Interfaces:**
- Consumes: nothing (docs only) — describes the exact request/response shapes and behavior implemented in Task 1.
- Produces: nothing consumed by later tasks — this is the last task in this plan.

- [ ] **Step 1: Append a new section to `docs/API_REFERENCE.md`**

Append after the file's current final line (958, end of the "Default behavior (backward compatible)" bullets under "## 11) MCP Findings Lifecycle (ingest & read)"):

```markdown

## 12) MCP Findings Admin Actions

Four routes to manually change a finding's `status` outside of the automatic ingest lifecycle. All four share the same request/response shape and differ only in the target status they set.

### Authentication

Same sync-token model as `ingest_mcp_findings` and `get_mcp_findings`: `X-SIMPLEMDM-API-KEY: <sync-token>` header, validated via the module's existing `is_valid_sync_token()` check. No new auth mechanism.

### Routes

| Route | Method | Sets `status` to | Also sets |
|---|---|---|---|
| `acknowledge_mcp_finding` | POST | `acknowledged` | `resolved_at` cleared to `null` if it was set |
| `resolve_mcp_finding` | POST | `resolved` | `resolved_at` set to now (UTC ISO-8601) |
| `ignore_mcp_finding` | POST | `ignored` | `resolved_at` cleared to `null` if it was set |
| `suppress_mcp_finding` | POST | `suppressed` | `resolved_at` cleared to `null` if it was set |

Transitions are unconditional: any finding, in any current status, can be moved to any of these four target statuses via any of these four routes. There is no rejection for "already in that status" or an invalid-transition error.

**Note on scope:** `suppress_mcp_finding` only changes the status of the specific finding(s) named in the request. It does not create a persistent suppression rule that would automatically suppress future, not-yet-ingested findings — that is a separate, later feature (a dedicated suppression-rules table), not implemented by this route.

### Request

```json
{ "id": 42 }
```

or, for batch operation:

```json
{ "ids": [42, 43, 44] }
```

Exactly one of `id` (positive integer) or `ids` (non-empty array of positive integers) must be present. Ids are the `id` column on `simplemdm_mcp_finding` (as returned by `get_mcp_findings`), not scoped by `source` — a caller does not need to also know or pass `source`.

### Response

```json
{
  "status": "success",
  "requested": 3,
  "updated": 2,
  "not_found": [44]
}
```

- `requested`: count of ids in the request (after normalizing `id` → `ids`).
- `updated`: count of rows actually matched and updated.
- `not_found`: any requested ids that did not match an existing row.

### Errors

- `400` with `{"status":"error","message":"id or non-empty ids array is required (positive integers)"}` if the body is missing both `id` and `ids`, `ids` is empty, or no value is a positive integer.
- `400` with `{"status":"error","message":"Invalid JSON data"}` if the request body is not valid JSON.
- `401` with `{"status":"error","message":"Unauthorized"}` if the sync token is missing or invalid.
```

- [ ] **Step 2: Add a `CHANGELOG.md` entry**

Add to the top of the `## [Unreleased]` section (create the section if the top entry is no longer `[Unreleased]`):

```markdown
### Added
- Four admin action routes — `acknowledge_mcp_finding`, `resolve_mcp_finding`, `ignore_mcp_finding`, `suppress_mcp_finding` — to manually change a finding's status by id (single or batch), independent of the automatic ingest lifecycle. Same sync-token auth as the existing ingest/read routes. `suppress_mcp_finding` only changes the named finding's status; it does not create a persistent suppression rule for future findings.
```

- [ ] **Step 3: Update `README.md`**

Find the paragraph added in the prior slice describing the MCP findings lifecycle (search for "lifecycle status and history" or the CHANGELOG link it added). Immediately after that paragraph, add:

```markdown
Findings can also be transitioned manually via four admin action routes (`acknowledge_mcp_finding`, `resolve_mcp_finding`, `ignore_mcp_finding`, `suppress_mcp_finding`), independent of the automatic ingest lifecycle — see the CHANGELOG and `docs/API_REFERENCE.md` for the request/response shape.
```

- [ ] **Step 4: Commit**

```bash
cd <repo-root>
git add local/modules/simplemdm/README.md local/modules/simplemdm/CHANGELOG.md local/modules/simplemdm/docs/API_REFERENCE.md
git commit -m "docs(simplemdm): document admin action routes (acknowledge/resolve/ignore/suppress)"
```

---

## Explicitly Out of Scope for This Slice

- Persistent suppression rules / `simplemdm_mcp_finding_suppressions` table (PRD §16.3, Phase 2) — `suppress_mcp_finding` only changes the named finding's status.
- Comment threads / `simplemdm_mcp_finding_comments` table and `add_comment` route (PRD §16.3/§11.2, Phase 2).
- Any admin UI wiring these routes to buttons/forms — separate PRD phase (§22.1 Phase 2), not yet built.
- `get_mcp_finding_stats`, `export_mcp_findings`, `get_mcp_scan_status` — read-only routes listed alongside the admin routes in PRD §15.3, but not status-mutating actions; a separate slice.
- Audit fields (actor, reason) — no auth-identity model exists yet to attach them to meaningfully.
- State-transition validation — all four actions are unconditionally applicable from any status.

## Self-Review Notes

- **Spec coverage check:** Design spec's "Authentication" section → Task 1 Step 1 (`is_valid_sync_token()` reuse). "Request shape" section → Task 1 Step 1 (`id`/`ids` parsing). "Behavior per route" table → Task 1 Step 1 (four wrapper methods + `resolved_at` handling in the helper), verified per-route in Steps 4-6. "Response shape" → Task 1 Step 1 (`jsonView` call), verified in Steps 4-7. "Implementation shape" (shared private helper) → Task 1 Step 1 structure matches exactly. "Testing approach" → Task 1 Steps 3-8 (curl + sqlite3, single id, batch ids, mixed found/not-found, malformed body, bad auth). All design sections have a corresponding task step.
- **Backward compatibility check:** No existing route, model, or migration is modified — this plan only adds new methods to `simplemdm_controller.php` and new documentation. `ingest_mcp_findings` and `get_mcp_findings` (previous slice) are untouched.

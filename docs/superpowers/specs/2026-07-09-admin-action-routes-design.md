# Admin Action Routes — Design

**Status:** Approved
**Date:** 2026-07-09
**PRD reference:** `SimpleMDM_MCP_MunkiReport_Findings_Platform_PRD_SDS_v4` §11.2 ("Optional write/admin routes: acknowledge, set_status, suppress, resolve"), §15.3 (Admin/status routes table), §10.2 (status enum), §16.3 (supporting tables and their phase).

## Context

The MCP findings lifecycle slice (merged via PR #1) gave findings a `status` column with six values (`open`, `acknowledged`, `in_progress`, `resolved`, `ignored`, `suppressed`) and `Simplemdm_mcp_finding_model::STATUS_*` constants, but the only thing that can currently change a finding's status is the automatic ingest lifecycle (`ingest_mcp_findings`'s upsert/auto-resolve/reopen logic). There is no way for a human or script to manually acknowledge, resolve, ignore, or suppress a finding outside of a scan pushing new data.

This slice adds the four admin action routes the PRD names in §15.3: `acknowledge_mcp_finding`, `resolve_mcp_finding`, `ignore_mcp_finding`, `suppress_mcp_finding`.

## Scope

**In scope:**
- Four new POST routes on `simplemdm_controller.php`, each setting a finding's `status` to a fixed target value.
- Batch support: act on one id or an array of ids in a single call.
- Sync-token authentication, matching every other route in this module.

**Explicitly out of scope (later PRD phases):**
- Persistent suppression rules that auto-suppress future matching findings (`simplemdm_mcp_finding_suppressions` table — PRD §16.3 marks this Phase 2). `suppress_mcp_finding` in this slice only changes the status of the specific finding(s) named in the request; it does not create a rule that suppresses anything not yet ingested.
- Comment threads (`simplemdm_mcp_finding_comments` table — PRD §16.3 marks this Phase 2, and `add_comment` from §11.2's route list is not implemented here).
- Any admin UI (buttons, forms) that calls these routes — the widget/device-page UI work is a separate PRD phase (§22.1 Phase 2) not yet built.
- `get_mcp_finding_stats`, `export_mcp_findings`, `get_mcp_scan_status` — these are read-only routes listed alongside the admin routes in §15.3's table, but they're not status-mutating actions and belong in their own slice.
- Audit fields (actor, reason, timestamp-of-action beyond `resolved_at`) — deferred until there's an auth-identity model (session-based admin auth) worth attaching them to.
- State-transition validation (e.g. blocking "resolve" on an already-suppressed finding) — all four actions are unconditionally applicable from any current status.

## Design

### Authentication

Reuse the existing sync-token model (`is_valid_sync_token()`, checked via `X-SIMPLEMDM-API-KEY`), identical to `ingest_mcp_findings` and `get_mcp_findings`. No new auth mechanism. This keeps the four new routes consistent with the rest of the module and means they work equally from curl, scripts, or a future UI without a second auth path to build and test.

### Request shape

All four routes accept the same JSON body shape over POST:

```json
{ "id": 42 }
```

or, for batch operation:

```json
{ "ids": [42, 43, 44] }
```

Exactly one of `id` (int) or `ids` (non-empty array of ints) must be present. `id` is normalized internally to a single-element array so both forms share one code path.

Ids are **global row ids** (the `id` column on `simplemdm_mcp_finding`), not scoped by `source` — a caller acting on ids obtained from a prior `get_mcp_findings` response does not need to also know or pass `source`.

### Behavior per route

| Route | Sets `status` to | Also sets |
|---|---|---|
| `acknowledge_mcp_finding` | `Simplemdm_mcp_finding_model::STATUS_ACKNOWLEDGED` | `resolved_at = NULL` if it was previously set |
| `resolve_mcp_finding` | `Simplemdm_mcp_finding_model::STATUS_RESOLVED` | `resolved_at = now` (UTC ISO-8601, same format used elsewhere in this controller) |
| `ignore_mcp_finding` | `Simplemdm_mcp_finding_model::STATUS_IGNORED` | `resolved_at = NULL` if it was previously set |
| `suppress_mcp_finding` | `Simplemdm_mcp_finding_model::STATUS_SUPPRESSED` | `resolved_at = NULL` if it was previously set |

Transitions are unconditional: any finding, in any current status, can be moved to any of these four target statuses. There is no rejection for "already in that status" or "invalid transition" — matching how the existing ingest lifecycle already treats `status` as freely mutable, and avoiding an unspecified transition-rule matrix.

Clearing `resolved_at` on the three non-resolve actions mirrors the reopen behavior already built into `ingest_mcp_findings` (a resolved finding that recurs gets `resolved_at` cleared) — `resolved_at` should only ever be non-null when `status = resolved`.

### Response shape

Each route returns the same shape (batch-aware even for a single `id`):

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

If the request body is malformed (neither `id` nor `ids` present, `ids` empty, non-integer values), the route returns `400` with an error message, matching the validation-error style already used in `ingest_mcp_findings`.

If auth fails, `401`, matching existing routes.

### Implementation shape

A shared private helper does the request-parsing, bulk update, and response-building:

```php
private function applyFindingStatusAction($targetStatus)
```

Each of the four public route methods is a thin wrapper:

```php
public function acknowledge_mcp_finding()
{
    $this->applyFindingStatusAction(Simplemdm_mcp_finding_model::STATUS_ACKNOWLEDGED);
}
```

The helper:
1. Validates sync token (`is_valid_sync_token()`).
2. Parses and normalizes `id`/`ids` from the JSON body.
3. Runs one `whereIn('id', $ids)` query to find which ids exist, then one `whereIn('id', $existingIds)->update([...])` to apply the status change (and `resolved_at` handling) in a single bulk statement — no per-row loop, since this is a straightforward bulk field-set, not the per-row upsert-with-branching-logic that `ingest_mcp_findings` needed.
4. Builds and returns the `{status, requested, updated, not_found}` response.

This keeps the four PRD-named routes as the public API surface (matching §15.3's route table) while sharing all real logic in one place, avoiding four near-identical copies of parsing/validation/response code.

### Testing approach

Consistent with the prior slice (this module has no PHPUnit coverage for controllers): live verification against the running `munkireport-local` Docker container via curl + direct `sqlite3` reads of `app/db/db.sqlite`, per action route, covering:
- Single `id` acknowledge/resolve/ignore/suppress, each verified via a `sqlite3` row check.
- Batch `ids` covering a mix of existing and non-existent ids, verifying `updated` count and `not_found` list.
- `resolve` sets `resolved_at`; a subsequent `acknowledge` on the same row clears it back to null.
- Missing/malformed body returns 400; missing/invalid token returns 401.

## Open questions / risks

None outstanding — all major decisions (auth, lookup key, audit fields, transition strictness) were resolved during brainstorming.

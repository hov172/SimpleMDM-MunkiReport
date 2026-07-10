# MCP Findings PHP Test Coverage (Phase A) — Design

**Status:** Approved
**Date:** 2026-07-10
**PRD reference:** none directly — this is test-debt work discovered while verifying PRD Phase 1-3 completion. All the PRD-listed MCP findings routes (lifecycle, admin actions, settings, category/fingerprint, analytics) are implemented and wired correctly, but the module has zero automated test coverage on the PHP side, unlike the SimpleMDM-MCP (TypeScript) side of the same PRD, which is fully tested.

## Context

The MCP findings logic lives almost entirely inside `Simplemdm_controller` — a single ~294KB file — as inline code in large methods (`ingest_mcp_findings`, `applyFindingStatusAction`, `get_mcp_findings`, `get_mcp_finding_stats`, `export_mcp_findings`, `get_mcp_scan_status`). The only pure, already-testable piece is `Simplemdm_mcp_finding_model::computeFingerprint()`.

Neither this module nor the host MunkiReport app has any real precedent for testing Eloquent-model or module-controller logic: the host's `tests/` directory has exactly one test (`ConfigTest.php`, config-loading only, no DB). This module has no `tests/` directory at all. There is also no PHP runtime and no installed `phpunit` binary in the environment this design was authored in — verification of anything written here has to happen via the existing `docker-compose.yml`'s `munkireport` service (which mounts `./local` and can run `composer install --dev` + `phpunit` for real), not a bare host shell.

## Scope

**In scope (Phase A):**

1. Extract pure decision/validation logic out of `Simplemdm_controller` into static methods on `Simplemdm_mcp_finding_model`, behavior-preserving (no change to what any route returns or does).
2. New test infrastructure under the module's own `tests/` directory, with a bootstrap that runs the module's real migrations against an in-memory SQLite Eloquent connection.
3. Unit tests for the extracted methods (no DB) and DB-backed tests for the upsert/dedup/reopen/auto-resolve behavior (real migrated schema, in-memory SQLite).

**Explicitly out of scope (Phase B, not designed here):**

- Any test that exercises the actual HTTP controller layer: route dispatch, sync-token auth (`is_valid_sync_token()`), JSON response shapes/status codes, `$sync_actions`/`$token_read_actions` registration.
- `mcp_findings_enabled()` / settings-gating (`get_config_value()`) — depends on framework config state, not pure.
- `export_mcp_findings`'s CSV streaming/headers.
- Any change to the four analytics/read routes' actual query-building, beyond the `parseMultiValueParam` extraction below.
- Any behavior change anywhere. This is a test-and-extract-for-testability slice, not a feature change.

## Design

### Test location and infrastructure

Tests live inside the module's own repo, not the host app's `tests/` — the module carries its own tests to any MunkiReport install it's dropped into.

```
local/modules/simplemdm/
  phpunit.xml                          # bootstrap -> tests/bootstrap.php
  tests/
    bootstrap.php
    Unit/
      McpFindingModelTest.php          # computeFingerprint, normalizeFinding,
                                        #   computeUpsertUpdate, parseFindingIds,
                                        #   buildStatusUpdate, parseMultiValueParam
      McpFindingUpsertDbTest.php       # DB-backed upsert/dedup/reopen/auto-resolve
```

`tests/bootstrap.php`:

1. `require`s the host app's `vendor/autoload.php` via `__DIR__/../../../../vendor/autoload.php` — safe because a MunkiReport module is always installed at `<host>/local/modules/<name>/`, which is the module system's own contract.
2. Boots an `Illuminate\Database\Capsule\Manager` with a `sqlite`/`:memory:` connection, `setAsGlobal()` + `bootEloquent()`.
3. `require`s and runs the `up()` method of each of the module's three `simplemdm_mcp_finding*` migration files, in order, against that connection — the test schema is the real, currently-shipping schema, not a hand-maintained copy that can drift from it.
4. `require`s `simplemdm_mcp_finding_model.php`.

**Running tests:** this sandbox has no PHP runtime, and the host app's `vendor/phpunit` isn't installed despite `phpunit.xml` existing at the host root. Verification happens via the existing `docker-compose.yml`'s `munkireport` service, which builds from the host `Dockerfile` and mounts `./local` (so it sees the module's new test files):

```
docker compose run --rm munkireport composer install
docker compose run --rm munkireport local/modules/simplemdm/vendor/bin/phpunit \
  -c local/modules/simplemdm/phpunit.xml
```

Exact invocation (including whether `phpunit` needs to be required at the module level via its own `composer.json` `require-dev`, or is reachable from the host's vendor after `composer install`) gets nailed down in the implementation plan against the real container. The point is that every test written here is actually executed, not just written and assumed correct.

### Extraction — exact method signatures

All added to `Simplemdm_mcp_finding_model`, next to the existing `computeFingerprint`. Every method is pure: no DB access, no framework calls, same output the controller computes inline today.

```php
// From ingest_mcp_findings's per-item loop body. Returns null for anything that
// today causes $skipped++, else the normalized fields ready for create()/fill().
public static function normalizeFinding(array $finding, $metadataMaxBytes)
{
    // trims/validates finding_type + message (required, else null)
    // normalizes severity to danger|warning|info, default info
    // truncates serial_number (64), finding_type (128), category (128, '' -> null)
    // truncates message to 1000
    // json-encodes/truncates `data` to $metadataMaxBytes
    // returns ['serial_number'=>, 'category'=>, 'finding_type'=>, 'message'=>, 'severity'=>, 'data'=>]
}

// From the existing-row branch of ingest_mcp_findings. $existing is the fetched
// Eloquent row itself (its ->status and ->occurrence_count are read directly).
// Returns ['update' => [...fields for fill()...], 'kind' => 'updated'|'reopened'|'unchanged']
public static function computeUpsertUpdate($existing, array $normalized, $scanId, $now)

// From applyFindingStatusAction. Mirrors the existing id/ids parsing exactly.
public static function parseFindingIds(array $data)   // returns int[], deduped, positive only

public static function buildStatusUpdate($targetStatus)  // returns ['status'=>, 'resolved_at'=>]

// From get_mcp_findings / get_mcp_finding_stats / export_mcp_findings -- identical
// logic duplicated verbatim 3x today (explode on comma, trim, filter empties).
public static function parseMultiValueParam($raw)   // returns string[] (possibly empty)
```

Controller call sites become thin wrappers: e.g. `ingest_mcp_findings`'s loop calls `normalizeFinding()`, skips on `null`, then calls `computeUpsertUpdate()` to get the field array and the inserted/updated/reopened counters, then still performs the actual `::create()` / `->fill()->save()` itself — the DB write stays in the controller, only the decision logic moves.

`computeUpsertUpdate` is unit-testable without a DB despite taking an Eloquent row: a test can `new Simplemdm_mcp_finding_model()` and set `->status`/`->occurrence_count` in memory without saving, since the method only reads those two properties.

### DB-backed test scenarios

In `McpFindingUpsertDbTest.php`, against the real migrated in-memory SQLite schema:

1. First push for a `(source, serial, finding_type, category)` combo -> row created, `status=open`, `occurrence_count=1`.
2. Second push with an identical combo -> same row updated (no duplicate row), `occurrence_count` incremented.
3. A `resolved` row re-pushed with the same fingerprint -> reopens (`status=open`, `resolved_at=null`), counts as `reopened` not `updated`.
4. A `suppressed`/`ignored` row re-pushed with the same fingerprint -> fields refresh (message/severity/etc.) but status is left alone, doesn't count as `updated`.
5. Two findings differing only by `category` -> two distinct rows (confirms fingerprint scoping still works post-refactor).
6. A `replace=true` push that omits a previously-active row for that source -> that row transitions to `resolved` (auto-resolve), confirmed via a direct query after the push.
7. A category-less finding (`category` omitted/empty) -> fingerprints identically to the pre-category-field behavior (locks in the backward-compat guarantee the `2026_07_09_100000_simplemdm_mcp_finding_category.php` migration's docstring already claims).

## Non-goals

No HTTP/controller-route testing, no `mcp_findings_enabled()`/settings tests, no CSV export testing, no changes to the four analytics/read routes' actual query logic beyond the `parseMultiValueParam` extraction, no behavior changes anywhere.

## Open questions / risks

- Whether `phpunit` should be a `require-dev` of the module's own `composer.json` (self-contained, but the module currently only declares `"php": ">=7.0"` and nothing else) or relied on transitively via the host app's `vendor/` after `composer install --dev` there. Decide in the implementation plan once the container's actual `vendor/` state after `composer install` is confirmed.
- The exact relative-path depth in `tests/bootstrap.php` (`__DIR__/../../../../vendor/autoload.php`) assumes the module is checked out at exactly `<host>/local/modules/simplemdm/`. This holds for every known install of this module (including this workspace) but should be verified against the actual container filesystem during implementation, not assumed from local dev host paths.

## Next steps

`writing-plans` skill -> implementation plan with exact extraction diffs against the controller's current line numbers, the bootstrap file contents, and the exact Docker verification commands confirmed against the real container.

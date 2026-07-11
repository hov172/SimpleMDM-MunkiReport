# MCP Findings Retention Setting Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the PRD §11.5 `mcp_findings_retention_days` admin setting (default 0 = retain indefinitely) plus a lazy purge that deletes non-active MCP findings not seen within the retention window.

**Architecture:** One new static query helper on the findings model (`purgeExpired`), called best-effort at the end of `ingest_mcp_findings`; setting registered through the module's existing config plumbing (get_config defaults → save_config whitelist/clamp → admin panel field → populate JS). No new routes, no schema changes.

**Tech Stack:** PHP (MunkiReport module), Eloquent/Capsule, PHPUnit (in-memory SQLite harness), Bootstrap 3 admin view + jQuery.

**Design spec:** `docs/superpowers/specs/2026-07-12-mcp-findings-retention-design.md` — read it before starting.

## Global Constraints

- Config values are stored as **strings** (`'0'`, `'30'`), like every other `simplemdm_config` value.
- Setting key is exactly `mcp_findings_retention_days` (PRD-literal). Default `'0'` = purge disabled.
- Clamp: negative input persists as `'0'` (`max(0, (int) $value)`).
- Purge NEVER deletes rows whose `status` is in `Simplemdm_mcp_finding_model::ACTIVE_STATUSES` (`open`, `acknowledged`, `in_progress`), regardless of age.
- Purge failure must NEVER fail an ingest (try/catch, no rethrow) — same convention as the summary-event call.
- Tests run inside the Docker container (no host PHP): `docker exec -w /var/munkireport/local/modules/simplemdm munkireport-local vendor/bin/phpunit`
- All timestamps are ISO-8601 UTC strings produced by `gmdate('c')`; comparisons are string comparisons, which are correct because every stored value shares that format.
- Commit messages: conventional-commit style with scope `simplemdm` where the module code changes (see each task's commit step); end with the Claude co-author trailer.

---

### Task 1: `purgeExpired` model helper (TDD)

**Files:**
- Test (create): `tests/Unit/McpFindingPurgeDbTest.php`
- Modify: `simplemdm_mcp_finding_model.php` (add one public static method after `computeUpsertUpdate`, which ends at line ~139)

**Interfaces:**
- Consumes: `Simplemdm_mcp_finding_model::ACTIVE_STATUSES` (existing, line ~36), Eloquent query builder (the model is already Capsule-backed in tests via `tests/bootstrap.php`).
- Produces: `Simplemdm_mcp_finding_model::purgeExpired($retentionDays, $now): int` — `$retentionDays` int-ish (int or numeric string), `$now` an ISO-8601 UTC string (`gmdate('c')`); returns the number of rows deleted. Task 2's controller call site depends on exactly this signature.

- [ ] **Step 1: Write the failing tests**

Create `tests/Unit/McpFindingPurgeDbTest.php` with this exact content:

```php
<?php

use PHPUnit\Framework\TestCase;

final class McpFindingPurgeDbTest extends TestCase
{
    protected function setUp(): void
    {
        // Each test gets a clean table -- bootstrap.php's connection persists
        // across the whole process, so truncate rather than re-migrate.
        Simplemdm_mcp_finding_model::query()->delete();
    }

    /**
     * Seeds one finding row directly. $daysAgo controls last_seen_at
     * (null = leave last_seen_at NULL, for pre-lifecycle-row coverage).
     */
    private function seedRow($status, $daysAgo, $reportedDaysAgo = null, $suffix = '')
    {
        $lastSeen = $daysAgo === null ? null : gmdate('c', time() - $daysAgo * 86400);
        $reported = gmdate('c', time() - ($reportedDaysAgo ?? ($daysAgo ?? 0)) * 86400);

        return Simplemdm_mcp_finding_model::create([
            'serial_number'    => 'SER' . $status . $suffix,
            'category'         => 'Test',
            'source'           => 'mcp_test',
            'finding_type'     => 'retention_probe_' . $status . $suffix,
            'fingerprint'      => sha1($status . ($daysAgo ?? 'null') . $suffix),
            'severity'         => 'warning',
            'status'           => $status,
            'occurrence_count' => 1,
            'scan_id'          => 'scan_retention_test',
            'message'          => 'retention probe',
            'data'             => null,
            'reported_at'      => $reported,
            'first_seen_at'    => $reported,
            'last_seen_at'     => $lastSeen,
            'resolved_at'      => $status === Simplemdm_mcp_finding_model::STATUS_RESOLVED ? $reported : null,
        ]);
    }

    public function testPurgesResolvedRowOlderThanWindow(): void
    {
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_RESOLVED, 31);

        $deleted = Simplemdm_mcp_finding_model::purgeExpired(30, gmdate('c'));

        $this->assertSame(1, $deleted);
        $this->assertSame(0, Simplemdm_mcp_finding_model::count());
    }

    public function testKeepsResolvedRowInsideWindow(): void
    {
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_RESOLVED, 29);

        $deleted = Simplemdm_mcp_finding_model::purgeExpired(30, gmdate('c'));

        $this->assertSame(0, $deleted);
        $this->assertSame(1, Simplemdm_mcp_finding_model::count());
    }

    public function testNeverPurgesActiveRowsRegardlessOfAge(): void
    {
        foreach (Simplemdm_mcp_finding_model::ACTIVE_STATUSES as $i => $status) {
            $this->seedRow($status, 400, null, (string) $i);
        }

        $deleted = Simplemdm_mcp_finding_model::purgeExpired(30, gmdate('c'));

        $this->assertSame(0, $deleted);
        $this->assertSame(
            count(Simplemdm_mcp_finding_model::ACTIVE_STATUSES),
            Simplemdm_mcp_finding_model::count()
        );
    }

    public function testPurgesStaleSuppressedAndIgnoredRows(): void
    {
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_SUPPRESSED, 31);
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_IGNORED, 31);
        // A suppressed finding still being observed keeps a fresh last_seen_at
        // (computeUpsertUpdate bumps it on every push) and must survive.
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_SUPPRESSED, 0, null, 'fresh');

        $deleted = Simplemdm_mcp_finding_model::purgeExpired(30, gmdate('c'));

        $this->assertSame(2, $deleted);
        $this->assertSame(1, Simplemdm_mcp_finding_model::count());
        $this->assertSame(
            Simplemdm_mcp_finding_model::STATUS_SUPPRESSED,
            Simplemdm_mcp_finding_model::first()->status
        );
    }

    public function testZeroAndNegativeRetentionAreNoOps(): void
    {
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_RESOLVED, 400);

        $this->assertSame(0, Simplemdm_mcp_finding_model::purgeExpired(0, gmdate('c')));
        $this->assertSame(0, Simplemdm_mcp_finding_model::purgeExpired(-5, gmdate('c')));
        $this->assertSame(1, Simplemdm_mcp_finding_model::count());
    }

    public function testNullLastSeenFallsBackToReportedAt(): void
    {
        // Pre-lifecycle rows can have NULL last_seen_at; reported_at decides.
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_RESOLVED, null, 31, 'old');
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_RESOLVED, null, 5, 'new');

        $deleted = Simplemdm_mcp_finding_model::purgeExpired(30, gmdate('c'));

        $this->assertSame(1, $deleted);
        $remaining = Simplemdm_mcp_finding_model::first();
        $this->assertSame('retention_probe_resolvednew', $remaining->finding_type);
    }

    public function testReturnsExactDeletedCount(): void
    {
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_RESOLVED, 31, null, 'a');
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_RESOLVED, 32, null, 'b');
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_IGNORED, 33, null, 'c');
        $this->seedRow(Simplemdm_mcp_finding_model::STATUS_RESOLVED, 1, null, 'keep');

        $this->assertSame(3, Simplemdm_mcp_finding_model::purgeExpired(30, gmdate('c')));
        $this->assertSame(1, Simplemdm_mcp_finding_model::count());
    }
}
```

- [ ] **Step 2: Run the new test class to verify it fails**

Run:
```bash
docker exec -w /var/munkireport/local/modules/simplemdm munkireport-local vendor/bin/phpunit --filter McpFindingPurgeDbTest
```
Expected: every test ERRORS with `Call to undefined method Simplemdm_mcp_finding_model::purgeExpired()`.

- [ ] **Step 3: Implement `purgeExpired`**

In `simplemdm_mcp_finding_model.php`, directly after the closing brace of `computeUpsertUpdate` (line ~139), add:

```php
    /**
     * Deletes non-active findings not seen within the retention window.
     * Rows in ACTIVE_STATUSES are never deleted regardless of age.
     * Staleness is judged by last_seen_at, falling back to reported_at for
     * pre-lifecycle rows where last_seen_at is NULL.
     * Returns the number of rows deleted. $retentionDays <= 0 is a no-op.
     */
    public static function purgeExpired($retentionDays, $now)
    {
        $retentionDays = (int) $retentionDays;
        if ($retentionDays <= 0) {
            return 0;
        }
        $cutoff = gmdate('c', strtotime($now) - $retentionDays * 86400);

        return self::whereNotIn('status', self::ACTIVE_STATUSES)
            ->where(function ($query) use ($cutoff) {
                $query->where('last_seen_at', '<', $cutoff)
                    ->orWhere(function ($fallback) use ($cutoff) {
                        $fallback->whereNull('last_seen_at')
                            ->where('reported_at', '<', $cutoff);
                    });
            })
            ->delete();
    }
```

- [ ] **Step 4: Run the new test class to verify it passes**

Run:
```bash
docker exec -w /var/munkireport/local/modules/simplemdm munkireport-local vendor/bin/phpunit --filter McpFindingPurgeDbTest
```
Expected: `OK (7 tests, ...)`.

- [ ] **Step 5: Run the whole suite to check for regressions**

Run:
```bash
docker exec -w /var/munkireport/local/modules/simplemdm munkireport-local vendor/bin/phpunit
```
Expected: all green (57 tests / ~110 assertions — 50/92 at base plus this task's 7).

- [ ] **Step 6: Commit**

```bash
git add tests/Unit/McpFindingPurgeDbTest.php simplemdm_mcp_finding_model.php
git commit -m "feat(simplemdm): purgeExpired model helper for findings retention

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 2: Controller wiring — setting registration + lazy purge in ingest

**Files:**
- Modify: `simplemdm_controller.php` — four spots:
  1. `get_config()` defaults block, after the `mcp_findings_event_warning_threshold` default (line ~3723-3725)
  2. `save_config()` `$config_keys` whitelist tail (line ~3828-3833)
  3. `save_config()` clamp chain, after the `mcp_findings_event_warning_threshold` clamp (line ~3919-3921)
  4. `ingest_mcp_findings()`, between the auto-resolve block and the summary-event try/catch (line ~6663-6665), plus the response array (~6671-6682)

**Interfaces:**
- Consumes: `Simplemdm_mcp_finding_model::purgeExpired($retentionDays, $now): int` (Task 1); existing `$this->get_config_value($name, $default)`; ingest's existing `$now` variable (`gmdate('c')`, set at line ~6576).
- Produces: config key `mcp_findings_retention_days` readable via `get_config`, writable via `save_config` (clamped ≥ 0); `purged` integer field in the `ingest_mcp_findings` JSON response. Task 3's view and Task 4's docs depend on this key name and response field.

- [ ] **Step 1: Add the get_config default**

In `simplemdm_controller.php`, after the `mcp_findings_event_warning_threshold` default block (`:3723-3725`), add:

```php
        if (! isset($config['mcp_findings_retention_days'])) {
            $config['mcp_findings_retention_days'] = '0';
        }
```

- [ ] **Step 2: Whitelist the key in save_config**

In the `$config_keys` array (`:3828-3833`), after `'mcp_findings_event_warning_threshold',`, add:

```php
            'mcp_findings_retention_days',
```

- [ ] **Step 3: Add the clamp**

In the save_config clamp chain, after the `mcp_findings_event_warning_threshold` branch (`:3919-3921`), add:

```php
                } elseif ($key === 'mcp_findings_retention_days') {
                    $v = max(0, (int) $value);
                    $value = (string) $v;
```

- [ ] **Step 4: Call the purge from ingest and report it**

In `ingest_mcp_findings()`, the current code between the auto-resolve sweep and the response reads (`:6663-6683`):

```php
        }

        try {
            $this->sync_mcp_findings_summary_event();
        } catch (\Throwable $e) {
            // Silently fail - summary event is best-effort
        }

        jsonView([
            'status'   => 'success',
            ...
            'resolved' => $resolved,
            'skipped'  => $skipped,
            'replace'  => $replace,
        ]);
```

Insert the purge between the auto-resolve block's closing brace and the summary-event try/catch, and add `purged` to the response after `resolved`:

```php
        $purged = 0;
        try {
            $retentionDays = (int) $this->get_config_value('mcp_findings_retention_days', '0');
            $purged = Simplemdm_mcp_finding_model::purgeExpired($retentionDays, $now);
        } catch (\Throwable $e) {
            // Silently fail - retention purge is best-effort and must never
            // fail an ingest.
        }
```

and in the `jsonView` array:

```php
            'resolved' => $resolved,
            'purged'   => $purged,
            'skipped'  => $skipped,
```

Ordering rationale (from the design spec): purge runs after the upsert loop and auto-resolve sweep, so rows touched by this request always carry `last_seen_at = $now` and cannot be purged by the same request; it runs before the summary event so the event reflects post-purge state.

- [ ] **Step 5: PHP-lint the controller**

Run:
```bash
docker exec -w /var/munkireport/local/modules/simplemdm munkireport-local php -l simplemdm_controller.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 6: Live verification against the container**

The container is `AUTH_METHODS=NOAUTH` — a curl cookie jar is an instant global-admin session. Base URL http://localhost:8888.

```bash
# admin session cookie
curl -s -c /tmp/mr.jar http://localhost:8888/index.php?/auth/login > /dev/null

# 1. default: get_config returns '0' for the new key
curl -s -b /tmp/mr.jar "http://localhost:8888/index.php?/module/simplemdm/get_config" | python3 -c "import sys,json; print(json.load(sys.stdin)['mcp_findings_retention_days'])"
# Expected: 0

# 2. clamp: negative persists as '0'
curl -s -b /tmp/mr.jar -X POST "http://localhost:8888/index.php?/module/simplemdm/save_config" --data "mcp_findings_retention_days=-5" > /dev/null
curl -s -b /tmp/mr.jar "http://localhost:8888/index.php?/module/simplemdm/get_config" | python3 -c "import sys,json; print(json.load(sys.stdin)['mcp_findings_retention_days'])"
# Expected: 0

# 3. save 30, read back 30
curl -s -b /tmp/mr.jar -X POST "http://localhost:8888/index.php?/module/simplemdm/save_config" --data "mcp_findings_retention_days=30" > /dev/null
curl -s -b /tmp/mr.jar "http://localhost:8888/index.php?/module/simplemdm/get_config" | python3 -c "import sys,json; print(json.load(sys.stdin)['mcp_findings_retention_days'])"
# Expected: 30
```

Ingest round-trip. Auth is the sync token in the `X-SIMPLEMDM-API-KEY` header (`is_valid_sync_token()`, controller :2304); the stored token is config key `api_key` (`get_stored_api_key()`, controller :88). Endpoint form per `docs/API_REFERENCE.md:811`: `POST /index.php?/module/simplemdm/index?op=ingest_mcp_findings`.

```bash
TOKEN=$(docker exec munkireport-local php -r '
$p = new PDO("sqlite:/var/munkireport/app/db/db.sqlite");
echo $p->query("SELECT value FROM simplemdm_config WHERE name=\"api_key\"")->fetchColumn();')

# push one finding; response must contain "purged":0 (nothing old yet)
curl -s -X POST "http://localhost:8888/index.php?/module/simplemdm/index?op=ingest_mcp_findings" \
  -H "X-SIMPLEMDM-API-KEY: $TOKEN" -H "Content-Type: application/json" \
  -d '{"source":"mcp_retention_check","findings":[{"serial_number":"RETPROBE1","finding_type":"probe","severity":"warning","message":"retention probe"}]}'
# Expected JSON includes: "status":"success", ... "purged":0

# backdate the row and mark it resolved (no sqlite3 CLI in the container; use PHP PDO)
docker exec munkireport-local php -r '
$p = new PDO("sqlite:/var/munkireport/app/db/db.sqlite");
$old = gmdate("c", time() - 40*86400);
$n = $p->exec("UPDATE simplemdm_mcp_finding SET status=\"resolved\", resolved_at=\"$old\", last_seen_at=\"$old\" WHERE source=\"mcp_retention_check\"");
echo "backdated: $n\n";'
# Expected: backdated: 1

# push again (different finding): the stale resolved row must purge
curl -s -X POST "http://localhost:8888/index.php?/module/simplemdm/index?op=ingest_mcp_findings" \
  -H "X-SIMPLEMDM-API-KEY: $TOKEN" -H "Content-Type: application/json" \
  -d '{"source":"mcp_retention_check","findings":[{"serial_number":"RETPROBE2","finding_type":"probe2","severity":"warning","message":"retention probe 2"}]}'
# Expected JSON includes: "purged":1

# cleanup: retention back to 0 and remove probe rows
curl -s -b /tmp/mr.jar -X POST "http://localhost:8888/index.php?/module/simplemdm/save_config" --data "mcp_findings_retention_days=0" > /dev/null
docker exec munkireport-local php -r '
$p = new PDO("sqlite:/var/munkireport/app/db/db.sqlite");
$p->exec("DELETE FROM simplemdm_mcp_finding WHERE source=\"mcp_retention_check\"");'
```

Notes for the implementer:
- If the TOKEN one-liner returns empty, no `api_key` config row exists yet — set one via the admin UI Sync panel (or `save_config`) first.
- If the sqlite file is not at `/var/munkireport/app/db/db.sqlite`, check `docker exec munkireport-local sh -c 'ls /var/munkireport/app/db'` (the container also has a legacy `munkireport.sq3`; `db.sqlite` is the live one — confirm by checking which contains the `simplemdm_mcp_finding` table).
- The cookie-jar URL form (`index.php?/...`) is the one the module's own TESTING.md uses.

- [ ] **Step 7: Run the full PHPUnit suite (regression)**

```bash
docker exec -w /var/munkireport/local/modules/simplemdm munkireport-local vendor/bin/phpunit
```
Expected: all green, same count as Task 1 Step 5.

- [ ] **Step 8: Commit**

```bash
git add simplemdm_controller.php
git commit -m "feat(simplemdm): mcp_findings_retention_days setting + lazy purge on ingest

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 3: Admin UI field

**Files:**
- Modify: `views/simplemdm_admin.php` — two spots:
  1. MCP Findings Settings panel form, after the `mcp_findings_event_warning_threshold` form-group (line ~891-895)
  2. Config-populate JS, after the `mcp_findings_event_warning_threshold` line (line ~1868)

**Interfaces:**
- Consumes: config key `mcp_findings_retention_days` from `get_config` / saved via the panel's existing `$.post` to `save_config` (Task 2). `pickValue` helper already exists in the view's JS.
- Produces: nothing downstream; Task 4 documents this field.

- [ ] **Step 1: Add the form field**

In `views/simplemdm_admin.php`, after the `mcp_findings_event_warning_threshold` form-group (`:891-895`, ends with `</div>` before the submit button), insert:

```html
                        <div class="form-group">
                            <label for="mcp_findings_retention_days">Retention Days</label>
                            <input type="number" min="0" step="1" class="form-control" id="mcp_findings_retention_days" name="mcp_findings_retention_days" placeholder="0">
                            <p class="help-block">Days to keep resolved/ignored/suppressed findings after they were last seen; older ones are deleted during ingest. Open, acknowledged, and in-progress findings are never deleted. 0 keeps everything forever.</p>
                        </div>
```

- [ ] **Step 2: Add the populate line**

In the config-populate JS, after `:1868` (`$('#mcp_findings_event_warning_threshold').val(pickValue(data.mcp_findings_event_warning_threshold, '1'));`), add:

```javascript
        $('#mcp_findings_retention_days').val(pickValue(data.mcp_findings_retention_days, '0'));
```

- [ ] **Step 3: PHP-lint the view**

```bash
docker exec -w /var/munkireport/local/modules/simplemdm munkireport-local php -l views/simplemdm_admin.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 4: Live verification (admin page round-trip)**

Established pattern for view-only changes: verify against the live container (no JS test framework exists in this module). With the `/browse` gstack skill or curl:

- Load `http://localhost:8888/index.php?/module/simplemdm/admin`, expand the "MCP Findings Settings" panel.
- Confirm the "Retention Days" field renders after "Summary Event Warning Threshold" and is populated with `0` (or `30` if Task 2's verification left it — then reset to 0 and re-save).
- Type `45`, click "Save MCP Findings Settings", reload the page, confirm the field shows `45`.
- Type `-3`, save, reload: field shows `0` (server clamp round-tripped).
- Reset to `0` and save when done.

- [ ] **Step 5: Commit**

```bash
git add views/simplemdm_admin.php
git commit -m "feat(simplemdm): Retention Days field in MCP Findings admin panel

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 4: Documentation

**Files:**
- Modify: `docs/API_REFERENCE.md` (admin settings table ~:1014-1018; `ingest_mcp_findings` response example)
- Modify: `README.md` (~:109 — the "five admin settings" sentence)
- Modify: `docs/DEVELOPER_GUIDE.md` (~:190-192 — MCP Findings Settings panel key list)
- Modify: `docs/TESTING.md` (~:629-634 — MCP findings settings manual checklist)
- Modify: `docs/SECURITY.md` (data-handling note)
- Modify: `CHANGELOG.md` (`[Unreleased]` section)

**Interfaces:**
- Consumes: key name `mcp_findings_retention_days`, default `'0'`, clamp-to-0 behavior, `purged` response field (Task 2); "Retention Days" field label (Task 3); purge semantics (design spec).
- Produces: nothing downstream.

- [ ] **Step 1: API_REFERENCE.md**

Add to the admin settings table (after the `mcp_findings_event_warning_threshold` row):

```markdown
| `mcp_findings_retention_days` | `0` | Days to keep resolved/ignored/suppressed findings after they were last seen; ingest deletes older ones. Active (open/acknowledged/in_progress) findings are never deleted. `0` retains indefinitely. Negative values are clamped to `0`. |
```

In the `ingest_mcp_findings` response documentation, add the `purged` field next to `resolved`:

```markdown
- `purged` — number of non-active findings deleted by the retention purge during this ingest (`0` when `mcp_findings_retention_days` is `0`).
```

Match the surrounding formatting (the section may use a JSON example instead of a bullet list — if so, add `"purged": 0,` after `"resolved": ...` in the example and a sentence below it).

- [ ] **Step 2: README.md**

Update the admin-settings sentence at ~:109: "five admin settings" → "six admin settings", and append a clause: "…and `mcp_findings_retention_days` (how long resolved/ignored/suppressed findings are kept after they were last seen; 0 = forever)." Match the sentence's existing structure — read it first and keep its list style.

- [ ] **Step 3: DEVELOPER_GUIDE.md**

Add to the MCP Findings Settings panel bullet list (~:190-192):

```markdown
- `mcp_findings_retention_days` — lazy purge window for non-active findings, enforced at the end of `ingest_mcp_findings` via `Simplemdm_mcp_finding_model::purgeExpired()`; 0 disables.
```

- [ ] **Step 4: TESTING.md**

In the MCP findings settings manual checklist (~:629-634), after the metadata_max_bytes floor step, add:

```markdown
- Retention Days: enter `-5`, save, reload — field shows `0` (server clamps). Set `1`, push a finding, backdate it to `status='resolved'` with `last_seen_at` 2+ days old (see the PDO one-liner in the retention plan), push again from the same source — the ingest response reports `"purged": 1` and the row is gone. Reset to `0` afterwards.
```

- [ ] **Step 5: SECURITY.md**

Add one line in the data-handling/settings discussion (near the existing `mcp_findings_event_enabled` reference at ~:26):

```markdown
`mcp_findings_retention_days` is the data-minimization control for stored finding history: non-active findings are hard-deleted once unseen for the configured window (0 = keep forever).
```

- [ ] **Step 6: CHANGELOG.md**

Under `## [Unreleased]`, add:

```markdown
### Added
- `mcp_findings_retention_days` admin setting (default 0 = keep forever): when set, `ingest_mcp_findings` lazily hard-deletes resolved/ignored/suppressed findings not seen within the window and reports the count as `purged` in its response. Open, acknowledged, and in-progress findings are never deleted; suppressed findings that still occur keep a fresh last-seen timestamp and are never purged, so retention cannot undo an active suppression.
```

If `[Unreleased]` already has an `### Added` heading, append the bullet to it instead of adding a second heading.

- [ ] **Step 7: Verify docs accuracy**

Re-read each edited section and confirm: key name spelled `mcp_findings_retention_days` everywhere; default stated as `0`; the "never deletes active findings" caveat present in API_REFERENCE and README-level text; no doc claims a scheduled/cron purge (it is ingest-triggered only).

- [ ] **Step 8: Commit**

```bash
git add docs/API_REFERENCE.md README.md docs/DEVELOPER_GUIDE.md docs/TESTING.md docs/SECURITY.md CHANGELOG.md
git commit -m "docs: document mcp_findings_retention_days and ingest purged count

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

## Final verification (after all tasks)

1. Full suite: `docker exec -w /var/munkireport/local/modules/simplemdm munkireport-local vendor/bin/phpunit` — green.
2. Whole-branch review (superpowers:requesting-code-review) against the pre-Task-1 base — per project convention, the final review must trace claims against real source, not the plan.
3. Container state restored: retention setting back to `0`, probe rows deleted (Task 2 Step 6 cleanup + Task 3 Step 4 reset).

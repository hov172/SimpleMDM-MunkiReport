# Category Field + Fingerprint Rescoping Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a `category` field to MCP findings, rescope the dedup fingerprint to include it (`hash(source|serial|finding_type|category)`, matching PRD §16.1's original intent), and expose `category` as a `get_mcp_findings` filter — a prerequisite (Slice A) for the later `get_mcp_finding_stats`/`export_mcp_findings`/`get_mcp_scan_status` routes (Slice B, not part of this plan).

**Architecture:** Three-file change inside the existing `simplemdm` MunkiReport module: (1) a new additive migration adds a nullable `category` column and backfills existing rows' `fingerprint` using `category=''`, (2) `Simplemdm_mcp_finding_model::computeFingerprint()` gains a 4th parameter, (3) `ingest_mcp_findings()` accepts and stores an optional per-finding `category`, and `get_mcp_findings()` gains a `category` query filter. No new tables, no UI changes.

**Tech Stack:** PHP 8.1, Illuminate/Database (Eloquent) with SQLite (local dev, `app/db/db.sqlite`, bind-mounted into the `munkireport-local` Docker container) and MySQL (production).

**Verification approach — deviates from strict PHPUnit TDD:** Matches every prior slice (no PHPUnit coverage for this module's controllers): every task's "test" step is a live HTTP request (curl) against the running `munkireport-local` Docker container plus a direct `sqlite3` read of `app/db/db.sqlite`.

## Global Constraints

- Design spec: `docs/superpowers/specs/2026-07-09-category-fingerprint-design.md` — this plan implements that spec; if any step here appears to contradict it, the spec governs and should be flagged.
- Do not edit `migrations/2026_07_07_000000_simplemdm_mcp_finding.php` or `migrations/2026_07_09_000000_simplemdm_mcp_finding_lifecycle.php` — shipped migrations are immutable; all schema changes must be a new additive migration file.
- **Compatibility is the load-bearing property of this slice:** a finding pushed WITHOUT `category` must dedupe identically before and after this migration (both hash against `category=''`). This must be explicitly verified, not assumed.
- `category` is stored as-given (case preserved, trimmed, capped at 128 chars — same treatment as `finding_type`), but lowercased only inside the fingerprint hash (same pattern already used for every other fingerprint field).
- Absent/empty `category` is stored as `null` (matching `serial_number`'s existing nullable pattern), hashed as `''`.
- Do not implement `get_mcp_finding_stats`, `export_mcp_findings`, `get_mcp_scan_status`, `risk_score`, `device_id`, `udid`, `device_name`, or any UI/widget changes — all explicitly out of scope per the design spec (Slice B or later).

---

### Task 1: Migration — add `category` column and rescope fingerprint backfill

**Files:**
- Create: `migrations/2026_07_09_100000_simplemdm_mcp_finding_category.php`
- Test: manual (`php please migrate` against the running container + `sqlite3` schema/row check)

**Interfaces:**
- Produces: a new nullable `category` column on `simplemdm_mcp_finding`, and recomputed `fingerprint` values for all existing rows using the 4-field formula with `category=''`. Task 2 depends on the column existing exactly as spelled here; Task 3 depends on the backfill formula matching Task 2's `computeFingerprint()` byte-for-byte.

- [ ] **Step 1: Write the migration file**

```php
<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmMcpFindingCategory extends Migration
{
    public function up()
    {
        $capsule = new Capsule();
        $schema = $capsule::schema();

        $schema->table('simplemdm_mcp_finding', function (Blueprint $table) {
            $table->string('category')->nullable()->after('finding_type');
        });

        // Recompute fingerprints so the new category dimension is reflected.
        // Every existing row has no category, so this hashes against ''
        // for all of them -- byte-for-byte identical to what a future
        // ingest push without a category will also hash against, per
        // Simplemdm_mcp_finding_model::computeFingerprint()'s 4th
        // parameter default. This is what keeps existing dedup behavior
        // unchanged for publishers that don't send category.
        $rows = Capsule::table('simplemdm_mcp_finding')
            ->select('id', 'source', 'serial_number', 'finding_type')
            ->get();
        foreach ($rows as $row) {
            $fingerprint = hash(
                'sha256',
                strtolower((string) $row->source) . '|' . strtolower((string) $row->serial_number) . '|' . strtolower((string) $row->finding_type) . '|'
            );
            Capsule::table('simplemdm_mcp_finding')->where('id', $row->id)->update([
                'fingerprint' => $fingerprint,
            ]);
        }
    }

    public function down()
    {
        $capsule = new Capsule();
        $schema = $capsule::schema();
        $schema->table('simplemdm_mcp_finding', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
}
```

- [ ] **Step 2: Run the migration against the running dev container**

```bash
docker compose -f <repo-root>/docker-compose.yml exec munkireport php please migrate
```

Expected: output lists `SimplemdmMcpFindingCategory` as migrated, no errors.

- [ ] **Step 3: Verify schema and recomputed fingerprints directly against SQLite**

```bash
sqlite3 <repo-root>/app/db/db.sqlite ".schema simplemdm_mcp_finding"
```

Expected: schema output includes the new `category` column (nullable string).

```bash
sqlite3 <repo-root>/app/db/db.sqlite \
  "SELECT id, source, serial_number, finding_type, category, fingerprint FROM simplemdm_mcp_finding LIMIT 5;"
```

Expected: `category` is `NULL`/empty for every existing row; `fingerprint` is a 64-char hex string (recomputed — will differ from pre-migration values, this is expected).

- [ ] **Step 4: Commit**

```bash
cd <repo-root>
git add local/modules/simplemdm/migrations/2026_07_09_100000_simplemdm_mcp_finding_category.php
git commit -m "feat(simplemdm): add category column, rescope fingerprint backfill"
```

---

### Task 2: Model — extend `computeFingerprint()` with category

**Files:**
- Modify: `simplemdm_mcp_finding_model.php`
- Test: manual (PHP CLI smoke check via `php -r` inside the container)

**Interfaces:**
- Consumes: nothing new.
- Produces: `Simplemdm_mcp_finding_model::computeFingerprint($source, $serialNumber, $findingType, $category = '')` — the new 4th parameter, must match Task 1's backfill formula exactly when `$category` is `''`. `category` added to `$fillable`. Task 3 calls this with an explicit 4th argument.

- [ ] **Step 1: Update the model file**

Replace the `computeFingerprint` method and the `$fillable` array:

```php
    protected $fillable = [
        'serial_number',
        'source',
        'finding_type',
        'category',
        'fingerprint',
        'severity',
        'status',
        'occurrence_count',
        'scan_id',
        'message',
        'data',
        'reported_at',
        'first_seen_at',
        'last_seen_at',
        'resolved_at',
    ];
```

(This replaces the existing `$fillable` array — only the new `'category',` line is added, positioned right after `'finding_type',` to mirror the migration's column order; every other entry stays as-is.)

```php
    /**
     * Deterministic dedup key: same source + serial_number + finding_type +
     * category always maps to the same fingerprint, so repeated ingest
     * pushes upsert the same row instead of creating duplicates. Must stay
     * byte-for-byte identical to the backfill formula in migration
     * 2026_07_09_100000_simplemdm_mcp_finding_category.php. A missing/empty
     * category hashes as '', matching the backfill of every pre-existing
     * row -- this is what keeps dedup behavior unchanged for publishers
     * that don't send category.
     */
    public static function computeFingerprint($source, $serialNumber, $findingType, $category = '')
    {
        return hash(
            'sha256',
            strtolower((string) $source) . '|' . strtolower((string) $serialNumber) . '|' . strtolower((string) $findingType) . '|' . strtolower((string) $category)
        );
    }
```

(This replaces the existing `computeFingerprint` method in full, including its docblock.)

- [ ] **Step 2: Smoke-check the fingerprint helper matches the Task 1 backfill formula when category is empty**

```bash
docker compose -f <repo-root>/docker-compose.yml exec munkireport php -r '
require "/var/munkireport/local/modules/simplemdm/simplemdm_mcp_finding_model.php";
echo Simplemdm_mcp_finding_model::computeFingerprint("stale_devices", "C02EXAMPLE", "stale_device"), PHP_EOL;
echo Simplemdm_mcp_finding_model::computeFingerprint("stale_devices", "C02EXAMPLE", "stale_device", ""), PHP_EOL;
echo Simplemdm_mcp_finding_model::computeFingerprint("stale_devices", "C02EXAMPLE", "stale_device", "FileVault"), PHP_EOL;
'
```

Expected: the first two lines print the IDENTICAL 64-char hex string (default `$category=''` behaves the same as explicitly passing `""`), and the third line prints a DIFFERENT hash (a real category changes the fingerprint).

- [ ] **Step 3: Commit**

```bash
cd <repo-root>
git add local/modules/simplemdm/simplemdm_mcp_finding_model.php
git commit -m "feat(simplemdm): extend computeFingerprint with category dimension"
```

---

### Task 3: Controller — ingest category, filter by category

**Files:**
- Modify: `simplemdm_controller.php:6444-6619` (`ingest_mcp_findings()`) and `simplemdm_controller.php:6627-6712` (`get_mcp_findings()`) — confirm these line ranges before editing; they may have shifted slightly since plan-writing, locate by method name if so.
- Test: manual (`curl` against the live container + `sqlite3` row assertions)

**Interfaces:**
- Consumes: `Simplemdm_mcp_finding_model::computeFingerprint($source, $serialNumber, $findingType, $category)` (Task 2) — must be called with the 4th argument now (not omitted).
- Produces: `ingest_mcp_findings` accepts an optional per-finding `category` field; `get_mcp_findings` gains a `category` query param (comma-separated list). Per-finding response rows automatically include `category` via the existing `$row->toArray()` call — no explicit code change needed for that. No later task in this plan depends on this response shape.

- [ ] **Step 1: In `ingest_mcp_findings()`, extract and store `category` per finding**

In the per-finding loop, immediately after the existing line that computes `$findingType` (currently `$findingType = substr($type, 0, 128);`), insert:

```php
            $category = isset($finding['category']) ? substr(trim((string) $finding['category']), 0, 128) : null;
            $category = $category === '' ? null : $category;
```

Then change the fingerprint computation line from:

```php
            $fingerprint = Simplemdm_mcp_finding_model::computeFingerprint($source, $serialNumber, $findingType);
```

to:

```php
            $fingerprint = Simplemdm_mcp_finding_model::computeFingerprint($source, $serialNumber, $findingType, $category);
```

Then add `'category' => $category,` to BOTH the `$update` array (in the `if ($existing)` branch, alongside the existing `'serial_number' => $serialNumber,` line) AND the `Simplemdm_mcp_finding_model::create([...])` array (in the `else` branch, alongside the existing `'serial_number' => $serialNumber,` line) — `category` should be written on every insert AND every update, consistent with how `serial_number` is already treated (a finding's category can change on recurrence, same as its serial_number/severity/message can).

- [ ] **Step 2: In `get_mcp_findings()`, add the `category` query filter**

Immediately after the existing `source` filter block (the block starting `$source = isset($_GET['source']) ...` and ending with its closing `}`), insert:

```php

        $category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
        if ($category !== '') {
            $categories = array_values(array_filter(array_map('trim', explode(',', $category))));
            if (count($categories) === 1) {
                $query->where('category', $categories[0]);
            } elseif (count($categories) > 1) {
                $query->whereIn('category', $categories);
            }
        }
```

Note: unlike `severity`/`status`/`source`, do NOT lowercase the `category` filter value — category is stored with case preserved (per Task 1/2's design), so filtering must match on the stored case exactly. This matches the storage behavior established in Step 1 above.

- [ ] **Step 3: Verify PHP syntax**

```bash
docker compose -f <repo-root>/docker-compose.yml exec munkireport php -l /var/munkireport/local/modules/simplemdm/simplemdm_controller.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 4: Verify fingerprint compatibility — a category-less push dedupes the same before and after this slice**

```bash
TOKEN="<paste your simplemdm sync token here>"
BASE="http://localhost:8888/index.php?/module/simplemdm"

curl -s -X POST "$BASE/ingest_mcp_findings" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"category_test","replace":false,"findings":[{"serial_number":"C02CATTEST1","finding_type":"cat_check","severity":"info","message":"first push, no category"}]}'
```

Expected JSON: `"inserted":1`.

```bash
curl -s -X POST "$BASE/ingest_mcp_findings" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"category_test","replace":false,"findings":[{"serial_number":"C02CATTEST1","finding_type":"cat_check","severity":"info","message":"second push, still no category"}]}'
```

Expected JSON: `"inserted":0,"updated":1` (deduped to the same row — confirms compatibility).

```bash
sqlite3 <repo-root>/app/db/db.sqlite \
  "SELECT count(*), category FROM simplemdm_mcp_finding WHERE source='category_test';"
```

Expected: `count(*)=1`, `category` is empty/NULL.

- [ ] **Step 5: Verify a different category creates a distinct row (not a collision)**

```bash
curl -s -X POST "$BASE/ingest_mcp_findings" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"category_test","replace":false,"findings":[{"serial_number":"C02CATTEST1","finding_type":"cat_check","severity":"info","message":"same device+type, WITH category","category":"FileVault"}]}'
```

Expected JSON: `"inserted":1` (NOT an update — same `source`/`serial_number`/`finding_type` as the earlier pushes, but a different `category` makes this a distinct fingerprint).

```bash
sqlite3 <repo-root>/app/db/db.sqlite \
  "SELECT count(*), category FROM simplemdm_mcp_finding WHERE source='category_test' ORDER BY id;"
```

Expected: `count(*)=2` total rows for this source — one with empty/NULL category, one with `category='FileVault'` (case preserved as sent).

- [ ] **Step 6: Verify `get_mcp_findings?category=` filtering**

```bash
curl -s "$BASE/get_mcp_findings?source=category_test&category=FileVault" -H "X-SIMPLEMDM-API-KEY: $TOKEN"
```

Expected: `"count":1`, the one finding's `category` is `"FileVault"`.

```bash
curl -s "$BASE/get_mcp_findings?source=category_test" -H "X-SIMPLEMDM-API-KEY: $TOKEN"
```

Expected: `"count":2` (no category filter — both rows returned, since both are `open`/active status).

- [ ] **Step 7: Clean up test rows**

```bash
sqlite3 <repo-root>/app/db/db.sqlite \
  "DELETE FROM simplemdm_mcp_finding WHERE source='category_test';"
```

- [ ] **Step 8: Commit**

```bash
cd <repo-root>
git add local/modules/simplemdm/simplemdm_controller.php
git commit -m "feat(simplemdm): ingest and filter findings by category"
```

---

### Task 4: Documentation update

**Files:**
- Modify: `docs/API_REFERENCE.md` (ingest/read route sections)
- Modify: `CHANGELOG.md` (add new entry)

**Interfaces:**
- Consumes: nothing (docs only).
- Produces: nothing consumed by later tasks — this is the last task in this plan.

- [ ] **Step 1: Update `docs/API_REFERENCE.md`'s `ingest_mcp_findings` and `get_mcp_findings` sections**

In the `ingest_mcp_findings` per-finding field list, add a `category` row: optional string, trimmed and capped at 128 chars, case preserved in storage. Note that the fingerprint/dedup key now includes `category` (case-insensitively) — two findings with the same `source`/`serial_number`/`finding_type` but different `category` are distinct findings, not the same finding updated.

In the `get_mcp_findings` query params list, add `category` (comma-separated list, exact case match — NOT lowercased like `severity`/`status`/`source`).

- [ ] **Step 2: Add a `CHANGELOG.md` entry**

Add to the top of the `## [Unreleased]` section:

```markdown
### Changed
- Findings gain an optional `category` field (e.g. `FileVault`, `Compliance`). The dedup fingerprint now includes `category` alongside `source`/`serial_number`/`finding_type`, matching the original PRD intent — two findings that differ only by category are now distinct findings rather than colliding into one. A finding pushed without `category` continues to dedupe exactly as before this change (hashes against an empty category, both before and after the migration). `get_mcp_findings` gains a `category` filter (comma-separated, case-sensitive exact match).
```

- [ ] **Step 3: Commit**

```bash
cd <repo-root>
git add local/modules/simplemdm/docs/API_REFERENCE.md local/modules/simplemdm/CHANGELOG.md
git commit -m "docs(simplemdm): document category field and fingerprint rescoping"
```

---

## Explicitly Out of Scope for This Slice

- `get_mcp_finding_stats`, `export_mcp_findings`, `get_mcp_scan_status` — Slice B, a separate plan, built on top of this slice's `category` column.
- `risk_score`, `device_id`, `udid`, `device_name` — still deferred.
- Any widget/UI changes.

## Self-Review Notes

- **Spec coverage check:** Design spec's migration section → Task 1. Model section → Task 2, verified in Step 2's three-way smoke check (default vs. explicit `''` vs. real category). Compatibility note → Task 3 Step 4 (explicit before/after dedup test). Controller ingest section (case preserved, capped 128, null-when-absent) → Task 3 Step 1. `get_mcp_findings` filter section (comma-separated, NOT lowercased) → Task 3 Step 2, verified in Step 6. Testing approach's four bullet points → Task 3 Steps 4-6 cover all four. All design sections have a corresponding task step.
- **Backward compatibility check:** the single most important property of this slice — a category-less ingest dedupes identically before and after — is explicitly tested in Task 3 Step 4, not just asserted. No existing route signature, response field, or other behavior changes; `category` is purely additive to both request and response shapes.

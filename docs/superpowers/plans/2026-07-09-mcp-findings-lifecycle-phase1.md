# MCP Findings Lifecycle (Phase 1 Slice) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the current delete-and-replace `ingest_mcp_findings` behavior with fingerprint-based upsert semantics (status, occurrence_count, first_seen_at, last_seen_at, resolved_at, scan_id), so findings persist history, dedupe correctly, reopen when they recur, and auto-resolve on complete scans — without touching any UI, MCP-side publisher, Event integration, or admin settings (those are later PRD phases).

**Architecture:** This is a two-file change plus one additive migration inside the existing `simplemdm` MunkiReport module: (1) a new migration adds lifecycle columns to `simplemdm_mcp_finding` and backfills existing rows, (2) `Simplemdm_mcp_finding_model` gains status constants and a deterministic `computeFingerprint()` helper, (3) `simplemdm_controller.php`'s `ingest_mcp_findings()` is rewritten to upsert-by-`(source, fingerprint)` instead of delete-and-replace, and `get_mcp_findings()` gains `status`/`since`/`offset`/`scan_id` filters plus a default "active statuses only" view so the existing dashboard widget's displayed counts don't change meaning. No route signatures, widget files, or MCP-server code change.

**Tech Stack:** PHP 8.1, Illuminate/Database (Eloquent) with SQLite (local dev, `app/db/db.sqlite`, bind-mounted into the `munkireport-local` Docker container) and MySQL (production) via `doctrine/dbal ~3.6` (already a dependency, required for SQLite `ALTER TABLE` support through Laravel's schema builder).

**Verification approach — deviates from strict PHPUnit TDD:** This module (and this MunkiReport install generally) has zero PHPUnit coverage for controllers/models — the only existing tests are root-level `tests/Unit/ConfigTest.php` for env/config parsing, with no DB-backed Feature test harness. Building one from scratch is out of scope for this slice and isn't how prior fixes in this module were verified (per project history, verification has consistently been live `curl`/browser checks against the running `munkireport-local` Docker container). This plan follows that established pattern: every task's "test" step is a live HTTP request against the running container plus a direct `sqlite3` read of `app/db/db.sqlite` to assert row state, run from the module's `docs/` sibling shell rather than from a PHP test runner.

## Global Constraints

- Do not edit `migrations/2026_07_07_000000_simplemdm_mcp_finding.php` — per `README.md:705-706`, shipped migrations are immutable once deployed; all schema changes must be a new additive migration file.
- Existing routes, widget files, and response fields consumed by `views/simplemdm_mcp_findings_widget.php` (`totals.danger/warning/info`, and per-finding `severity`, `finding_type`, `serial_number`, `message`, `source`, `reported_at`, `data`) must keep returning the same shape and same *meaning* — i.e., after this change the widget must still only reflect currently-open-ish findings, not historical resolved ones, even though resolved rows now persist in the table.
- `ingest_mcp_findings` payload cap (2 MB, 2000 findings/push) and per-field truncation (`finding_type` 128 chars, `message` 1000 chars, `data` 4096 chars, `serial_number` 64 chars) stay as-is.
- Do not add `category`, `risk_score`, `device_id`, `udid`, Event-widget integration, admin settings, suppression rules, a device-page findings tab, or any MCP-server (TypeScript) changes — those are separate, later phases of the PRD and out of scope here.
- N+1 query note: this slice upserts row-by-row (find-or-create per finding) rather than a bulk SQL upsert, matching the existing per-row validation style. This is acceptable at the current 2000-findings/push cap but is a known follow-up for the performance-hardening phase (PRD §18) — do not attempt to batch-optimize in this slice.

---

### Task 1: Migration — add lifecycle columns and backfill

**Files:**
- Create: `local/modules/simplemdm/migrations/2026_07_09_000000_simplemdm_mcp_finding_lifecycle.php`
- Test: manual (`php please migrate` against the running container + `sqlite3` schema/row check)

**Interfaces:**
- Produces: seven new nullable/defaulted columns on `simplemdm_mcp_finding` — `fingerprint` (string), `status` (string, default `open`), `occurrence_count` (unsigned int, default 1), `scan_id` (string, nullable), `first_seen_at` (datetime, nullable), `last_seen_at` (datetime, nullable), `resolved_at` (datetime, nullable) — plus a unique index `uniq_simplemdm_mcp_finding_source_fingerprint` on `(source, fingerprint)`. Task 2 and Task 3 depend on all of these column names existing exactly as spelled here.

- [ ] **Step 1: Write the migration file**

```php
<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmMcpFindingLifecycle extends Migration
{
    public function up()
    {
        $capsule = new Capsule();
        $schema = $capsule::schema();

        $schema->table('simplemdm_mcp_finding', function (Blueprint $table) {
            $table->string('fingerprint')->nullable()->after('finding_type');
            $table->string('status')->default('open')->index()->after('severity');
            $table->unsignedInteger('occurrence_count')->default(1)->after('status');
            $table->string('scan_id')->nullable()->index()->after('occurrence_count');
            $table->dateTime('first_seen_at')->nullable()->after('reported_at');
            $table->dateTime('last_seen_at')->nullable()->after('first_seen_at');
            $table->dateTime('resolved_at')->nullable()->after('last_seen_at');
        });

        // Backfill existing rows so the new unique index has valid, deterministic
        // data. Must match Simplemdm_mcp_finding_model::computeFingerprint()
        // exactly (Task 2) or future pushes will fail to match these rows.
        $rows = Capsule::table('simplemdm_mcp_finding')
            ->select('id', 'source', 'serial_number', 'finding_type', 'reported_at')
            ->get();
        foreach ($rows as $row) {
            $fingerprint = hash(
                'sha256',
                strtolower((string) $row->source) . '|' . strtolower((string) $row->serial_number) . '|' . strtolower((string) $row->finding_type)
            );
            $seenAt = $row->reported_at ?: gmdate('c');
            Capsule::table('simplemdm_mcp_finding')->where('id', $row->id)->update([
                'fingerprint'      => $fingerprint,
                'status'           => 'open',
                'occurrence_count' => 1,
                'first_seen_at'    => $seenAt,
                'last_seen_at'     => $seenAt,
            ]);
        }

        $schema->table('simplemdm_mcp_finding', function (Blueprint $table) {
            $table->unique(['source', 'fingerprint'], 'uniq_simplemdm_mcp_finding_source_fingerprint');
        });
    }

    public function down()
    {
        $capsule = new Capsule();
        $schema = $capsule::schema();
        $schema->table('simplemdm_mcp_finding', function (Blueprint $table) {
            $table->dropUnique('uniq_simplemdm_mcp_finding_source_fingerprint');
            $table->dropColumn(['fingerprint', 'status', 'occurrence_count', 'scan_id', 'first_seen_at', 'last_seen_at', 'resolved_at']);
        });
    }
}
```

- [ ] **Step 2: Run the migration against the running dev container**

```bash
docker compose -f /Users/helpdesk/websites/munkireport-php/docker-compose.yml exec munkireport php please migrate
```

Expected: output lists `SimplemdmMcpFindingLifecycle` as migrated, no errors.

- [ ] **Step 3: Verify schema and backfill directly against the SQLite file**

```bash
sqlite3 /Users/helpdesk/websites/munkireport-php/app/db/db.sqlite ".schema simplemdm_mcp_finding"
sqlite3 /Users/helpdesk/websites/munkireport-php/app/db/db.sqlite \
  "SELECT id, source, fingerprint, status, occurrence_count, first_seen_at, last_seen_at FROM simplemdm_mcp_finding LIMIT 5;"
```

Expected: schema output includes all seven new columns and the new unique index; every existing row (7 rows as of this plan's writing, sources `mcp_connectivity_test` and `stale_devices`) has a non-null 64-char hex `fingerprint`, `status = 'open'`, `occurrence_count = 1`, and matching `first_seen_at`/`last_seen_at`.

- [ ] **Step 4: Commit**

```bash
cd /Users/helpdesk/websites/munkireport-php
git add local/modules/simplemdm/migrations/2026_07_09_000000_simplemdm_mcp_finding_lifecycle.php
git commit -m "feat(simplemdm): add lifecycle columns to simplemdm_mcp_finding"
```

---

### Task 2: Model — status constants and fingerprint helper

**Files:**
- Modify: `local/modules/simplemdm/simplemdm_mcp_finding_model.php`
- Test: manual (PHP CLI smoke check via `php -r` inside the container)

**Interfaces:**
- Consumes: nothing new (extends existing `Eloquent`/`MRModel`).
- Produces: `Simplemdm_mcp_finding_model::computeFingerprint($source, $serialNumber, $findingType): string` (deterministic sha256 hex string, must match Task 1's backfill formula exactly), and status constants `STATUS_OPEN`, `STATUS_ACKNOWLEDGED`, `STATUS_IN_PROGRESS`, `STATUS_RESOLVED`, `STATUS_IGNORED`, `STATUS_SUPPRESSED`, and `ACTIVE_STATUSES` (array of the first three). Task 3 calls all of these by exact name.

- [ ] **Step 1: Replace the model file contents**

```php
<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_mcp_finding_model extends Eloquent
{
    protected $table = 'simplemdm_mcp_finding';

    protected $fillable = [
        'serial_number',
        'source',
        'finding_type',
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

    public $timestamps = false;

    const STATUS_OPEN = 'open';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_IGNORED = 'ignored';
    const STATUS_SUPPRESSED = 'suppressed';

    const ACTIVE_STATUSES = [self::STATUS_OPEN, self::STATUS_ACKNOWLEDGED, self::STATUS_IN_PROGRESS];

    /**
     * Deterministic dedup key: same source + serial_number + finding_type
     * always maps to the same fingerprint, so repeated ingest pushes upsert
     * the same row instead of creating duplicates. Must stay byte-for-byte
     * identical to the backfill formula in migration
     * 2026_07_09_000000_simplemdm_mcp_finding_lifecycle.php.
     */
    public static function computeFingerprint($source, $serialNumber, $findingType)
    {
        return hash(
            'sha256',
            strtolower((string) $source) . '|' . strtolower((string) $serialNumber) . '|' . strtolower((string) $findingType)
        );
    }
}
```

- [ ] **Step 2: Smoke-check the fingerprint helper matches the backfilled values**

```bash
docker compose -f /Users/helpdesk/websites/munkireport-php/docker-compose.yml exec munkireport php -r '
require "/var/munkireport/local/modules/simplemdm/simplemdm_mcp_finding_model.php";
echo Simplemdm_mcp_finding_model::computeFingerprint("stale_devices", "C02EXAMPLE", "stale_device"), PHP_EOL;
'
```

Expected: a 64-character lowercase hex string, with no PHP errors/warnings.

- [ ] **Step 3: Commit**

```bash
cd /Users/helpdesk/websites/munkireport-php
git add local/modules/simplemdm/simplemdm_mcp_finding_model.php
git commit -m "feat(simplemdm): add finding lifecycle status constants and fingerprint helper"
```

---

### Task 3: Controller — upsert-by-fingerprint ingest with auto-resolve

**Files:**
- Modify: `local/modules/simplemdm/simplemdm_controller.php:6418-6508` (`ingest_mcp_findings()`)
- Test: manual (`curl` against the live container + `sqlite3` row assertions)

**Interfaces:**
- Consumes: `Simplemdm_mcp_finding_model::computeFingerprint()`, `::STATUS_*` constants, `::ACTIVE_STATUSES` (Task 2).
- Produces: same route signature (`POST ingest_mcp_findings`, sync-token auth via `is_valid_sync_token()`), but response JSON now includes `scan_id`, `received`, `inserted`, `updated`, `reopened`, `resolved`, `skipped`, `replace` (replaces the old `stored`/`replaced` keys — no other route depends on those old keys; confirmed no other file in this module or the SimpleMDM-MCP repo reads the ingest response body). Task 4 does not depend on this response shape.

- [ ] **Step 1: Replace `ingest_mcp_findings()` (lines 6418-6508)**

```php
    public function ingest_mcp_findings()
    {
        $this->connectDB();
        if (! $this->is_valid_sync_token()) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        $payload = file_get_contents('php://input');
        if (strlen($payload) > 2097152) {
            jsonView(['status' => 'error', 'message' => 'Payload too large (2 MB cap)'], 413);
            return;
        }
        $data = json_decode($payload, true);
        if (! is_array($data)) {
            jsonView(['status' => 'error', 'message' => 'Invalid JSON data'], 400);
            return;
        }

        $source = isset($data['source']) ? strtolower(trim((string) $data['source'])) : '';
        if ($source === '' || ! preg_match('/^[a-z0-9_\-]{1,64}$/', $source)) {
            jsonView(['status' => 'error', 'message' => 'source is required (a-z, 0-9, _, -)'], 400);
            return;
        }

        $findings = isset($data['findings']) && is_array($data['findings']) ? $data['findings'] : null;
        if ($findings === null) {
            jsonView(['status' => 'error', 'message' => 'findings array is required'], 400);
            return;
        }
        if (count($findings) > 2000) {
            jsonView(['status' => 'error', 'message' => 'Too many findings (2000 cap per push)'], 413);
            return;
        }

        $scanId = isset($data['scan_id']) ? substr(trim((string) $data['scan_id']), 0, 128) : '';
        if ($scanId === '') {
            $scanId = 'scan_' . gmdate('Ymd\THis\Z') . '_' . bin2hex(random_bytes(4));
        }

        $valid_severities = ['danger', 'warning', 'info'];
        $now = gmdate('c');
        $skipped = 0;
        $inserted = 0;
        $updated = 0;
        $reopened = 0;
        $touchedIds = [];

        foreach ($findings as $finding) {
            if (! is_array($finding)) {
                $skipped++;
                continue;
            }
            $type = isset($finding['finding_type']) ? trim((string) $finding['finding_type']) : '';
            $message = isset($finding['message']) ? trim((string) $finding['message']) : '';
            if ($type === '' || $message === '') {
                $skipped++;
                continue;
            }
            $severity = isset($finding['severity']) ? strtolower(trim((string) $finding['severity'])) : 'info';
            if (! in_array($severity, $valid_severities, true)) {
                $severity = 'info';
            }
            $extra = '';
            if (isset($finding['data']) && $finding['data'] !== null && $finding['data'] !== '') {
                $extra = is_string($finding['data']) ? $finding['data'] : json_encode($finding['data']);
                if ($extra === false) {
                    $extra = '';
                }
                if (strlen($extra) > 4096) {
                    $extra = substr($extra, 0, 4096);
                }
            }

            $serialNumber = isset($finding['serial_number']) ? substr(trim((string) $finding['serial_number']), 0, 64) : null;
            $findingType = substr($type, 0, 128);
            $fingerprint = Simplemdm_mcp_finding_model::computeFingerprint($source, $serialNumber, $findingType);

            $existing = Simplemdm_mcp_finding_model::where('source', $source)
                ->where('fingerprint', $fingerprint)
                ->first();

            if ($existing) {
                $wasResolved = $existing->status === Simplemdm_mcp_finding_model::STATUS_RESOLVED;
                $isSuppressedOrIgnored = in_array($existing->status, [
                    Simplemdm_mcp_finding_model::STATUS_SUPPRESSED,
                    Simplemdm_mcp_finding_model::STATUS_IGNORED,
                ], true);

                $update = [
                    'serial_number'    => $serialNumber,
                    'severity'         => $severity,
                    'message'          => substr($message, 0, 1000),
                    'data'             => $extra,
                    'reported_at'      => $now,
                    'last_seen_at'     => $now,
                    'scan_id'          => $scanId,
                    'occurrence_count' => $existing->occurrence_count + 1,
                ];
                if ($wasResolved) {
                    $update['status'] = Simplemdm_mcp_finding_model::STATUS_OPEN;
                    $update['resolved_at'] = null;
                    $reopened++;
                } elseif (! $isSuppressedOrIgnored) {
                    $updated++;
                }
                $existing->fill($update);
                $existing->save();
                $touchedIds[] = $existing->id;
            } else {
                $row = Simplemdm_mcp_finding_model::create([
                    'serial_number'    => $serialNumber,
                    'source'           => $source,
                    'finding_type'     => $findingType,
                    'fingerprint'      => $fingerprint,
                    'severity'         => $severity,
                    'status'           => Simplemdm_mcp_finding_model::STATUS_OPEN,
                    'occurrence_count' => 1,
                    'scan_id'          => $scanId,
                    'message'          => substr($message, 0, 1000),
                    'data'             => $extra,
                    'reported_at'      => $now,
                    'first_seen_at'    => $now,
                    'last_seen_at'     => $now,
                    'resolved_at'      => null,
                ]);
                $inserted++;
                $touchedIds[] = $row->id;
            }
        }

        $replace = ! isset($data['replace']) || $data['replace'] !== false;

        if ($replace && count($findings) > 0 && $skipped === count($findings)) {
            jsonView([
                'status'  => 'error',
                'message' => 'All findings failed validation; refusing to auto-resolve on a fully invalid replace payload',
            ], 400);
            return;
        }

        $resolved = 0;
        if ($replace) {
            $staleQuery = Simplemdm_mcp_finding_model::where('source', $source)
                ->whereIn('status', Simplemdm_mcp_finding_model::ACTIVE_STATUSES);
            if (! empty($touchedIds)) {
                $staleQuery->whereNotIn('id', $touchedIds);
            }
            $resolved = $staleQuery->count();
            $staleQuery->update([
                'status'      => Simplemdm_mcp_finding_model::STATUS_RESOLVED,
                'resolved_at' => $now,
            ]);
        }

        jsonView([
            'status'   => 'success',
            'source'   => $source,
            'scan_id'  => $scanId,
            'received' => count($findings),
            'inserted' => $inserted,
            'updated'  => $updated,
            'reopened' => $reopened,
            'resolved' => $resolved,
            'skipped'  => $skipped,
            'replace'  => $replace,
        ]);
    }
```

- [ ] **Step 2: Reset test data and verify insert-then-reopen-then-resolve lifecycle live**

The dev container mounts `local/` and `app/db/` live, so no rebuild is needed — edits take effect immediately.

```bash
# Use the module's own stored sync token from Admin > SimpleMDM settings (do not print it to logs/chat).
TOKEN="<paste your simplemdm sync token here>"
BASE="http://localhost:8888/index.php?/module/simplemdm/index"

# 1. First push: creates a new open finding.
curl -s -X POST "$BASE?op=ingest_mcp_findings" \
  -H "Content-Type: application/json" \
  -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"phase1_test","scan_id":"scan_test_1","replace":true,
       "findings":[{"serial_number":"C02PHASE1TEST","finding_type":"phase1_lifecycle_check","severity":"warning","message":"first push"}]}'
```

Expected JSON: `"inserted":1,"updated":0,"reopened":0,"resolved":0,"skipped":0`.

```bash
sqlite3 /Users/helpdesk/websites/munkireport-php/app/db/db.sqlite \
  "SELECT status, occurrence_count, first_seen_at, last_seen_at, resolved_at FROM simplemdm_mcp_finding WHERE source='phase1_test';"
```

Expected: one row, `status=open`, `occurrence_count=1`, `resolved_at` empty.

```bash
# 2. Second push, same finding: should update in place (dedupe), not duplicate.
curl -s -X POST "$BASE?op=ingest_mcp_findings" \
  -H "Content-Type: application/json" \
  -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"phase1_test","scan_id":"scan_test_2","replace":true,
       "findings":[{"serial_number":"C02PHASE1TEST","finding_type":"phase1_lifecycle_check","severity":"warning","message":"second push"}]}'
```

Expected JSON: `"inserted":0,"updated":1,"reopened":0,"resolved":0`.

```bash
sqlite3 /Users/helpdesk/websites/munkireport-php/app/db/db.sqlite \
  "SELECT count(*), occurrence_count, status FROM simplemdm_mcp_finding WHERE source='phase1_test';"
```

Expected: still exactly 1 row (`count(*)=1`), `occurrence_count=2`, `status=open`.

```bash
# 3. Third push with replace=true and no findings: complete scan found nothing -> auto-resolve.
curl -s -X POST "$BASE?op=ingest_mcp_findings" \
  -H "Content-Type: application/json" \
  -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"phase1_test","scan_id":"scan_test_3","replace":true,"findings":[]}'
```

Expected JSON: `"resolved":1`.

```bash
sqlite3 /Users/helpdesk/websites/munkireport-php/app/db/db.sqlite \
  "SELECT status, resolved_at FROM simplemdm_mcp_finding WHERE source='phase1_test';"
```

Expected: `status=resolved`, `resolved_at` populated.

```bash
# 4. Fourth push, same finding reappears: should reopen, not duplicate.
curl -s -X POST "$BASE?op=ingest_mcp_findings" \
  -H "Content-Type: application/json" \
  -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"phase1_test","scan_id":"scan_test_4","replace":true,
       "findings":[{"serial_number":"C02PHASE1TEST","finding_type":"phase1_lifecycle_check","severity":"warning","message":"reappeared"}]}'
```

Expected JSON: `"inserted":0,"updated":0,"reopened":1`.

```bash
sqlite3 /Users/helpdesk/websites/munkireport-php/app/db/db.sqlite \
  "SELECT count(*), status, occurrence_count, resolved_at FROM simplemdm_mcp_finding WHERE source='phase1_test';"
```

Expected: `count(*)=1`, `status=open`, `occurrence_count=3`, `resolved_at` empty again.

- [ ] **Step 3: Clean up test rows**

```bash
sqlite3 /Users/helpdesk/websites/munkireport-php/app/db/db.sqlite \
  "DELETE FROM simplemdm_mcp_finding WHERE source='phase1_test';"
```

- [ ] **Step 4: Commit**

```bash
cd /Users/helpdesk/websites/munkireport-php
git add local/modules/simplemdm/simplemdm_controller.php
git commit -m "feat(simplemdm): upsert-by-fingerprint ingest with reopen/auto-resolve lifecycle"
```

---

### Task 4: Controller — read-back filters and widget-safe defaults on `get_mcp_findings`

**Files:**
- Modify: `local/modules/simplemdm/simplemdm_controller.php:6516-6557` (`get_mcp_findings()`)
- Test: manual (`curl` against the live container)

**Interfaces:**
- Consumes: `Simplemdm_mcp_finding_model::ACTIVE_STATUSES` (Task 2).
- Produces: same route signature (`GET get_mcp_findings[/serial]`). Response JSON adds `status_totals` (counts across all six statuses) alongside the existing `totals` (severity counts, now scoped to `ACTIVE_STATUSES` only, matching what the widget showed before this change). Adds query params `status` (comma-separated), `since` (parses via `strtotime`), `offset`, `scan_id`. When no `status` param is given, the finding list defaults to `ACTIVE_STATUSES` only — this is required so the existing widget (which never passes `status`) doesn't start showing resolved findings as if they were still active.

- [ ] **Step 1: Replace `get_mcp_findings()` (lines 6516-6557)**

```php
    public function get_mcp_findings($serial_number = '')
    {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
        if ($limit < 1) {
            $limit = 100;
        }
        if ($limit > 500) {
            $limit = 500;
        }
        $offset = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;

        $query = Simplemdm_mcp_finding_model::orderBy('id', 'desc')->limit($limit)->offset($offset);

        $serial_number = trim((string) $serial_number);
        if ($serial_number !== '') {
            $query->where('serial_number', $serial_number);
        }

        $severity = isset($_GET['severity']) ? strtolower(trim((string) $_GET['severity'])) : '';
        if ($severity !== '') {
            $severities = array_values(array_filter(array_map('trim', explode(',', $severity))));
            if (count($severities) === 1) {
                $query->where('severity', $severities[0]);
            } elseif (count($severities) > 1) {
                $query->whereIn('severity', $severities);
            }
        }

        $status = isset($_GET['status']) ? strtolower(trim((string) $_GET['status'])) : '';
        if ($status !== '') {
            $statuses = array_values(array_filter(array_map('trim', explode(',', $status))));
            if (count($statuses) === 1) {
                $query->where('status', $statuses[0]);
            } elseif (count($statuses) > 1) {
                $query->whereIn('status', $statuses);
            }
        } else {
            $query->whereIn('status', Simplemdm_mcp_finding_model::ACTIVE_STATUSES);
        }

        $source = isset($_GET['source']) ? strtolower(trim((string) $_GET['source'])) : '';
        if ($source !== '') {
            $query->where('source', $source);
        }

        $scanId = isset($_GET['scan_id']) ? trim((string) $_GET['scan_id']) : '';
        if ($scanId !== '') {
            $query->where('scan_id', $scanId);
        }

        $since = isset($_GET['since']) ? trim((string) $_GET['since']) : '';
        if ($since !== '' && strtotime($since) !== false) {
            $query->where('last_seen_at', '>=', gmdate('c', strtotime($since)));
        }

        $rows = [];
        foreach ($query->get() as $row) {
            $rows[] = $row->toArray();
        }

        $totals = [
            'danger'  => (int) Simplemdm_mcp_finding_model::whereIn('status', Simplemdm_mcp_finding_model::ACTIVE_STATUSES)->where('severity', 'danger')->count(),
            'warning' => (int) Simplemdm_mcp_finding_model::whereIn('status', Simplemdm_mcp_finding_model::ACTIVE_STATUSES)->where('severity', 'warning')->count(),
            'info'    => (int) Simplemdm_mcp_finding_model::whereIn('status', Simplemdm_mcp_finding_model::ACTIVE_STATUSES)->where('severity', 'info')->count(),
        ];
        $status_totals = [
            'open'         => (int) Simplemdm_mcp_finding_model::where('status', 'open')->count(),
            'acknowledged' => (int) Simplemdm_mcp_finding_model::where('status', 'acknowledged')->count(),
            'in_progress'  => (int) Simplemdm_mcp_finding_model::where('status', 'in_progress')->count(),
            'resolved'     => (int) Simplemdm_mcp_finding_model::where('status', 'resolved')->count(),
            'ignored'      => (int) Simplemdm_mcp_finding_model::where('status', 'ignored')->count(),
            'suppressed'   => (int) Simplemdm_mcp_finding_model::where('status', 'suppressed')->count(),
        ];

        jsonView([
            'count'         => count($rows),
            'totals'        => $totals,
            'status_totals' => $status_totals,
            'findings'      => $rows,
        ]);
    }
```

- [ ] **Step 2: Verify default view excludes resolved findings, and explicit status filter includes them**

```bash
TOKEN="<paste your simplemdm sync token here>"
BASE="http://localhost:8888/index.php?/module/simplemdm/index"

# Push one finding, then resolve it via an empty complete-scan push (reuses Task 3's flow).
curl -s -X POST "$BASE?op=ingest_mcp_findings" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"phase1_test2","replace":true,"findings":[{"serial_number":"C02PHASE1TEST2","finding_type":"phase1_check2","severity":"info","message":"will be resolved"}]}' > /dev/null
curl -s -X POST "$BASE?op=ingest_mcp_findings" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"phase1_test2","replace":true,"findings":[]}' > /dev/null

# Default call (no status param) — must NOT include the now-resolved finding.
curl -s "$BASE?op=get_mcp_findings&source=phase1_test2" -H "X-SIMPLEMDM-API-KEY: $TOKEN"
```

Expected: `"count":0`, `"findings":[]`.

```bash
# Explicit status=resolved — must include it.
curl -s "$BASE?op=get_mcp_findings&source=phase1_test2&status=resolved" -H "X-SIMPLEMDM-API-KEY: $TOKEN"
```

Expected: `"count":1`, finding's `status` is `resolved`, `status_totals.resolved >= 1`.

```bash
sqlite3 /Users/helpdesk/websites/munkireport-php/app/db/db.sqlite \
  "DELETE FROM simplemdm_mcp_finding WHERE source='phase1_test2';"
```

- [ ] **Step 3: Verify the existing MCP Findings dashboard widget still renders correctly in browser**

Open `http://localhost:8888/index.php?/dashboard` (or `/clients`, whichever the SimpleMDM widgets are currently placed on per prior session notes) and confirm the "MCP Findings" panel loads without JS console errors and shows the same style of summary/list it did before this change (severity badges, totals, recent items) — no `status` field should leak into the visual rendering since the widget doesn't read it.

- [ ] **Step 4: Commit**

```bash
cd /Users/helpdesk/websites/munkireport-php
git add local/modules/simplemdm/simplemdm_controller.php
git commit -m "feat(simplemdm): add status/since/offset/scan_id filters to get_mcp_findings"
```

---

### Task 5: Documentation update

**Files:**
- Modify: `local/modules/simplemdm/README.md` (MCP findings section — same section touched during the widget-detail doc pass, per CHANGELOG.md)
- Modify: `local/modules/simplemdm/CHANGELOG.md` (add new entry)
- Modify: `local/modules/simplemdm/docs/API_REFERENCE.md` (ingest/read route descriptions)

**Interfaces:**
- Consumes: nothing (docs only).
- Produces: nothing consumed by later tasks — this is the last task in the slice.

- [ ] **Step 1: Update `API_REFERENCE.md`'s `ingest_mcp_findings` and `get_mcp_findings` sections**

Document: the new request field `scan_id` (optional, server-generates one if omitted), the new response fields (`scan_id`, `received`, `inserted`, `updated`, `reopened`, `resolved`, `skipped`, `replace` — note the old `stored`/`replaced` keys are gone), the new `status`/`since`/`offset`/`scan_id` query params on `get_mcp_findings`, the new `status_totals` response key, and the lifecycle behavior: repeated pushes for the same `(source, serial_number, finding_type)` update the existing row instead of duplicating; a `replace:true` push (the default) resolves any previously-open finding for that `source` that wasn't present in the current push; a resolved finding reopens if it reappears in a later push; `get_mcp_findings` without an explicit `status` filter only returns `open`/`acknowledged`/`in_progress` findings.

- [ ] **Step 2: Add a CHANGELOG.md entry**

```markdown
## [Unreleased]
### Changed
- `ingest_mcp_findings` now upserts findings by a deterministic `(source, serial_number, finding_type)` fingerprint instead of deleting and replacing all findings for a source on every push. Findings persist `status`, `occurrence_count`, `first_seen_at`, `last_seen_at`, and `resolved_at`. A complete scan (`replace: true`, the default) auto-resolves findings from that source that were not present in the push; a resolved finding reopens if it reappears later.
- `get_mcp_findings` gains `status`, `since`, `offset`, and `scan_id` filters, and a new `status_totals` response field. Without an explicit `status` filter it now returns only active (`open`/`acknowledged`/`in_progress`) findings, matching what the dashboard widget always displayed.
```

- [ ] **Step 3: Update README.md's MCP findings description**

Add one paragraph noting findings now have lifecycle status and history instead of being wiped on every push, and link to the CHANGELOG entry for the full field list.

- [ ] **Step 4: Commit**

```bash
cd /Users/helpdesk/websites/munkireport-php
git add local/modules/simplemdm/README.md local/modules/simplemdm/CHANGELOG.md local/modules/simplemdm/docs/API_REFERENCE.md
git commit -m "docs(simplemdm): document MCP findings lifecycle upsert behavior"
```

---

## Explicitly Out of Scope for This Slice

These are real parts of the PRD but belong to later phases — do not implement them here:

- `category`, `risk_score`, `device_id`, `udid`, `device_name` columns and fingerprint scoping by category.
- Event widget summary generation (PRD §13) — nothing here writes to `Event_model`.
- Admin settings UI for thresholds/retention/suppression (PRD §11.5).
- `acknowledge_mcp_finding`, `resolve_mcp_finding`, `ignore_mcp_finding`, `suppress_mcp_finding` admin routes (PRD §15.3) — status can currently only change via the ingest lifecycle logic in this slice, not via direct admin action.
- Device-page findings section/tab (PRD §14.2).
- Any SimpleMDM-MCP (TypeScript) repo changes — `push_munkireport_findings` already sends a compatible payload shape and needs no changes to keep working against this slice; it just won't get `scan_id`-aware behavior until the MCP-side publisher work (PRD §12) is scoped separately.
- Bulk/batched SQL upsert for performance at 10,000+ findings/push (PRD §18) — flagged as a known follow-up in Global Constraints.

## Self-Review Notes

- **Spec coverage check:** PRD §9.1 required fields `status`, `occurrence_count`, `first_seen`, `last_seen`, `resolved_at` → Task 1. §10.4 lifecycle rules (new fingerprint = new open finding; existing open finding recurs = update; resolved finding recurs = reopen; complete scan resolves absent findings; partial scan does not) → Task 3, verified step-by-step in Task 3 Step 2. §15.2 `get_mcp_findings` filters (`severity`, `status`, `since`, `scan_id` — `category`/`tool`/`offset` partially: `offset` done, `category`/`tool` out of scope since those columns don't exist yet) → Task 4. §16.2 unique index on `(source_module, fingerprint)` → Task 1 (using existing `source` column in place of the PRD's `source_module`, since this module doesn't split source_module/source_tool — noted as a naming difference, not a gap).
- **Backward compatibility check:** widget fields unchanged; existing `stored`/`replaced` ingest response keys are removed since nothing else in either repo reads them (confirmed during research — the MCP-side `push_munkireport_findings` tool doesn't inspect the ingest response body beyond HTTP status).

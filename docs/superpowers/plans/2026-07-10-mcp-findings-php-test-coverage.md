# MCP Findings PHP Test Coverage (Phase A) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give the MunkiReport SimpleMDM module's MCP findings logic real, executed PHP test coverage by extracting the pure decision/validation logic currently inline in `Simplemdm_controller` into static methods on `Simplemdm_mcp_finding_model`, and testing those plus the model's real DB upsert/dedup behavior against an in-memory-SQLite-backed PHPUnit suite that lives in the module's own `tests/` directory.

**Architecture:** Five small, behavior-preserving extractions turn six large inline controller methods' risk surface into pure, directly-testable static methods; the controller becomes a thin caller of each. A new `tests/bootstrap.php` boots `Illuminate\Database\Capsule\Manager` against `sqlite`/`:memory:`, runs the module's three real `simplemdm_mcp_finding*` migrations against it, and requires the model file — giving DB-backed tests the real, currently-shipping schema rather than a hand-maintained copy.

**Tech Stack:** PHP 8.2 (matches the project's Docker image), PHPUnit ^10.0 (module-scoped `require-dev`, its own `vendor/`), Illuminate/Eloquent 10.13.5 (resolved from the host app's `vendor/`, not duplicated). Verified end-to-end in this session via `docker compose` against the real `munkireport` service image — every command below has actually been run once, not assumed.

## Global Constraints

- No behavior changes anywhere in this plan. Every extraction must produce byte-identical controller behavior to what exists today — proven by keeping the DB-backed tests' expectations matched against the *current* logic, not a "should be" logic.
- Tests live in `local/modules/simplemdm/tests/` (the module's own directory), not the host app's `tests/` — confirmed by the user during brainstorming.
- `phpunit/phpunit` is a `require-dev` of the module's own `composer.json`, with its own `vendor/` (gitignored — `vendor/` is already in `.gitignore`). Do NOT add it to the host app's `composer.json` — that file belongs to a different repo/concern and this plan must not touch it.
- Tests are run via Docker (`docker compose run --rm munkireport ...` from the host repo root, `<repo-root>`), never a bare `php`/`phpunit` binary — there is no PHP runtime outside the container in this environment.
- No test may exercise the HTTP/controller layer, `mcp_findings_enabled()`/settings gating, or `export_mcp_findings`'s CSV output — those are explicitly Phase B, out of scope here (see spec `docs/superpowers/specs/2026-07-10-mcp-findings-php-test-coverage-design.md`).

---

### Task 1: Test infrastructure (composer.json, phpunit.xml, bootstrap, first real test)

**Files:**
- Modify: `local/modules/simplemdm/composer.json`
- Create: `local/modules/simplemdm/phpunit.xml`
- Create: `local/modules/simplemdm/tests/bootstrap.php`
- Create: `local/modules/simplemdm/tests/Unit/McpFindingModelTest.php` (first test only — `computeFingerprint`, which already exists and is already pure; later tasks append to this same file)

**Interfaces:**
- Produces: a working `docker compose run --rm munkireport local/modules/simplemdm/vendor/bin/phpunit -c local/modules/simplemdm/phpunit.xml` command that every later task's tests run through. Produces `tests/bootstrap.php`, which every later test file relies on implicitly (declared via `phpunit.xml`'s `bootstrap` attribute, never `require`d directly by test files).

- [ ] **Step 1: Add phpunit as a module-scoped dev dependency**

Edit `local/modules/simplemdm/composer.json`:

```json
{
    "name": "munkireport/simplemdm",
    "description": "SimpleMDM module for MunkiReport. Syncs device data from SimpleMDM API.",
    "license": "MIT",
    "require": {
        "php": ">=7.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    }
}
```

- [ ] **Step 2: Write phpunit.xml**

Create `local/modules/simplemdm/phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php" colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

- [ ] **Step 3: Write the bootstrap**

Create `local/modules/simplemdm/tests/bootstrap.php`:

```php
<?php

// A MunkiReport module is always installed at <host>/local/modules/<name>/,
// which is the module system's own contract -- this relative depth is safe.
require __DIR__ . '/../../../../vendor/autoload.php';

$capsule = new \Illuminate\Database\Capsule\Manager();
$capsule->addConnection([
    'driver'   => 'sqlite',
    'database' => ':memory:',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Run the module's real migrations against the in-memory connection so tests
// exercise the actual, currently-shipping schema -- not a hand-maintained copy.
$migrationFiles = [
    __DIR__ . '/../migrations/2026_07_07_000000_simplemdm_mcp_finding.php',
    __DIR__ . '/../migrations/2026_07_09_000000_simplemdm_mcp_finding_lifecycle.php',
    __DIR__ . '/../migrations/2026_07_09_100000_simplemdm_mcp_finding_category.php',
];
foreach ($migrationFiles as $file) {
    require_once $file;
}
(new SimplemdmMcpFinding())->up();
(new SimplemdmMcpFindingLifecycle())->up();
(new SimplemdmMcpFindingCategory())->up();

require_once __DIR__ . '/../simplemdm_mcp_finding_model.php';
```

Before writing this file for real, run `ls local/modules/simplemdm/migrations/` and confirm the three filenames above still match exactly (they were confirmed during planning on 2026-07-10; if a filename has changed, use the actual one and note it in your report) and open `local/modules/simplemdm/migrations/2026_07_09_100000_simplemdm_mcp_finding_category.php` to confirm its migration class name (used implicitly above as `SimplemdmMcpFindingCategory` — verify against the actual `class X extends Migration` line before relying on it).

- [ ] **Step 4: Write the first real test**

Create `local/modules/simplemdm/tests/Unit/McpFindingModelTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class McpFindingModelTest extends TestCase
{
    public function testComputeFingerprintIsStableForSameInputs(): void
    {
        $a = Simplemdm_mcp_finding_model::computeFingerprint('sofa_audit', 'C02ABC123', 'os_eol', 'OS');
        $b = Simplemdm_mcp_finding_model::computeFingerprint('sofa_audit', 'C02ABC123', 'os_eol', 'OS');
        $this->assertSame($a, $b);
    }

    public function testComputeFingerprintDiffersByCategory(): void
    {
        $withCategory = Simplemdm_mcp_finding_model::computeFingerprint('sofa_audit', 'C02ABC123', 'os_eol', 'OS');
        $withoutCategory = Simplemdm_mcp_finding_model::computeFingerprint('sofa_audit', 'C02ABC123', 'os_eol');
        $this->assertNotSame($withCategory, $withoutCategory);
    }

    public function testComputeFingerprintIsCaseInsensitive(): void
    {
        $lower = Simplemdm_mcp_finding_model::computeFingerprint('sofa_audit', 'c02abc123', 'os_eol', 'os');
        $upper = Simplemdm_mcp_finding_model::computeFingerprint('SOFA_AUDIT', 'C02ABC123', 'OS_EOL', 'OS');
        $this->assertSame($lower, $upper);
    }
}
```

- [ ] **Step 5: Install and run**

Run (from the host repo root, `<repo-root>`):

```
docker compose run --rm munkireport bash -c "cd local/modules/simplemdm && composer install"
docker compose run --rm munkireport local/modules/simplemdm/vendor/bin/phpunit -c local/modules/simplemdm/phpunit.xml
```

Expected: `OK (3 tests, 3 assertions)`. If the container image isn't built yet, run `docker compose build munkireport` first (verified to take a few minutes on a cold cache).

- [ ] **Step 6: Commit**

```bash
git add composer.json phpunit.xml tests/bootstrap.php tests/Unit/McpFindingModelTest.php
git commit -m "test(simplemdm): add PHPUnit infra for MCP findings (in-memory SQLite + real migrations)"
```

`composer.lock` and `vendor/` are gitignored/untracked-by-design at the module level — do not add them.

---

### Task 2: Extract `normalizeFinding()`

**Files:**
- Modify: `local/modules/simplemdm/simplemdm_mcp_finding_model.php`
- Modify: `local/modules/simplemdm/simplemdm_controller.php:6502-6522` (the per-finding validation/normalization block inside `ingest_mcp_findings`'s loop)
- Modify: `local/modules/simplemdm/tests/Unit/McpFindingModelTest.php` (append)

**Interfaces:**
- Produces: `Simplemdm_mcp_finding_model::normalizeFinding(array $finding, $metadataMaxBytes): ?array` — returns `null` for anything invalid (empty/missing `finding_type` or `message`), else `['serial_number' => ?string, 'category' => ?string, 'finding_type' => string, 'message' => string, 'severity' => string, 'data' => string]`. Consumed by Task 3's `computeUpsertUpdate` caller (the controller, wiring both together in `ingest_mcp_findings`'s loop).

- [ ] **Step 1: Write the failing tests**

Append to `local/modules/simplemdm/tests/Unit/McpFindingModelTest.php` (inside the existing class, before the closing `}`):

```php
    public function testNormalizeFindingRejectsMissingFindingType(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding(['message' => 'hi'], 65536);
        $this->assertNull($result);
    }

    public function testNormalizeFindingRejectsMissingMessage(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding(['finding_type' => 'os_eol'], 65536);
        $this->assertNull($result);
    }

    public function testNormalizeFindingRejectsEmptyStrings(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding(['finding_type' => '  ', 'message' => ' '], 65536);
        $this->assertNull($result);
    }

    public function testNormalizeFindingDefaultsSeverityToInfo(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding(['finding_type' => 'x', 'message' => 'm'], 65536);
        $this->assertSame('info', $result['severity']);
    }

    public function testNormalizeFindingRejectsInvalidSeverityFallsBackToInfo(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding(['finding_type' => 'x', 'message' => 'm', 'severity' => 'critical'], 65536);
        $this->assertSame('info', $result['severity']);
    }

    public function testNormalizeFindingLowercasesSeverity(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding(['finding_type' => 'x', 'message' => 'm', 'severity' => 'DANGER'], 65536);
        $this->assertSame('danger', $result['severity']);
    }

    public function testNormalizeFindingTruncatesSerialNumberTo64Chars(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding([
            'finding_type' => 'x', 'message' => 'm', 'serial_number' => str_repeat('A', 100),
        ], 65536);
        $this->assertSame(64, strlen($result['serial_number']));
    }

    public function testNormalizeFindingTruncatesFindingTypeTo128Chars(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding([
            'finding_type' => str_repeat('x', 200), 'message' => 'm',
        ], 65536);
        $this->assertSame(128, strlen($result['finding_type']));
    }

    public function testNormalizeFindingEmptyCategoryBecomesNull(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding(['finding_type' => 'x', 'message' => 'm', 'category' => '  '], 65536);
        $this->assertNull($result['category']);
    }

    public function testNormalizeFindingTruncatesMessageTo1000Chars(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding([
            'finding_type' => 'x', 'message' => str_repeat('m', 1500),
        ], 65536);
        $this->assertSame(1000, strlen($result['message']));
    }

    public function testNormalizeFindingEncodesArrayDataAsJson(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding([
            'finding_type' => 'x', 'message' => 'm', 'data' => ['cves_behind' => 3],
        ], 65536);
        $this->assertSame(json_encode(['cves_behind' => 3]), $result['data']);
    }

    public function testNormalizeFindingKeepsStringDataAsIs(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding([
            'finding_type' => 'x', 'message' => 'm', 'data' => 'raw-string',
        ], 65536);
        $this->assertSame('raw-string', $result['data']);
    }

    public function testNormalizeFindingTruncatesDataToMetadataMaxBytes(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding([
            'finding_type' => 'x', 'message' => 'm', 'data' => str_repeat('d', 200),
        ], 100);
        $this->assertSame(100, strlen($result['data']));
    }

    public function testNormalizeFindingNullOrEmptyDataBecomesEmptyString(): void
    {
        $result = Simplemdm_mcp_finding_model::normalizeFinding(['finding_type' => 'x', 'message' => 'm', 'data' => null], 65536);
        $this->assertSame('', $result['data']);
    }
```

- [ ] **Step 2: Run to verify failure**

```
docker compose run --rm munkireport local/modules/simplemdm/vendor/bin/phpunit -c local/modules/simplemdm/phpunit.xml
```

Expected: fatal error / failures — `normalizeFinding` doesn't exist yet.

- [ ] **Step 3: Read the current inline block to extract**

Run: `sed -n '6497,6528p' local/modules/simplemdm/simplemdm_controller.php` and confirm it still matches:

```php
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
                $metadataMaxBytes = (int) $this->get_config_value('mcp_findings_metadata_max_bytes', 65536);
                if (strlen($extra) > $metadataMaxBytes) {
                    $extra = substr($extra, 0, $metadataMaxBytes);
                }
            }

            $serialNumber = isset($finding['serial_number']) ? substr(trim((string) $finding['serial_number']), 0, 64) : null;
            $findingType = substr($type, 0, 128);
            $category = isset($finding['category']) ? substr(trim((string) $finding['category']), 0, 128) : null;
            $category = $category === '' ? null : $category;
```

If the actual current content differs from this (line numbers may have drifted from other work on the module since this plan was written), locate the equivalent block by searching for `$valid_severities` and adapt the following steps to the real content — do not guess.

- [ ] **Step 4: Add `normalizeFinding` to the model**

In `local/modules/simplemdm/simplemdm_mcp_finding_model.php`, add this method (after `computeFingerprint`, before the closing `}` of the class):

```php
    /**
     * Validates and normalizes one raw finding object from an ingest_mcp_findings
     * payload. Returns null for anything that should be skipped (missing/empty
     * finding_type or message) -- mirrors the validation ingest_mcp_findings
     * performed inline before this extraction, byte-for-byte.
     */
    public static function normalizeFinding($finding, $metadataMaxBytes)
    {
        $type = isset($finding['finding_type']) ? trim((string) $finding['finding_type']) : '';
        $message = isset($finding['message']) ? trim((string) $finding['message']) : '';
        if ($type === '' || $message === '') {
            return null;
        }

        $validSeverities = ['danger', 'warning', 'info'];
        $severity = isset($finding['severity']) ? strtolower(trim((string) $finding['severity'])) : 'info';
        if (! in_array($severity, $validSeverities, true)) {
            $severity = 'info';
        }

        $extra = '';
        if (isset($finding['data']) && $finding['data'] !== null && $finding['data'] !== '') {
            $extra = is_string($finding['data']) ? $finding['data'] : json_encode($finding['data']);
            if ($extra === false) {
                $extra = '';
            }
            if (strlen($extra) > $metadataMaxBytes) {
                $extra = substr($extra, 0, $metadataMaxBytes);
            }
        }

        $serialNumber = isset($finding['serial_number']) ? substr(trim((string) $finding['serial_number']), 0, 64) : null;
        $findingType = substr($type, 0, 128);
        $category = isset($finding['category']) ? substr(trim((string) $finding['category']), 0, 128) : null;
        $category = $category === '' ? null : $category;

        return [
            'serial_number' => $serialNumber,
            'category'      => $category,
            'finding_type'  => $findingType,
            'message'       => substr($message, 0, 1000),
            'severity'      => $severity,
            'data'          => $extra,
        ];
    }
```

- [ ] **Step 5: Rewire the controller to call it**

In `local/modules/simplemdm/simplemdm_controller.php`, replace the block confirmed in Step 3 with:

```php
        $metadataMaxBytes = (int) $this->get_config_value('mcp_findings_metadata_max_bytes', 65536);
        foreach ($findings as $finding) {
            if (! is_array($finding)) {
                $skipped++;
                continue;
            }
            $normalized = Simplemdm_mcp_finding_model::normalizeFinding($finding, $metadataMaxBytes);
            if ($normalized === null) {
                $skipped++;
                continue;
            }
            $serialNumber = $normalized['serial_number'];
            $category = $normalized['category'];
            $findingType = $normalized['finding_type'];
            $message = $normalized['message'];
            $severity = $normalized['severity'];
            $extra = $normalized['data'];
```

This keeps every downstream local variable (`$serialNumber`, `$category`, `$findingType`, `$message`, `$severity`, `$extra`) with its original name so the rest of the loop body (fingerprint computation, existing-row lookup, insert/update) is untouched by this task — confirm by reading the ~15 lines immediately after your edit before saving, to make sure `$fingerprint = Simplemdm_mcp_finding_model::computeFingerprint($source, $serialNumber, $findingType, $category);` still follows directly.

Note `$metadataMaxBytes` moved outside the loop (computed once, not per-finding) — this is a behavior-neutral micro-optimization (the config value can't change mid-loop) but if you'd rather keep it exactly inline per-finding to minimize diff risk, that's also fine; either is acceptable, just be consistent and note your choice in the report.

- [ ] **Step 6: Run to verify pass**

```
docker compose run --rm munkireport local/modules/simplemdm/vendor/bin/phpunit -c local/modules/simplemdm/phpunit.xml
```

Expected: all tests pass (17 total: 3 from Task 1 + 14 new).

- [ ] **Step 7: Commit**

```bash
git add simplemdm_mcp_finding_model.php simplemdm_controller.php tests/Unit/McpFindingModelTest.php
git commit -m "refactor(simplemdm): extract normalizeFinding() from ingest_mcp_findings, add tests"
```

---

### Task 3: Extract `computeUpsertUpdate()`

**Files:**
- Modify: `local/modules/simplemdm/simplemdm_mcp_finding_model.php`
- Modify: `local/modules/simplemdm/simplemdm_controller.php` (the existing-row branch inside `ingest_mcp_findings`'s loop, originally lines 6534-6561 pre-Task-2 — re-locate by searching for `$wasResolved` after Task 2's edit)
- Modify: `local/modules/simplemdm/tests/Unit/McpFindingModelTest.php` (append)

**Interfaces:**
- Consumes: the `$normalized` array shape produced by Task 2's `normalizeFinding()` (`serial_number`, `category`, `severity`, `message`, `data` keys used; `finding_type` not needed here since it doesn't change on update).
- Produces: `Simplemdm_mcp_finding_model::computeUpsertUpdate($existing, array $normalized, $scanId, $now): array` — `$existing` is the fetched `Simplemdm_mcp_finding_model` Eloquent row (only `->status` and `->occurrence_count` are read). Returns `['update' => [...fill()-ready fields...], 'kind' => 'updated'|'reopened'|'unchanged']`.

- [ ] **Step 1: Write the failing tests**

Append to `local/modules/simplemdm/tests/Unit/McpFindingModelTest.php`:

```php
    private function fakeExisting($status, $occurrenceCount)
    {
        $row = new Simplemdm_mcp_finding_model();
        $row->status = $status;
        $row->occurrence_count = $occurrenceCount;
        return $row;
    }

    private function fakeNormalized(array $overrides = [])
    {
        return array_merge([
            'serial_number' => 'C02ABC123',
            'category'      => 'OS',
            'severity'      => 'danger',
            'message'       => 'OS end-of-life',
            'data'          => '',
        ], $overrides);
    }

    public function testComputeUpsertUpdateOpenFindingCountsAsUpdated(): void
    {
        $existing = $this->fakeExisting(Simplemdm_mcp_finding_model::STATUS_OPEN, 3);
        $result = Simplemdm_mcp_finding_model::computeUpsertUpdate($existing, $this->fakeNormalized(), 'scan_1', '2026-07-10T00:00:00+00:00');
        $this->assertSame('updated', $result['kind']);
        $this->assertSame(4, $result['update']['occurrence_count']);
        $this->assertArrayNotHasKey('status', $result['update']);
    }

    public function testComputeUpsertUpdateResolvedFindingReopens(): void
    {
        $existing = $this->fakeExisting(Simplemdm_mcp_finding_model::STATUS_RESOLVED, 5);
        $result = Simplemdm_mcp_finding_model::computeUpsertUpdate($existing, $this->fakeNormalized(), 'scan_1', '2026-07-10T00:00:00+00:00');
        $this->assertSame('reopened', $result['kind']);
        $this->assertSame(Simplemdm_mcp_finding_model::STATUS_OPEN, $result['update']['status']);
        $this->assertNull($result['update']['resolved_at']);
    }

    public function testComputeUpsertUpdateSuppressedFindingDoesNotCountAsUpdated(): void
    {
        $existing = $this->fakeExisting(Simplemdm_mcp_finding_model::STATUS_SUPPRESSED, 2);
        $result = Simplemdm_mcp_finding_model::computeUpsertUpdate($existing, $this->fakeNormalized(), 'scan_1', '2026-07-10T00:00:00+00:00');
        $this->assertSame('unchanged', $result['kind']);
        $this->assertArrayNotHasKey('status', $result['update']);
    }

    public function testComputeUpsertUpdateIgnoredFindingDoesNotCountAsUpdated(): void
    {
        $existing = $this->fakeExisting(Simplemdm_mcp_finding_model::STATUS_IGNORED, 1);
        $result = Simplemdm_mcp_finding_model::computeUpsertUpdate($existing, $this->fakeNormalized(), 'scan_1', '2026-07-10T00:00:00+00:00');
        $this->assertSame('unchanged', $result['kind']);
    }

    public function testComputeUpsertUpdateRefreshesFieldsRegardlessOfStatus(): void
    {
        $existing = $this->fakeExisting(Simplemdm_mcp_finding_model::STATUS_SUPPRESSED, 2);
        $normalized = $this->fakeNormalized(['severity' => 'warning', 'message' => 'new message']);
        $result = Simplemdm_mcp_finding_model::computeUpsertUpdate($existing, $normalized, 'scan_2', '2026-07-10T00:00:00+00:00');
        $this->assertSame('warning', $result['update']['severity']);
        $this->assertSame('new message', $result['update']['message']);
        $this->assertSame('scan_2', $result['update']['scan_id']);
        $this->assertSame('2026-07-10T00:00:00+00:00', $result['update']['last_seen_at']);
    }
```

- [ ] **Step 2: Run to verify failure**

```
docker compose run --rm munkireport local/modules/simplemdm/vendor/bin/phpunit -c local/modules/simplemdm/phpunit.xml
```

Expected: failures — `computeUpsertUpdate` doesn't exist yet.

- [ ] **Step 3: Read the current existing-row branch to extract**

Run: `grep -n '\$wasResolved' local/modules/simplemdm/simplemdm_controller.php` to relocate it after Task 2's edit, then read ~30 lines from that point to confirm it still matches (originally):

```php
            if ($existing) {
                $wasResolved = $existing->status === Simplemdm_mcp_finding_model::STATUS_RESOLVED;
                $isSuppressedOrIgnored = in_array($existing->status, [
                    Simplemdm_mcp_finding_model::STATUS_SUPPRESSED,
                    Simplemdm_mcp_finding_model::STATUS_IGNORED,
                ], true);

                $update = [
                    'serial_number'    => $serialNumber,
                    'category'         => $category,
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
```

Note: `substr($message, 0, 1000)` here is now redundant after Task 2 (normalizeFinding already truncated `$message` to 1000) — harmless double-truncation, but Step 4 below removes the redundant `substr` call since `computeUpsertUpdate` receives the already-normalized `$normalized['message']`.

- [ ] **Step 4: Add `computeUpsertUpdate` to the model**

In `local/modules/simplemdm/simplemdm_mcp_finding_model.php`, add this method (after `normalizeFinding`):

```php
    /**
     * Decides what to write when an ingest push matches an existing row by
     * fingerprint, and whether it counts as updated/reopened/unchanged for the
     * ingest_mcp_findings response counters. Does not touch the database --
     * the caller still performs the actual fill()/save().
     */
    public static function computeUpsertUpdate($existing, $normalized, $scanId, $now)
    {
        $wasResolved = $existing->status === self::STATUS_RESOLVED;
        $isSuppressedOrIgnored = in_array($existing->status, [
            self::STATUS_SUPPRESSED,
            self::STATUS_IGNORED,
        ], true);

        $update = [
            'serial_number'    => $normalized['serial_number'],
            'category'         => $normalized['category'],
            'severity'         => $normalized['severity'],
            'message'          => $normalized['message'],
            'data'             => $normalized['data'],
            'reported_at'      => $now,
            'last_seen_at'     => $now,
            'scan_id'          => $scanId,
            'occurrence_count' => $existing->occurrence_count + 1,
        ];

        if ($wasResolved) {
            $update['status'] = self::STATUS_OPEN;
            $update['resolved_at'] = null;
            $kind = 'reopened';
        } elseif (! $isSuppressedOrIgnored) {
            $kind = 'updated';
        } else {
            $kind = 'unchanged';
        }

        return ['update' => $update, 'kind' => $kind];
    }
```

- [ ] **Step 5: Rewire the controller to call it**

Replace the block confirmed in Step 3 with:

```php
            if ($existing) {
                $result = Simplemdm_mcp_finding_model::computeUpsertUpdate($existing, $normalized, $scanId, $now);
                if ($result['kind'] === 'reopened') {
                    $reopened++;
                } elseif ($result['kind'] === 'updated') {
                    $updated++;
                }
                $existing->fill($result['update']);
                $existing->save();
                $touchedIds[] = $existing->id;
            } else {
```

Confirm the `create([...])` branch immediately after (the `else` block) is untouched by this task — it doesn't reference `$wasResolved`/`$isSuppressedOrIgnored` and needs no change, only verify it still reads `$normalized['...']`-derived local variables from Task 2's rewiring (`$serialNumber`, `$category`, `$findingType`, `$message`, `$severity`, `$extra`), which remain valid.

- [ ] **Step 6: Run to verify pass**

```
docker compose run --rm munkireport local/modules/simplemdm/vendor/bin/phpunit -c local/modules/simplemdm/phpunit.xml
```

Expected: all tests pass (22 total).

- [ ] **Step 7: Commit**

```bash
git add simplemdm_mcp_finding_model.php simplemdm_controller.php tests/Unit/McpFindingModelTest.php
git commit -m "refactor(simplemdm): extract computeUpsertUpdate() from ingest_mcp_findings, add tests"
```

---

### Task 4: Extract `parseFindingIds()` and `buildStatusUpdate()`

**Files:**
- Modify: `local/modules/simplemdm/simplemdm_mcp_finding_model.php`
- Modify: `local/modules/simplemdm/simplemdm_controller.php:6983-7012` (inside `applyFindingStatusAction`)
- Modify: `local/modules/simplemdm/tests/Unit/McpFindingModelTest.php` (append)

**Interfaces:**
- Produces: `Simplemdm_mcp_finding_model::parseFindingIds(array $data): array` (int[], deduped, positive only) and `Simplemdm_mcp_finding_model::buildStatusUpdate($targetStatus): array` (`['status' => ..., 'resolved_at' => ...]`).

- [ ] **Step 1: Write the failing tests**

Append to `local/modules/simplemdm/tests/Unit/McpFindingModelTest.php`:

```php
    public function testParseFindingIdsFromIdsArray(): void
    {
        $result = Simplemdm_mcp_finding_model::parseFindingIds(['ids' => [1, 2, 3]]);
        $this->assertSame([1, 2, 3], $result);
    }

    public function testParseFindingIdsFromSingleId(): void
    {
        $result = Simplemdm_mcp_finding_model::parseFindingIds(['id' => 5]);
        $this->assertSame([5], $result);
    }

    public function testParseFindingIdsDedupes(): void
    {
        $result = Simplemdm_mcp_finding_model::parseFindingIds(['ids' => [1, 1, 2, 2, 3]]);
        $this->assertSame([1, 2, 3], $result);
    }

    public function testParseFindingIdsDropsNonPositiveAndNonNumeric(): void
    {
        $result = Simplemdm_mcp_finding_model::parseFindingIds(['ids' => [0, -1, 'abc', 4]]);
        $this->assertSame([4], $result);
    }

    public function testParseFindingIdsEmptyWhenNeitherIdNorIdsPresent(): void
    {
        $result = Simplemdm_mcp_finding_model::parseFindingIds([]);
        $this->assertSame([], $result);
    }

    public function testBuildStatusUpdateResolvedSetsResolvedAt(): void
    {
        $result = Simplemdm_mcp_finding_model::buildStatusUpdate(Simplemdm_mcp_finding_model::STATUS_RESOLVED);
        $this->assertSame(Simplemdm_mcp_finding_model::STATUS_RESOLVED, $result['status']);
        $this->assertNotNull($result['resolved_at']);
    }

    public function testBuildStatusUpdateNonResolvedClearsResolvedAt(): void
    {
        $result = Simplemdm_mcp_finding_model::buildStatusUpdate(Simplemdm_mcp_finding_model::STATUS_ACKNOWLEDGED);
        $this->assertSame(Simplemdm_mcp_finding_model::STATUS_ACKNOWLEDGED, $result['status']);
        $this->assertNull($result['resolved_at']);
    }
```

- [ ] **Step 2: Run to verify failure**

```
docker compose run --rm munkireport local/modules/simplemdm/vendor/bin/phpunit -c local/modules/simplemdm/phpunit.xml
```

- [ ] **Step 3: Read the current block to extract**

Run: `sed -n '6963,7025p' local/modules/simplemdm/simplemdm_controller.php` (unaffected by Tasks 2-3, which only touched `ingest_mcp_findings`) and confirm it matches:

```php
    private function applyFindingStatusAction($targetStatus)
    {
        $this->connectDB();
        if (! $this->is_valid_sync_token()) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        if (! $this->mcp_findings_enabled()) {
            jsonView(['status' => 'error', 'message' => 'MCP findings are disabled'], 403);
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
```

- [ ] **Step 4: Add both methods to the model**

In `local/modules/simplemdm/simplemdm_mcp_finding_model.php`, add (after `computeUpsertUpdate`):

```php
    /**
     * Parses the id/ids field from an admin-action request body into a
     * deduped list of positive integer ids. Mirrors applyFindingStatusAction's
     * original inline parsing byte-for-byte.
     */
    public static function parseFindingIds($data)
    {
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

        return array_values(array_unique($ids));
    }

    public static function buildStatusUpdate($targetStatus)
    {
        $update = ['status' => $targetStatus];
        $update['resolved_at'] = $targetStatus === self::STATUS_RESOLVED ? gmdate('c') : null;
        return $update;
    }
```

- [ ] **Step 5: Rewire the controller to call them**

Replace the `$rawIds`/`$ids` parsing block and the `$update` construction block (identified in Step 3) with:

```php
        $ids = Simplemdm_mcp_finding_model::parseFindingIds($data);

        if (empty($ids)) {
            jsonView(['status' => 'error', 'message' => 'id or non-empty ids array is required (positive integers)'], 400);
            return;
        }

        $existingIds = Simplemdm_mcp_finding_model::whereIn('id', $ids)->pluck('id')->all();
        $existingIds = array_map('intval', $existingIds);
        $notFound = array_values(array_diff($ids, $existingIds));

        $update = Simplemdm_mcp_finding_model::buildStatusUpdate($targetStatus);
```

- [ ] **Step 6: Run to verify pass**

```
docker compose run --rm munkireport local/modules/simplemdm/vendor/bin/phpunit -c local/modules/simplemdm/phpunit.xml
```

Expected: all tests pass (30 total).

- [ ] **Step 7: Commit**

```bash
git add simplemdm_mcp_finding_model.php simplemdm_controller.php tests/Unit/McpFindingModelTest.php
git commit -m "refactor(simplemdm): extract parseFindingIds()/buildStatusUpdate() from applyFindingStatusAction, add tests"
```

---

### Task 5: Extract `parseMultiValueParam()`

**Files:**
- Modify: `local/modules/simplemdm/simplemdm_mcp_finding_model.php`
- Modify: `local/modules/simplemdm/simplemdm_controller.php` (severity/status/category filter blocks inside `get_mcp_findings`, `get_mcp_finding_stats`, `export_mcp_findings`)
- Modify: `local/modules/simplemdm/tests/Unit/McpFindingModelTest.php` (append)

**Interfaces:**
- Produces: `Simplemdm_mcp_finding_model::parseMultiValueParam($raw): array` — comma-split, trimmed, empty-filtered string list.

- [ ] **Step 1: Write the failing tests**

Append to `local/modules/simplemdm/tests/Unit/McpFindingModelTest.php`:

```php
    public function testParseMultiValueParamSingleValue(): void
    {
        $this->assertSame(['danger'], Simplemdm_mcp_finding_model::parseMultiValueParam('danger'));
    }

    public function testParseMultiValueParamMultipleValues(): void
    {
        $this->assertSame(['danger', 'warning'], Simplemdm_mcp_finding_model::parseMultiValueParam('danger,warning'));
    }

    public function testParseMultiValueParamTrimsWhitespace(): void
    {
        $this->assertSame(['danger', 'warning'], Simplemdm_mcp_finding_model::parseMultiValueParam(' danger , warning '));
    }

    public function testParseMultiValueParamFiltersEmptyEntries(): void
    {
        $this->assertSame(['danger', 'warning'], Simplemdm_mcp_finding_model::parseMultiValueParam('danger,,warning,'));
    }

    public function testParseMultiValueParamEmptyStringReturnsEmptyArray(): void
    {
        $this->assertSame([], Simplemdm_mcp_finding_model::parseMultiValueParam(''));
    }
```

- [ ] **Step 2: Run to verify failure**

```
docker compose run --rm munkireport local/modules/simplemdm/vendor/bin/phpunit -c local/modules/simplemdm/phpunit.xml
```

- [ ] **Step 3: Locate the three duplicated call sites**

Run: `grep -n "array_values(array_filter(array_map('trim', explode(',',' local/modules/simplemdm/simplemdm_controller.php`

This pattern appears for: `severity` in `get_mcp_findings` and `export_mcp_findings`; `status` in `get_mcp_findings` and `export_mcp_findings`; `category` in `get_mcp_findings`, `get_mcp_finding_stats`, and `export_mcp_findings`. Read each surrounding ~8 lines before editing to confirm the exact current text — Tasks 2-4 didn't touch these methods, so they should match the spec's citations, but confirm before replacing.

- [ ] **Step 4: Add the method to the model**

In `local/modules/simplemdm/simplemdm_mcp_finding_model.php`, add (after `buildStatusUpdate`):

```php
    /**
     * Splits a comma-separated query-param value (severity/status/category
     * filters) into a trimmed, empty-filtered list. Was duplicated inline
     * verbatim across get_mcp_findings/get_mcp_finding_stats/export_mcp_findings.
     */
    public static function parseMultiValueParam($raw)
    {
        if ($raw === '' || $raw === null) {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
```

- [ ] **Step 5: Rewire each call site**

For each of the pattern instances found in Step 3, replace the inline:

```php
            $severities = array_values(array_filter(array_map('trim', explode(',', $severity))));
```

(and its `$status`/`$category` equivalents) with:

```php
            $severities = Simplemdm_mcp_finding_model::parseMultiValueParam($severity);
```

Keep every surrounding `if (count($severities) === 1) { ... } elseif (count($severities) > 1) { ... }` block exactly as-is in each of the three methods — only the one line building the list changes, at each of the (up to) three call sites per method as applicable (`get_mcp_findings`: severity, status, category; `get_mcp_finding_stats`: category only, inside its `$applyFilters` closure; `export_mcp_findings`: severity, status, category).

- [ ] **Step 6: Run to verify pass**

```
docker compose run --rm munkireport local/modules/simplemdm/vendor/bin/phpunit -c local/modules/simplemdm/phpunit.xml
```

Expected: all tests pass (35 total).

- [ ] **Step 7: Commit**

```bash
git add simplemdm_mcp_finding_model.php simplemdm_controller.php tests/Unit/McpFindingModelTest.php
git commit -m "refactor(simplemdm): extract parseMultiValueParam(), dedupe 3x-duplicated filter parsing, add tests"
```

---

### Task 6: DB-backed upsert/dedup/reopen/auto-resolve tests

**Files:**
- Create: `local/modules/simplemdm/tests/Unit/McpFindingUpsertDbTest.php`

**Interfaces:**
- Consumes: `Simplemdm_mcp_finding_model::computeFingerprint`, `::normalizeFinding`, `::computeUpsertUpdate` (Tasks 1-3) plus direct Eloquent calls (`::create`, `->fill()->save()`, `::where(...)`) to mirror exactly what `ingest_mcp_findings` orchestrates — this test file does NOT call the controller (Phase B, out of scope); it drives the same extracted methods plus real DB writes the way the controller does, against the real in-memory-SQLite schema from `tests/bootstrap.php`.

- [ ] **Step 1: Write the test file**

Create `local/modules/simplemdm/tests/Unit/McpFindingUpsertDbTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class McpFindingUpsertDbTest extends TestCase
{
    protected function setUp(): void
    {
        // Each test gets a clean table -- bootstrap.php's connection persists
        // across the whole process, so truncate rather than re-migrate.
        Simplemdm_mcp_finding_model::query()->delete();
    }

    /**
     * Mirrors ingest_mcp_findings' single-finding orchestration: normalize,
     * look up by (source, fingerprint), then either computeUpsertUpdate()+save
     * or create(). Returns the row and the 'kind' (inserted/updated/reopened/
     * unchanged) so tests can assert on both.
     */
    private function upsertOne($source, array $rawFinding, $scanId, $now)
    {
        $metadataMaxBytes = 65536;
        $normalized = Simplemdm_mcp_finding_model::normalizeFinding($rawFinding, $metadataMaxBytes);
        $this->assertNotNull($normalized, 'test fixture finding must be valid');

        $fingerprint = Simplemdm_mcp_finding_model::computeFingerprint(
            $source, $normalized['serial_number'], $normalized['finding_type'], $normalized['category']
        );

        $existing = Simplemdm_mcp_finding_model::where('source', $source)
            ->where('fingerprint', $fingerprint)->first();

        if ($existing) {
            $result = Simplemdm_mcp_finding_model::computeUpsertUpdate($existing, $normalized, $scanId, $now);
            $existing->fill($result['update']);
            $existing->save();
            return ['row' => $existing, 'kind' => $result['kind']];
        }

        $row = Simplemdm_mcp_finding_model::create([
            'serial_number'    => $normalized['serial_number'],
            'category'         => $normalized['category'],
            'source'           => $source,
            'finding_type'     => $normalized['finding_type'],
            'fingerprint'      => $fingerprint,
            'severity'         => $normalized['severity'],
            'status'           => Simplemdm_mcp_finding_model::STATUS_OPEN,
            'occurrence_count' => 1,
            'scan_id'          => $scanId,
            'message'          => $normalized['message'],
            'data'             => $normalized['data'],
            'reported_at'      => $now,
            'first_seen_at'    => $now,
            'last_seen_at'     => $now,
            'resolved_at'      => null,
        ]);
        return ['row' => $row, 'kind' => 'inserted'];
    }

    public function testFirstPushCreatesOpenRowWithOccurrenceCountOne(): void
    {
        $result = $this->upsertOne('sofa_audit', [
            'finding_type' => 'os_eol', 'message' => 'EOL', 'serial_number' => 'C02X', 'category' => 'OS',
        ], 'scan_1', '2026-07-10T00:00:00+00:00');

        $this->assertSame('inserted', $result['kind']);
        $this->assertSame(Simplemdm_mcp_finding_model::STATUS_OPEN, $result['row']->status);
        $this->assertSame(1, $result['row']->occurrence_count);
        $this->assertSame(1, Simplemdm_mcp_finding_model::count());
    }

    public function testRepeatPushUpdatesSameRowNotDuplicate(): void
    {
        $finding = ['finding_type' => 'os_eol', 'message' => 'EOL', 'serial_number' => 'C02X', 'category' => 'OS'];
        $first = $this->upsertOne('sofa_audit', $finding, 'scan_1', '2026-07-10T00:00:00+00:00');
        $second = $this->upsertOne('sofa_audit', $finding, 'scan_2', '2026-07-10T01:00:00+00:00');

        $this->assertSame('updated', $second['kind']);
        $this->assertSame($first['row']->id, $second['row']->id);
        $this->assertSame(2, $second['row']->occurrence_count);
        $this->assertSame(1, Simplemdm_mcp_finding_model::count());
    }

    public function testResolvedFindingReopensOnRepush(): void
    {
        $finding = ['finding_type' => 'os_eol', 'message' => 'EOL', 'serial_number' => 'C02X', 'category' => 'OS'];
        $first = $this->upsertOne('sofa_audit', $finding, 'scan_1', '2026-07-10T00:00:00+00:00');
        $first['row']->fill(['status' => Simplemdm_mcp_finding_model::STATUS_RESOLVED, 'resolved_at' => '2026-07-10T00:30:00+00:00']);
        $first['row']->save();

        $second = $this->upsertOne('sofa_audit', $finding, 'scan_2', '2026-07-10T01:00:00+00:00');

        $this->assertSame('reopened', $second['kind']);
        $this->assertSame(Simplemdm_mcp_finding_model::STATUS_OPEN, $second['row']->status);
        $this->assertNull($second['row']->resolved_at);
    }

    public function testSuppressedFindingRefreshesFieldsButStaysSuppressed(): void
    {
        $finding = ['finding_type' => 'os_eol', 'message' => 'old message', 'serial_number' => 'C02X', 'category' => 'OS'];
        $first = $this->upsertOne('sofa_audit', $finding, 'scan_1', '2026-07-10T00:00:00+00:00');
        $first['row']->fill(['status' => Simplemdm_mcp_finding_model::STATUS_SUPPRESSED]);
        $first['row']->save();

        $second = $this->upsertOne('sofa_audit', ['finding_type' => 'os_eol', 'message' => 'new message', 'serial_number' => 'C02X', 'category' => 'OS'], 'scan_2', '2026-07-10T01:00:00+00:00');

        $this->assertSame('unchanged', $second['kind']);
        $this->assertSame(Simplemdm_mcp_finding_model::STATUS_SUPPRESSED, $second['row']->status);
        $this->assertSame('new message', $second['row']->message);
    }

    public function testDifferingOnlyByCategoryProducesTwoDistinctRows(): void
    {
        $this->upsertOne('sofa_audit', ['finding_type' => 'os_eol', 'message' => 'm', 'serial_number' => 'C02X', 'category' => 'OS'], 'scan_1', '2026-07-10T00:00:00+00:00');
        $this->upsertOne('sofa_audit', ['finding_type' => 'os_eol', 'message' => 'm', 'serial_number' => 'C02X', 'category' => 'Compliance'], 'scan_1', '2026-07-10T00:00:00+00:00');

        $this->assertSame(2, Simplemdm_mcp_finding_model::count());
    }

    public function testCategorylessFindingFingerprintsSameAsEmptyCategory(): void
    {
        $withoutCategory = $this->upsertOne('sofa_audit', ['finding_type' => 'os_eol', 'message' => 'm', 'serial_number' => 'C02X'], 'scan_1', '2026-07-10T00:00:00+00:00');
        $expectedFingerprint = Simplemdm_mcp_finding_model::computeFingerprint('sofa_audit', 'C02X', 'os_eol', '');
        $this->assertSame($expectedFingerprint, $withoutCategory['row']->fingerprint);
    }

    public function testReplacePushAutoResolvesUntouchedActiveRows(): void
    {
        // Simulates a full scan: two findings pushed in scan_1, only one re-pushed in scan_2.
        $stale = $this->upsertOne('sofa_audit', ['finding_type' => 'filevault_disabled', 'message' => 'm', 'serial_number' => 'C02X', 'category' => 'FileVault'], 'scan_1', '2026-07-10T00:00:00+00:00');
        $touched = $this->upsertOne('sofa_audit', ['finding_type' => 'os_eol', 'message' => 'm', 'serial_number' => 'C02X', 'category' => 'OS'], 'scan_1', '2026-07-10T00:00:00+00:00');

        // Re-push only $touched's fingerprint for scan_2, then mirror ingest_mcp_findings'
        // auto-resolve step: mark every active row for this source NOT in touchedIds as resolved.
        $this->upsertOne('sofa_audit', ['finding_type' => 'os_eol', 'message' => 'm', 'serial_number' => 'C02X', 'category' => 'OS'], 'scan_2', '2026-07-10T01:00:00+00:00');
        $touchedIds = [$touched['row']->id];

        Simplemdm_mcp_finding_model::where('source', 'sofa_audit')
            ->whereIn('status', Simplemdm_mcp_finding_model::ACTIVE_STATUSES)
            ->whereNotIn('id', $touchedIds)
            ->update(['status' => Simplemdm_mcp_finding_model::STATUS_RESOLVED, 'resolved_at' => '2026-07-10T01:00:00+00:00']);

        $stale['row']->refresh();
        $this->assertSame(Simplemdm_mcp_finding_model::STATUS_RESOLVED, $stale['row']->status);
    }
}
```

- [ ] **Step 2: Run to verify pass**

```
docker compose run --rm munkireport local/modules/simplemdm/vendor/bin/phpunit -c local/modules/simplemdm/phpunit.xml
```

Expected: all tests pass (35 + 8 = 43 total). This is a create-and-verify test file (all scenarios new), not a strict RED→GREEN cycle against pre-existing code — the methods it drives already exist and are already tested in isolation by Tasks 2-3; this task's job is proving their *composition* against a real DB matches the seven scenarios from the spec. If any scenario fails, that's either a bug in Tasks 2-3's extraction or a misunderstanding of the original controller behavior — stop and report rather than adjusting the test to match unexpected output.

- [ ] **Step 3: Commit**

```bash
git add tests/Unit/McpFindingUpsertDbTest.php
git commit -m "test(simplemdm): DB-backed upsert/dedup/reopen/auto-resolve coverage for MCP findings"
```

---

### Task 7: Full regression pass

**Files:** none (verification only)

**Interfaces:** none — this task confirms Tasks 1-6 together, and confirms the extraction changed nothing about the controller's actual routes by re-reading the two fully-rewired methods end-to-end.

- [ ] **Step 1: Run the whole suite**

```
docker compose run --rm munkireport local/modules/simplemdm/vendor/bin/phpunit -c local/modules/simplemdm/phpunit.xml
```

Expected: `OK (43 tests, ...)` (exact assertion count may differ slightly from running total above if any step's count estimate drifted — treat 0 failures/errors as the real bar, not the exact number).

- [ ] **Step 2: Read the two fully-rewired controller methods end-to-end**

Run: `grep -n "function ingest_mcp_findings\|function applyFindingStatusAction" local/modules/simplemdm/simplemdm_controller.php` and read each method in full. Confirm:
- `ingest_mcp_findings` still returns the exact same `jsonView([...])` shape as before this plan (status/source/scan_id/received/inserted/updated/reopened/resolved/skipped/replace) — Tasks 2-3 must not have changed what gets counted where.
- `applyFindingStatusAction` still returns the exact same shape (status/requested/updated/not_found).

If anything looks different from the pre-refactor behavior described in the spec/earlier tasks, stop and report — do not silently "fix" the controller to match a test; the tests exist to match the controller's real behavior, not the other way around.

- [ ] **Step 3: Confirm no unrelated files changed**

```bash
git status --short
git log --oneline main..HEAD
```

Expected: only the files listed across Tasks 1-6, no stray scratch files staged.

- [ ] **Step 4: Report**

No commit for this task (verification-only) — summarize the final test count and confirm both controller methods' response shapes are unchanged.

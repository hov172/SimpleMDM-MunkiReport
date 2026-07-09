# Findings Analytics Routes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add three read-only routes to the `simplemdm` MunkiReport module — `get_mcp_finding_stats` (severity/status/category/source breakdowns), `export_mcp_findings` (CSV/JSON bulk export), `get_mcp_scan_status` (per-source last-scan summary) — token-authenticated the same way `get_mcp_findings` already is, so the existing iOS/Android client apps can call them with zero client-side changes.

**Architecture:** One controller change inside the existing `simplemdm` module: `simplemdm_controller.php` gains three new public methods (inserted between the existing `get_mcp_findings()` and `applyFindingStatusAction()`), and all three route names are added to the existing `$token_read_actions` allowlist (the same mechanism `get_mcp_findings` already uses — NOT `$sync_actions`, which is for write routes that call `is_valid_sync_token()` internally). No new tables, no model changes, no UI changes.

**Tech Stack:** PHP 8.1, Illuminate/Database (Eloquent) with SQLite (local dev, `app/db/db.sqlite`, bind-mounted into the `munkireport-local` Docker container) and MySQL (production).

**Verification approach — deviates from strict PHPUnit TDD:** Matches every prior slice (no PHPUnit coverage for this module's controllers): every task's "test" step is a live HTTP request (curl) against the running `munkireport-local` Docker container plus a direct `sqlite3` read of `app/db/db.sqlite`.

## Global Constraints

- Design spec: `docs/superpowers/specs/2026-07-10-findings-analytics-routes-design.md` — this plan implements that spec; if any step here appears to contradict it, the spec governs and should be flagged.
- **Auth mechanism (decided after the design spec was written, supersedes any silence there): all three routes must be added to `$token_read_actions` (`simplemdm_controller.php:16`), the exact mechanism `get_mcp_findings` already uses.** Do NOT add them to `$sync_actions` (that array is for write/ingest routes with an internal `is_valid_sync_token()` call) and do NOT leave them fully public. This choice was made specifically so the existing iOS (`ReportSimpleMDM`) and Android (`ReportSimpleMDMAndroid`) client apps — which already authenticate every module read with the `X-SIMPLEMDM-API-KEY` header via this exact allowlist — can call these new routes with zero client-side changes. Since these routes are brand new, no existing app call can be broken by this choice.
- `by_status` in `get_mcp_finding_stats` is unconditional (all six statuses, always present as keys even if `0`) — mirrors `get_mcp_findings`' existing `status_totals` field precedent. `by_severity`/`by_category`/`by_source` are scoped to `ACTIVE_STATUSES` only — mirrors `get_mcp_findings`' existing `totals` field precedent. `by_category`/`by_source` are dynamic group-by breakdowns (keys are whatever distinct values exist, omitting null/empty), NOT a fixed enum like the other two.
- `export_mcp_findings` reuses `get_mcp_findings`' EXACT filter parsing logic verbatim (severity/status/source/category/since/scan_id, `status` defaults to active-only) — do not reimplement or subtly diverge from that logic.
- `export_mcp_findings` has a hard 10,000-row cap, no offset pagination. A `truncated` flag must be present when the cap is hit (JSON: top-level key; CSV: `X-Export-Truncated` response header).
- `get_mcp_scan_status` has no failure/error tracking — omit that field entirely from the response, do not stub it to `0` or `null`.
- Follow this module's existing per-value count-query convention (already used by `get_mcp_findings`' `totals`/`status_totals`) rather than introducing a new `GROUP BY`-based aggregation style — N+1 count queries at this table's current scale is an established, already-accepted pattern in this codebase.
- Do not implement `risk_score`, `device_id`, `udid`, `device_name`, ingest-failure logging, or any UI/widget changes — all explicitly out of scope per the design spec.

---

### Task 1: Controller — `get_mcp_finding_stats` + token allowlist registration

**Files:**
- Modify: `simplemdm_controller.php:16` (`$token_read_actions` array — register all three new route names here, once, for this and the following two tasks) and insert a new method after `get_mcp_findings()` (currently ends at line 6726, immediately before `private function applyFindingStatusAction($targetStatus)` at line 6728 — confirm these line numbers haven't drifted before inserting; locate by method name if they have)
- Test: manual (`curl` against the live container + `sqlite3` cross-check)

**Interfaces:**
- Consumes: `Simplemdm_mcp_finding_model::ACTIVE_STATUSES`, `$this->mcp_findings_enabled()` (both already defined from prior slices).
- Produces: `$token_read_actions` gains `'get_mcp_finding_stats'`, `'export_mcp_findings'`, `'get_mcp_scan_status'` (all three, in this one task, since Tasks 2 and 3 depend on this registration already being in place and there is no reason to touch this array three separate times). `GET get_mcp_finding_stats` route, response shape `{by_status, by_severity, by_category, by_source}`. No later task in this plan depends on this response shape.

- [ ] **Step 1: Register all three route names in `$token_read_actions`**

Change line 16 from:

```php
    private $token_read_actions = ['get_sync_telemetry', 'get_compliance_stats', 'get_command_status_stats', 'get_assignment_group_stats', 'get_resource_type_stats', 'get_os_security_stats', 'get_supplemental_status', 'get_supplemental_overview_stats', 'get_supplemental_applecare_stats', 'get_device_resources', 'get_events', 'get_dashboard_trend', 'get_supplemental_data', 'get_client_facts', 'get_runner_status', 'get_mcp_findings'];
```

to:

```php
    private $token_read_actions = ['get_sync_telemetry', 'get_compliance_stats', 'get_command_status_stats', 'get_assignment_group_stats', 'get_resource_type_stats', 'get_os_security_stats', 'get_supplemental_status', 'get_supplemental_overview_stats', 'get_supplemental_applecare_stats', 'get_device_resources', 'get_events', 'get_dashboard_trend', 'get_supplemental_data', 'get_client_facts', 'get_runner_status', 'get_mcp_findings', 'get_mcp_finding_stats', 'export_mcp_findings', 'get_mcp_scan_status'];
```

- [ ] **Step 2: Insert `get_mcp_finding_stats()`**

Insert immediately after the closing `}` of `get_mcp_findings()` (currently line 6726) and before `private function applyFindingStatusAction($targetStatus)` (currently line 6728):

```php
    /**
     * Severity/status/category/source count breakdowns for MCP findings.
     * GET /module/simplemdm/get_mcp_finding_stats?source=&category=&scan_id=&since=
     *
     * @return void
     **/
    public function get_mcp_finding_stats()
    {
        if (! $this->mcp_findings_enabled()) {
            jsonView(['status' => 'error', 'message' => 'MCP findings are disabled'], 403);
            return;
        }

        $applyFilters = function ($query) {
            $source = isset($_GET['source']) ? strtolower(trim((string) $_GET['source'])) : '';
            if ($source !== '') {
                $query->where('source', $source);
            }
            $category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
            if ($category !== '') {
                $categories = array_values(array_filter(array_map('trim', explode(',', $category))));
                if (count($categories) === 1) {
                    $query->where('category', $categories[0]);
                } elseif (count($categories) > 1) {
                    $query->whereIn('category', $categories);
                }
            }
            $scanId = isset($_GET['scan_id']) ? trim((string) $_GET['scan_id']) : '';
            if ($scanId !== '') {
                $query->where('scan_id', $scanId);
            }
            $since = isset($_GET['since']) ? trim((string) $_GET['since']) : '';
            if ($since !== '' && strtotime($since) !== false) {
                $query->where('last_seen_at', '>=', gmdate('c', strtotime($since)));
            }
            return $query;
        };

        $by_status = [];
        foreach (['open', 'acknowledged', 'in_progress', 'resolved', 'ignored', 'suppressed'] as $status) {
            $by_status[$status] = (int) $applyFilters(Simplemdm_mcp_finding_model::where('status', $status))->count();
        }

        $by_severity = [];
        foreach (['danger', 'warning', 'info'] as $severity) {
            $by_severity[$severity] = (int) $applyFilters(
                Simplemdm_mcp_finding_model::whereIn('status', Simplemdm_mcp_finding_model::ACTIVE_STATUSES)->where('severity', $severity)
            )->count();
        }

        $by_category = [];
        $categoryValues = $applyFilters(
            Simplemdm_mcp_finding_model::whereIn('status', Simplemdm_mcp_finding_model::ACTIVE_STATUSES)
        )->whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category');
        foreach ($categoryValues as $categoryValue) {
            $by_category[$categoryValue] = (int) $applyFilters(
                Simplemdm_mcp_finding_model::whereIn('status', Simplemdm_mcp_finding_model::ACTIVE_STATUSES)->where('category', $categoryValue)
            )->count();
        }

        $by_source = [];
        $sourceValues = $applyFilters(
            Simplemdm_mcp_finding_model::whereIn('status', Simplemdm_mcp_finding_model::ACTIVE_STATUSES)
        )->distinct()->pluck('source');
        foreach ($sourceValues as $sourceValue) {
            $by_source[$sourceValue] = (int) $applyFilters(
                Simplemdm_mcp_finding_model::whereIn('status', Simplemdm_mcp_finding_model::ACTIVE_STATUSES)->where('source', $sourceValue)
            )->count();
        }

        jsonView([
            'by_status'   => $by_status,
            'by_severity' => $by_severity,
            'by_category' => $by_category,
            'by_source'   => $by_source,
        ]);
    }

```

- [ ] **Step 3: Verify PHP syntax**

```bash
docker compose -f /Users/helpdesk/websites/munkireport-php/docker-compose.yml exec munkireport php -l /var/munkireport/local/modules/simplemdm/simplemdm_controller.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 4: Seed test findings and verify the route**

```bash
TOKEN="<paste your simplemdm sync token here>"
BASE="http://localhost:8888/index.php?/module/simplemdm"

curl -s -X POST "$BASE/ingest_mcp_findings" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"stats_test","replace":false,"findings":[
    {"serial_number":"C02STATS1","finding_type":"t1","severity":"danger","message":"m1","category":"FileVault"},
    {"serial_number":"C02STATS2","finding_type":"t2","severity":"warning","message":"m2","category":"FileVault"},
    {"serial_number":"C02STATS3","finding_type":"t3","severity":"info","message":"m3"}
  ]}'
```

Expected JSON: `"inserted":3`.

```bash
curl -s "$BASE/get_mcp_finding_stats?source=stats_test" -H "X-SIMPLEMDM-API-KEY: $TOKEN"
```

Expected: `by_status.open=3` (and all other status keys present at `0`), `by_severity` has `danger=1,warning=1,info=1`, `by_category` has exactly `{"FileVault":2}` (the category-less finding does NOT appear as a `""` key), `by_source` has `{"stats_test":3}`.

- [ ] **Step 5: Verify the token-auth registration actually works (this is the point of this task)**

```bash
curl -s -o /dev/null -w "%{http_code}" "$BASE/get_mcp_finding_stats" -H "X-SIMPLEMDM-API-KEY: $TOKEN"
```

Expected: `200` (not `403`/`401` — confirms the `$token_read_actions` registration from Step 1 is effective, matching how `get_mcp_findings` already behaves with just the token header, no session).

- [ ] **Step 6: Clean up test rows**

```bash
sqlite3 /Users/helpdesk/websites/munkireport-php/app/db/db.sqlite \
  "DELETE FROM simplemdm_mcp_finding WHERE source='stats_test';"
```

- [ ] **Step 7: Commit**

```bash
cd /Users/helpdesk/websites/munkireport-php
git add local/modules/simplemdm/simplemdm_controller.php
git commit -m "feat(simplemdm): add get_mcp_finding_stats route, register analytics routes for token auth"
```

---

### Task 2: Controller — `export_mcp_findings`

**Files:**
- Modify: `simplemdm_controller.php` — insert a new method immediately after `get_mcp_finding_stats()` (Task 1) and before `applyFindingStatusAction()`
- Test: manual (`curl` against the live container, including a raw CSV response check)

**Interfaces:**
- Consumes: `$token_read_actions` already includes `'export_mcp_findings'` (registered in Task 1 — do not re-register here).
- Produces: `GET export_mcp_findings?format=csv|json&...` — same filter params as `get_mcp_findings`, plus `format`. JSON response `{count, truncated, findings}`; CSV response is a raw `text/csv` download. No later task in this plan depends on this response shape.

- [ ] **Step 1: Insert `export_mcp_findings()`**

Insert immediately after `get_mcp_finding_stats()`'s closing `}` (from Task 1) and before `applyFindingStatusAction()`:

```php
    /**
     * Bulk export of MCP findings as CSV or JSON, same filters as get_mcp_findings.
     * GET /module/simplemdm/export_mcp_findings?format=csv|json&severity=&status=&source=&category=&scan_id=&since=
     *
     * @return void
     **/
    public function export_mcp_findings()
    {
        if (! $this->mcp_findings_enabled()) {
            jsonView(['status' => 'error', 'message' => 'MCP findings are disabled'], 403);
            return;
        }

        $format = isset($_GET['format']) ? strtolower(trim((string) $_GET['format'])) : 'json';
        if ($format !== 'csv' && $format !== 'json') {
            jsonView(['status' => 'error', 'message' => 'format must be csv or json'], 400);
            return;
        }

        $exportCap = 10000;
        $query = Simplemdm_mcp_finding_model::orderBy('id', 'desc')->limit($exportCap + 1);

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

        $category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
        if ($category !== '') {
            $categories = array_values(array_filter(array_map('trim', explode(',', $category))));
            if (count($categories) === 1) {
                $query->where('category', $categories[0]);
            } elseif (count($categories) > 1) {
                $query->whereIn('category', $categories);
            }
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

        $truncated = count($rows) > $exportCap;
        if ($truncated) {
            $rows = array_slice($rows, 0, $exportCap);
        }

        if ($format === 'json') {
            jsonView([
                'count'     => count($rows),
                'truncated' => $truncated,
                'findings'  => $rows,
            ]);
            return;
        }

        $columns = ['id', 'source', 'serial_number', 'finding_type', 'category', 'severity', 'status', 'message', 'data', 'scan_id', 'occurrence_count', 'reported_at', 'first_seen_at', 'last_seen_at', 'resolved_at'];
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="mcp_findings_export_' . gmdate('Ymd\THis\Z') . '.csv"');
        if ($truncated) {
            header('X-Export-Truncated: true');
        }
        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);
        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $col) {
                $line[] = isset($row[$col]) ? $row[$col] : '';
            }
            fputcsv($out, $line);
        }
        fclose($out);
        exit;
    }

```

- [ ] **Step 2: Verify PHP syntax**

```bash
docker compose -f /Users/helpdesk/websites/munkireport-php/docker-compose.yml exec munkireport php -l /var/munkireport/local/modules/simplemdm/simplemdm_controller.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Seed test findings and verify JSON export matches `get_mcp_findings`' shape**

```bash
TOKEN="<paste your simplemdm sync token here>"
BASE="http://localhost:8888/index.php?/module/simplemdm"

curl -s -X POST "$BASE/ingest_mcp_findings" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"export_test","replace":false,"findings":[
    {"serial_number":"C02EXPORT1","finding_type":"t1","severity":"danger","message":"m1","category":"FileVault"},
    {"serial_number":"C02EXPORT2","finding_type":"t2","severity":"warning","message":"m2"}
  ]}'
```

Expected JSON: `"inserted":2`.

```bash
curl -s "$BASE/export_mcp_findings?source=export_test&format=json" -H "X-SIMPLEMDM-API-KEY: $TOKEN"
```

Expected: `"count":2`, `"truncated":false`, `"findings"` array with 2 rows, each row having the same fields `get_mcp_findings` already returns (including `category`).

- [ ] **Step 4: Verify CSV export**

```bash
curl -s -D - "$BASE/export_mcp_findings?source=export_test&format=csv" -H "X-SIMPLEMDM-API-KEY: $TOKEN" -o /tmp/export_test.csv
```

Expected headers include `Content-Type: text/csv` and `Content-Disposition: attachment; filename="mcp_findings_export_...csv"`.

```bash
cat /tmp/export_test.csv
```

Expected: a header row (`id,source,serial_number,finding_type,category,severity,status,message,data,scan_id,occurrence_count,reported_at,first_seen_at,last_seen_at,resolved_at`) followed by 2 data rows, one with `category=FileVault`, one with an empty category field.

- [ ] **Step 5: Verify `format` validation**

```bash
curl -s "$BASE/export_mcp_findings?format=xml" -H "X-SIMPLEMDM-API-KEY: $TOKEN"
```

Expected: HTTP 400, `{"status":"error","message":"format must be csv or json"}`.

- [ ] **Step 6: Clean up test rows and temp file**

```bash
sqlite3 /Users/helpdesk/websites/munkireport-php/app/db/db.sqlite \
  "DELETE FROM simplemdm_mcp_finding WHERE source='export_test';"
rm -f /tmp/export_test.csv
```

- [ ] **Step 7: Commit**

```bash
cd /Users/helpdesk/websites/munkireport-php
git add local/modules/simplemdm/simplemdm_controller.php
git commit -m "feat(simplemdm): add export_mcp_findings route (CSV/JSON bulk export)"
```

---

### Task 3: Controller — `get_mcp_scan_status`

**Files:**
- Modify: `simplemdm_controller.php` — insert a new method immediately after `export_mcp_findings()` (Task 2) and before `applyFindingStatusAction()`
- Test: manual (`curl` against the live container + `sqlite3` cross-check, including a two-scan sequence)

**Interfaces:**
- Consumes: `$token_read_actions` already includes `'get_mcp_scan_status'` (registered in Task 1 — do not re-register here).
- Produces: `GET get_mcp_scan_status?source=` — response shape `{sources: [{source, last_scan_id, last_ingest_at, counts: {danger, warning, info, total}}]}`. This is the last route in this plan.

- [ ] **Step 1: Insert `get_mcp_scan_status()`**

Insert immediately after `export_mcp_findings()`'s closing `}` (from Task 2) and before `applyFindingStatusAction()`:

```php
    /**
     * Per-source last-scan summary for MCP findings.
     * GET /module/simplemdm/get_mcp_scan_status?source=
     *
     * @return void
     **/
    public function get_mcp_scan_status()
    {
        if (! $this->mcp_findings_enabled()) {
            jsonView(['status' => 'error', 'message' => 'MCP findings are disabled'], 403);
            return;
        }

        $sourceFilter = isset($_GET['source']) ? strtolower(trim((string) $_GET['source'])) : '';

        $sourcesQuery = Simplemdm_mcp_finding_model::query();
        if ($sourceFilter !== '') {
            $sourcesQuery->where('source', $sourceFilter);
        }
        $sources = $sourcesQuery->distinct()->pluck('source');

        $result = [];
        foreach ($sources as $source) {
            $last = Simplemdm_mcp_finding_model::where('source', $source)
                ->orderBy('reported_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            if (! $last) {
                continue;
            }
            $lastScanId = $last->scan_id;

            $counts = [
                'danger'  => (int) Simplemdm_mcp_finding_model::where('source', $source)->where('scan_id', $lastScanId)->where('severity', 'danger')->count(),
                'warning' => (int) Simplemdm_mcp_finding_model::where('source', $source)->where('scan_id', $lastScanId)->where('severity', 'warning')->count(),
                'info'    => (int) Simplemdm_mcp_finding_model::where('source', $source)->where('scan_id', $lastScanId)->where('severity', 'info')->count(),
            ];
            $counts['total'] = $counts['danger'] + $counts['warning'] + $counts['info'];

            $result[] = [
                'source'         => $source,
                'last_scan_id'   => $lastScanId,
                'last_ingest_at' => $last->reported_at,
                'counts'         => $counts,
            ];
        }

        jsonView(['sources' => $result]);
    }

```

- [ ] **Step 2: Verify PHP syntax**

```bash
docker compose -f /Users/helpdesk/websites/munkireport-php/docker-compose.yml exec munkireport php -l /var/munkireport/local/modules/simplemdm/simplemdm_controller.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Verify a two-scan sequence shows only the newest scan's counts**

```bash
TOKEN="<paste your simplemdm sync token here>"
BASE="http://localhost:8888/index.php?/module/simplemdm"

curl -s -X POST "$BASE/ingest_mcp_findings" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"scan_status_test","scan_id":"scan_first","replace":true,"findings":[
    {"serial_number":"C02SCAN1","finding_type":"t1","severity":"danger","message":"m1"},
    {"serial_number":"C02SCAN2","finding_type":"t2","severity":"warning","message":"m2"}
  ]}'
```

Expected JSON: `"inserted":2`.

```bash
curl -s "$BASE/get_mcp_scan_status?source=scan_status_test" -H "X-SIMPLEMDM-API-KEY: $TOKEN"
```

Expected: one entry, `last_scan_id="scan_first"`, `counts.total=2` (`danger=1,warning=1`).

```bash
curl -s -X POST "$BASE/ingest_mcp_findings" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"scan_status_test","scan_id":"scan_second","replace":true,"findings":[
    {"serial_number":"C02SCAN3","finding_type":"t3","severity":"info","message":"m3"}
  ]}'
```

Expected JSON: `"inserted":1,"resolved":2` (the two `scan_first` findings auto-resolve since this is a `replace:true` push that omits them).

```bash
curl -s "$BASE/get_mcp_scan_status?source=scan_status_test" -H "X-SIMPLEMDM-API-KEY: $TOKEN"
```

Expected: `last_scan_id="scan_second"`, `counts.total=1` (`info=1`, `danger=0`, `warning=0`) — NOT the union of both scans (confirms scoping to only the most recent `scan_id`, not all-time counts for the source).

- [ ] **Step 4: Clean up test rows**

```bash
sqlite3 /Users/helpdesk/websites/munkireport-php/app/db/db.sqlite \
  "DELETE FROM simplemdm_mcp_finding WHERE source='scan_status_test';"
```

- [ ] **Step 5: Commit**

```bash
cd /Users/helpdesk/websites/munkireport-php
git add local/modules/simplemdm/simplemdm_controller.php
git commit -m "feat(simplemdm): add get_mcp_scan_status route (per-source last-scan summary)"
```

---

### Task 4: Documentation update

**Files:**
- Modify: `docs/API_REFERENCE.md` (new subsections for all three routes)
- Modify: `CHANGELOG.md` (add new entry)
- Modify: `README.md` (mention the new routes are token-readable, matching `get_mcp_findings`)

**Interfaces:**
- Consumes: nothing (docs only).
- Produces: nothing consumed by later tasks — this is the last task in this plan.

- [ ] **Step 1: Add sections to `docs/API_REFERENCE.md`**

Document all three routes (request params, response shape, auth) in the same style as the existing MCP Findings sections. Explicitly note: all three are in `$token_read_actions` (same auth as `get_mcp_findings` — `X-SIMPLEMDM-API-KEY` header, no session required), matching the existing iOS/Android client apps' auth model.

For `get_mcp_finding_stats`: document the four breakdowns, the fixed-vs-dynamic key distinction (`by_status`/`by_severity` always have all their keys present even at `0`; `by_category`/`by_source` only include keys that have at least one matching finding), and the four optional filters.

For `export_mcp_findings`: document `format=csv|json`, the same six filters as `get_mcp_findings`, the 10,000-row hard cap with no offset pagination, and the `truncated` flag's two representations (JSON key vs. `X-Export-Truncated` header for CSV).

For `get_mcp_scan_status`: document the per-source grouping, that `counts` reflects ONLY the most recent `scan_id` for that source (not all-time totals), the optional `source` filter, and explicitly that there is no failure-tracking field (nothing in this codebase logs failed ingest attempts).

- [ ] **Step 2: Add a `CHANGELOG.md` entry**

Add to the top of the `## [Unreleased]` section:

```markdown
### Added
- Three read-only findings analytics routes: `get_mcp_finding_stats` (severity/status/category/source count breakdowns), `export_mcp_findings` (CSV/JSON bulk export, 10,000-row cap), `get_mcp_scan_status` (per-source last-scan summary). All three are token-readable via `X-SIMPLEMDM-API-KEY`, the same mechanism `get_mcp_findings` already uses — no changes needed for existing client apps.
```

- [ ] **Step 3: Update `README.md`**

Find the paragraph mentioning `get_mcp_findings`'/admin settings' token auth (search for "token-readable" or "X-SIMPLEMDM-API-KEY" near the MCP findings section) and add one sentence noting the three new analytics routes use the same auth model.

- [ ] **Step 4: Commit**

```bash
cd /Users/helpdesk/websites/munkireport-php
git add local/modules/simplemdm/README.md local/modules/simplemdm/CHANGELOG.md local/modules/simplemdm/docs/API_REFERENCE.md
git commit -m "docs(simplemdm): document findings analytics routes"
```

---

## Explicitly Out of Scope for This Slice

- Ingest-attempt/failure logging — `get_mcp_scan_status` omits "failures" entirely.
- `risk_score`, `device_id`, `udid`, `device_name` — still deferred.
- Any UI/widget changes.
- Any write/admin-action routes.

## Self-Review Notes

- **Spec coverage check:** Design spec's `get_mcp_finding_stats` section (four breakdowns, fixed-vs-dynamic keys, filters) → Task 1, verified in Step 4. `export_mcp_findings` section (same filters as `get_mcp_findings`, format param, 10k cap, truncated flag, CSV headers) → Task 2, verified in Steps 3-5. `get_mcp_scan_status` section (per-source grouping, last-scan-only counts, no failures field) → Task 3, verified in Step 3's two-scan sequence. Auth decision (token_read_actions, not sync_actions, not public) → Task 1 Step 1, verified in Step 5 for the first route and implicitly covered for the other two since all three share the one registration. All design sections plus the post-design auth decision have a corresponding task step.
- **Backward compatibility check:** no existing route, model, or migration is modified — this plan only adds new methods to `simplemdm_controller.php`, extends the `$token_read_actions` array (additive, does not remove or change any existing entry), and adds documentation. `get_mcp_findings`, `ingest_mcp_findings`, and the four admin-action routes (all prior slices) are untouched.

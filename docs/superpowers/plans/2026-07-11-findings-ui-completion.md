# Findings UI Completion (PRD §13/§14) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the four remaining PRD gaps — device-page findings section (§14.2), findings list page (§14.3), Events summary integration (§13), and the Phase-2/3 analytics widgets (§14.1) — entirely inside the SimpleMDM module.

**Architecture:** Everything is module-local: controller route/auth additions in `simplemdm_controller.php`, pure static helpers on `simplemdm_mcp_finding_model.php` (PHPUnit-tested), and view files following the module's existing widget/section conventions. The four finding admin-action routes gain global-admin-session auth (in addition to sync token) so browser UIs can drive the lifecycle. No new tables, no migrations, no MunkiReport-core changes.

**Tech Stack:** PHP 7+ (MunkiReport module conventions: Eloquent-style models, `jsonView`, `View`), jQuery + NVD3 (already loaded by MunkiReport), PHPUnit 10 (in-memory SQLite + real migrations, existing harness in `tests/`).

## Global Constraints

- **No MunkiReport-core changes.** Only files under `local/modules/simplemdm/` may be created or modified.
- **No schema changes / no new migrations.** Severity stays the 3-value model (`danger`/`warning`/`info`); rich fields stay inside the `data` JSON blob.
- **Do not add `overscroll-behavior` to any scrollable widget container** (Safari sub-scroller freeze — see `docs/DEVELOPER_GUIDE.md` postmortem). Any new scrollable container must be bound through `bindWheelScroll` in `views/simplemdm_widget_modern_assets.php`.
- **Never dispatch a synthetic `window` resize unconditionally** from any code path a resize listener re-schedules (feedback-loop postmortem, same doc).
- Auth tiers (from `docs/SECURITY.md`): sync token = `X-SIMPLEMDM-API-KEY` header via `is_valid_sync_token()`; admin session = `$this->authorized('global')`. New pages use session auth (automatic for public controller methods not in `$sync_actions`/`$token_read_actions`).
- Run `vendor/bin/phpunit` from the module root for every model-helper change; verify view changes in the live Docker container at `http://localhost:8888` via the `/browse` gstack skill (no JS test framework exists for views).
- Commit after every task with the module's conventional prefix, e.g. `feat(simplemdm): ...`, trailer `Co-Authored-By:` per repo convention.
- Each slice (A–E) is independently shippable; later slices may depend on earlier ones but not vice versa.

---

## Slice A — Foundation: session auth for finding actions + `finding_type` filter

### Task 1: Global-admin-session auth on the four finding admin-action routes

**Files:**
- Modify: `simplemdm_controller.php` (method `applyFindingStatusAction`, ~line 6929)
- Modify: `docs/SECURITY.md` (auth matrix rows for the four routes)
- Modify: `docs/API_REFERENCE.md` (§12 Authentication paragraph)

**Interfaces:**
- Consumes: existing `is_valid_sync_token()`, `authorized('global')`.
- Produces: `acknowledge/resolve/ignore/suppress_mcp_finding` callable from a browser session held by a global admin (used by Tasks 4, 6). Request/response shape unchanged: POST JSON `{"id": N}` or `{"ids": [N,...]}` → `{"status":"success","requested":N,"updated":N,"not_found":[...]}`.

- [ ] **Step 1: Change the auth gate**

In `applyFindingStatusAction`, replace:

```php
        if (! $this->is_valid_sync_token()) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }
```

with:

```php
        // Sync token (the MCP publisher) OR a global-admin session (browser
        // UIs: device page, findings list page). Widening to admin session is
        // strictly additive; unlike the old save_config bug this cannot
        // let a lesser session skip a scope check -- authorized('global')
        // IS the scope check.
        if (! $this->is_valid_sync_token() && ! $this->authorized('global')) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }
```

Note: these four routes stay in `$sync_actions` (line 11) so token calls keep working; session calls arrive via the normal authenticated-route path.

- [ ] **Step 2: Negative + positive test via curl (no session → still 401 without token; token → still works)**

```bash
curl -s -o /dev/null -w '%{http_code}\n' -X POST "http://localhost:8888/module/simplemdm/acknowledge_mcp_finding" -H "Content-Type: application/json" -d '{"ids":[999999]}'
```
Expected: `401`

```bash
KEY=$(python3 -c "import sqlite3;print(sqlite3.connect('/Users/helpdesk/websites/munkireport-php/app/db/db.sqlite').execute(\"SELECT value FROM simplemdm_config WHERE name='api_key'\").fetchone()[0])")
curl -s -X POST "http://localhost:8888/module/simplemdm/acknowledge_mcp_finding" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $KEY" -d '{"ids":[999999]}'
```
Expected: `{"status":"success","requested":1,"updated":0,"not_found":[999999]}`

- [ ] **Step 3: Update docs**

`docs/SECURITY.md` — change the auth-matrix row for the four action routes from `Sync auth required` to `Sync auth OR global-admin session`, and append to the write-path #6 entry: `As of <today's date>, the four admin-action routes also accept a global-admin session, so the module's own device page and findings list page can drive the lifecycle without exposing the sync token to the browser.`

`docs/API_REFERENCE.md` §12 "Authentication" — change to: `Same sync-token model as ingest_mcp_findings (X-SIMPLEMDM-API-KEY header), or a global-admin MunkiReport session. Browser UIs use the session path; the sync token is never exposed to page JavaScript.`

- [ ] **Step 4: Commit**

```bash
git add simplemdm_controller.php docs/SECURITY.md docs/API_REFERENCE.md
git commit -m "feat(simplemdm): allow global-admin session on finding admin-action routes"
```

### Task 2: `finding_type` filter on `get_mcp_findings` + stats

**Files:**
- Modify: `simplemdm_controller.php` (`get_mcp_findings`, ~line 6597; `get_mcp_finding_stats`, ~line 6700 — the `$applyFilters` closure)
- Modify: `docs/API_REFERENCE.md` (filter tables for both routes)
- Test: `tests/Unit/McpFindingModelTest.php` (already covers `parseMultiValueParam`; no new model code needed — this task is controller wiring, verified via curl)

**Interfaces:**
- Consumes: `Simplemdm_mcp_finding_model::parseMultiValueParam($raw)` (existing static, returns `string[]`).
- Produces: `GET get_mcp_findings?finding_type=a,b` and `get_mcp_finding_stats?finding_type=a,b` filter rows by exact `finding_type` match (comma-separated, case-sensitive — same semantics as the existing `category` filter). Used by Task 6's deep links and Task 12's widget.

- [ ] **Step 1: Add the filter to `get_mcp_findings`**

Directly after the existing `category` filter block inside `get_mcp_findings` (pattern-match the category block at ~line 6620), add:

```php
        $findingType = isset($_GET['finding_type']) ? trim((string) $_GET['finding_type']) : '';
        if ($findingType !== '') {
            $types = Simplemdm_mcp_finding_model::parseMultiValueParam($findingType);
            if (count($types) === 1) {
                $query->where('finding_type', $types[0]);
            } elseif (count($types) > 1) {
                $query->whereIn('finding_type', $types);
            }
        }
```

- [ ] **Step 2: Add the same block to the `$applyFilters` closure in `get_mcp_finding_stats`**

Inside the closure (after its `category` block), using `$query` exactly as the closure's other filters do:

```php
            $findingType = isset($_GET['finding_type']) ? trim((string) $_GET['finding_type']) : '';
            if ($findingType !== '') {
                $types = Simplemdm_mcp_finding_model::parseMultiValueParam($findingType);
                if (count($types) === 1) {
                    $query->where('finding_type', $types[0]);
                } elseif (count($types) > 1) {
                    $query->whereIn('finding_type', $types);
                }
            }
```

- [ ] **Step 3: Verify via curl**

```bash
KEY=$(python3 -c "import sqlite3;print(sqlite3.connect('/Users/helpdesk/websites/munkireport-php/app/db/db.sqlite').execute(\"SELECT value FROM simplemdm_config WHERE name='api_key'\").fetchone()[0])")
curl -s -H "X-SIMPLEMDM-API-KEY: $KEY" "http://localhost:8888/module/simplemdm/get_mcp_findings?finding_type=stale_device&limit=2" | python3 -m json.tool | head -20
```
Expected: only `"finding_type": "stale_device"` rows.

```bash
curl -s -H "X-SIMPLEMDM-API-KEY: $KEY" "http://localhost:8888/module/simplemdm/get_mcp_findings?finding_type=does_not_exist&limit=2" | python3 -c "import sys,json; print(len(json.load(sys.stdin)['findings']))"
```
Expected: `0`

- [ ] **Step 4: Run the existing suite (guard against regressions)**

Run: `vendor/bin/phpunit`
Expected: all tests pass.

- [ ] **Step 5: Update `docs/API_REFERENCE.md`** — add `finding_type` to both routes' query-parameter tables: `comma-separated exact match, case-sensitive, same semantics as category`.

- [ ] **Step 6: Commit**

```bash
git add simplemdm_controller.php docs/API_REFERENCE.md
git commit -m "feat(simplemdm): finding_type filter on get_mcp_findings and stats"
```

---

## Slice B — Device page findings section (PRD §14.2)

### Task 3: Pass admin flag into the device view

**Files:**
- Modify: `simplemdm_controller.php` (`device()`, line 3595)

**Interfaces:**
- Produces: the view variable `$is_global_admin` (bool) inside `views/simplemdm_device.php`, and a JS global `window.simplemdmIsGlobalAdmin` for Task 4's conditional action buttons.

- [ ] **Step 1: Modify `device()`**

```php
    public function device($serial_number = '')
    {
        $obj = new View();
        $obj->view('simplemdm_device', [
            'serial_number'   => $serial_number,
            'is_global_admin' => (bool) $this->authorized('global'),
        ], $this->module_path . '/views/');
    }
```

- [ ] **Step 2: Expose to JS in `views/simplemdm_device.php`**

Near the top of the view's existing `<script>` block (it already reads `$serial_number`), add:

```php
window.simplemdmIsGlobalAdmin = <?php echo !empty($is_global_admin) ? 'true' : 'false'; ?>;
```

- [ ] **Step 3: Verify + commit**

Load `http://localhost:8888/module/simplemdm/device/ANYSERIAL` in the browse skill; `$B js "window.simplemdmIsGlobalAdmin"` → `true` (local Docker session is admin).

```bash
git add simplemdm_controller.php views/simplemdm_device.php
git commit -m "feat(simplemdm): expose global-admin flag to device page JS"
```

### Task 4: MCP Findings section on the device page

**Files:**
- Modify: `views/simplemdm_device.php` (new findings section, rendered by the page's existing section machinery)
- Modify: `docs/DEVELOPER_GUIDE.md` (device page bullet list gains the findings section)
- Modify: `docs/TESTING.md` (§9 device-detail checklist gains "MCP findings section")

**Interfaces:**
- Consumes: `GET get_mcp_findings/{serial}?status=<csv>&limit=200` (session auth — the page itself is session-gated); Task 1's session-auth'd action routes; the page's existing `createSectionHtml(id, title, bodyHtml, expanded)` helper (line ~540) and its `[data-section-toggle]` click handler (line ~819); `esc()` from the same file.
- Produces: a `#simplemdm-section-mcp-findings` section; hidden entirely when the serial has no findings (PRD: "when findings exist").

- [ ] **Step 1: Add the loader + renderer to the view's script**

Add after the existing section-rendering code (keep the file's `var`/`function` style). The section shows active findings by default with a "Show resolved/ignored" toggle re-fetching with all statuses, one row per finding with severity+status badges, type, category, message, source, `first_seen`/`last_seen`/`occurrence_count`/`resolved_at`, `data` behind a `<details>` disclosure, and per-finding action buttons for admins:

```javascript
function simplemdmRenderDeviceFindings(includeClosed) {
    var statuses = includeClosed
        ? 'open,acknowledged,in_progress,resolved,ignored,suppressed'
        : 'open,acknowledged,in_progress';
    $.getJSON(window.simplemdmModuleUrl('get_mcp_findings') + '/' + encodeURIComponent(serialNumber) + '?limit=200&status=' + statuses, function(data) {
        var findings = (data && data.findings) ? data.findings : [];
        var $existing = $('[data-section-id="mcp-findings"]');
        if (!findings.length && !includeClosed) {
            $existing.remove();
            return; // PRD 14.2: section only appears when findings exist
        }
        var rows = findings.map(function(f) {
            var sev = String(f.severity || 'info').toLowerCase();
            if (sev !== 'danger' && sev !== 'warning') { sev = 'info'; }
            var meta = [
                'first seen ' + esc(String(f.first_seen_at || '').slice(0, 10)),
                'last seen ' + esc(String(f.last_seen_at || '').slice(0, 10)),
                'seen ' + esc(f.occurrence_count || 1) + 'x'
            ];
            if (f.resolved_at) { meta.push('resolved ' + esc(String(f.resolved_at).slice(0, 10))); }
            var actions = '';
            if (window.simplemdmIsGlobalAdmin && f.status !== 'resolved') {
                actions = '<span class="simplemdm-finding-actions">' +
                    ['acknowledge', 'resolve', 'ignore', 'suppress'].map(function(a) {
                        return '<button type="button" class="btn btn-xs btn-default" data-finding-action="' + a + '" data-finding-id="' + Number(f.id) + '">' + a + '</button>';
                    }).join(' ') + '</span>';
            }
            var dataBlock = f.data
                ? '<details><summary class="text-muted">details</summary><pre class="simplemdm-finding-data">' + esc(String(f.data)) + '</pre></details>'
                : '';
            return '<div class="simplemdm-kv-row" data-finding-row="' + Number(f.id) + '">' +
                '<span class="badge alert-' + sev + '">' + esc(sev) + '</span> ' +
                '<span class="badge">' + esc(f.status || 'open') + '</span> ' +
                '<strong>' + esc(f.finding_type || '-') + '</strong>' +
                (f.category ? ' <span class="text-muted">[' + esc(f.category) + ']</span>' : '') +
                '<div>' + esc(f.message || '') + '</div>' +
                '<div class="text-muted" style="font-size:11px">' + esc(f.source || '') + ' &middot; ' + meta.join(' &middot; ') + '</div>' +
                dataBlock + actions +
            '</div>';
        }).join('');
        var toggleLabel = includeClosed ? 'Hide resolved/ignored' : 'Show resolved/ignored';
        var body = '<div><button type="button" class="btn btn-xs btn-default" id="simplemdm-findings-closed-toggle" data-include-closed="' + (includeClosed ? '1' : '0') + '">' + toggleLabel + '</button></div>' + rows;
        var html = createSectionHtml('mcp-findings', 'MCP Findings (' + findings.length + ')', body, true);
        if ($existing.length) { $existing.replaceWith(html); } else { $('[data-section-id]').last().after(html); }
    });
}
simplemdmRenderDeviceFindings(false);

$(document).on('click', '#simplemdm-findings-closed-toggle', function() {
    simplemdmRenderDeviceFindings($(this).attr('data-include-closed') !== '1');
});

$(document).on('click', '[data-finding-action]', function() {
    var action = String($(this).attr('data-finding-action'));
    var id = Number($(this).attr('data-finding-id'));
    if (['acknowledge', 'resolve', 'ignore', 'suppress'].indexOf(action) === -1 || !id) { return; }
    var $btn = $(this).prop('disabled', true);
    $.ajax({
        url: window.simplemdmModuleUrl(action + '_mcp_finding'),
        method: 'POST', contentType: 'application/json',
        data: JSON.stringify({ id: id })
    }).done(function() {
        var closed = $('#simplemdm-findings-closed-toggle').attr('data-include-closed') === '1';
        simplemdmRenderDeviceFindings(closed);
    }).fail(function() {
        $btn.prop('disabled', false).text(action + ' failed');
    });
});
```

Also add to the view's `<style>` block:

```css
.simplemdm-finding-data { max-height: 160px; overflow-y: auto; font-size: 11px; margin: 4px 0 0; }
.simplemdm-finding-actions { display: block; margin-top: 4px; }
```

Note: `serialNumber` is the variable the view already defines from `$serial_number` — reuse whatever exact name the file uses (check the top of the script; adjust if it is `serial_number`).

- [ ] **Step 2: Verify in browse against a serial that has findings**

Pick a serial from the live data: `python3 -c "import sqlite3;print(sqlite3.connect('/Users/helpdesk/websites/munkireport-php/app/db/db.sqlite').execute(\"SELECT serial_number FROM simplemdm_mcp_finding WHERE status IN ('open','acknowledged','in_progress') LIMIT 1\").fetchone()[0])"`.

- Section renders with count, badges, disclosure, buttons.
- Click `acknowledge` on one finding → row re-renders with `acknowledged` status badge.
- Set it back: click `resolve`, toggle "Show resolved/ignored", confirm it appears with resolved badge; then re-open it via curl ingest or leave (data-only).
- A serial with zero findings (e.g. a fake serial) renders NO section.
- No console errors.

- [ ] **Step 3: Update docs** — DEVELOPER_GUIDE device-page bullet (add "MCP findings section with admin lifecycle controls"); TESTING §9 device-detail sections list (add `- MCP findings section (badges, disclosure, admin actions, hidden when empty)`).

- [ ] **Step 4: Commit**

```bash
git add views/simplemdm_device.php docs/DEVELOPER_GUIDE.md docs/TESTING.md
git commit -m "feat(simplemdm): MCP findings section with lifecycle controls on device page"
```

---

## Slice C — Findings list page (PRD §14.3)

### Task 5: `findings` page route

**Files:**
- Modify: `simplemdm_controller.php` (new public method next to `device()`)
- Create: `views/simplemdm_findings_page.php` (skeleton this task; full UI Task 6)

**Interfaces:**
- Produces: `GET /module/simplemdm/findings` renders the view with `$is_global_admin`; session-authenticated automatically (not added to `$sync_actions`/`$token_read_actions`).

- [ ] **Step 1: Add the route**

```php
    /**
     * Full-page MCP findings browser (PRD 14.3).
     *
     * @return void
     **/
    public function findings()
    {
        $obj = new View();
        $obj->view('simplemdm_findings_page', [
            'is_global_admin' => (bool) $this->authorized('global'),
        ], $this->module_path . '/views/');
    }
```

- [ ] **Step 2: Skeleton view** (`views/simplemdm_findings_page.php`):

```php
<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>
<div class="container">
    <div class="row"><div class="col-lg-12">
        <h3><i class="fa fa-flag"></i> MCP Findings</h3>
        <div id="simplemdm-findings-page" data-admin="<?php echo !empty($is_global_admin) ? '1' : '0'; ?>">Loading...</div>
    </div></div>
</div>
```

- [ ] **Step 3: Verify + commit** — browse to `/module/simplemdm/findings`, expect the heading; `curl -s -o /dev/null -w '%{http_code}' http://localhost:8888/module/simplemdm/findings` without session cookies → redirect/403 (not 200 with content).

```bash
git add simplemdm_controller.php views/simplemdm_findings_page.php
git commit -m "feat(simplemdm): findings page route + skeleton view"
```

### Task 6: Findings list UI — filters, pagination, bulk actions, export

**Files:**
- Modify: `views/simplemdm_findings_page.php`
- Modify: `views/simplemdm_mcp_findings_widget.php` (point "+N more" notes and the truncation note at the new page)
- Modify: `README.md` (widget section + a one-line pointer), `docs/DEVELOPER_GUIDE.md` (UI map), `docs/TESTING.md` (new QA block)

**Interfaces:**
- Consumes: `get_mcp_findings` (`status`, `severity`, `category`, `source`, `finding_type` [Task 2], `limit`, `offset`, `status_totals`), `get_mcp_finding_stats` (`by_category`, `by_source` for filter dropdowns), Task 1 batch actions (`{"ids":[...]}`), `export_mcp_findings?format=csv|json` + same filters.
- Produces: page reads `?status=&severity=&category=&finding_type=&source=` from its URL (deep-link target for the widget).

- [ ] **Step 1: Implement the page** (replace the `Loading...` div content via JS in the same view file):

```php
<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>
<style>
#simplemdm-findings-page .simplemdm-findings-toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0; align-items: center; }
#simplemdm-findings-page select, #simplemdm-findings-page input[type="text"] { max-width: 180px; }
#simplemdm-findings-page table { width: 100%; }
#simplemdm-findings-page td, #simplemdm-findings-page th { padding: 6px 8px; border-bottom: 1px solid var(--simplemdm-border); vertical-align: top; }
#simplemdm-findings-page .simplemdm-findings-pager { margin: 12px 0; display: flex; gap: 8px; align-items: center; }
</style>
<div class="container">
    <div class="row"><div class="col-lg-12">
        <h3><i class="fa fa-flag"></i> MCP Findings</h3>
        <div id="simplemdm-findings-page" data-admin="<?php echo !empty($is_global_admin) ? '1' : '0'; ?>">
            <div class="simplemdm-findings-toolbar">
                <select id="f-status" multiple size="3">
                    <option value="open" selected>open</option><option value="acknowledged" selected>acknowledged</option><option value="in_progress" selected>in_progress</option>
                    <option value="resolved">resolved</option><option value="ignored">ignored</option><option value="suppressed">suppressed</option>
                </select>
                <select id="f-severity"><option value="">any severity</option><option>danger</option><option>warning</option><option>info</option></select>
                <select id="f-category"><option value="">any category</option></select>
                <select id="f-source"><option value="">any source</option></select>
                <input type="text" id="f-type" placeholder="finding_type (comma-sep)">
                <button class="btn btn-xs btn-primary" id="f-apply">Apply</button>
                <span style="flex:1"></span>
                <a class="btn btn-xs btn-default" id="f-export-csv" href="#">Export CSV</a>
                <a class="btn btn-xs btn-default" id="f-export-json" href="#">Export JSON</a>
            </div>
            <div id="f-bulkbar" style="display:none; margin-bottom:8px;">
                <span id="f-selcount"></span>
                <button class="btn btn-xs btn-default" data-bulk="acknowledge">Acknowledge</button>
                <button class="btn btn-xs btn-default" data-bulk="resolve">Resolve</button>
                <button class="btn btn-xs btn-default" data-bulk="ignore">Ignore</button>
                <button class="btn btn-xs btn-default" data-bulk="suppress">Suppress</button>
            </div>
            <table id="f-table"><thead><tr>
                <th><input type="checkbox" id="f-selall"></th>
                <th>Severity</th><th>Status</th><th>Type</th><th>Category</th><th>Serial</th><th>Message</th><th>Source</th><th>Last seen</th>
            </tr></thead><tbody></tbody></table>
            <div class="simplemdm-findings-pager">
                <button class="btn btn-xs btn-default" id="f-prev">&laquo; Prev</button>
                <span id="f-pageinfo"></span>
                <button class="btn btn-xs btn-default" id="f-next">Next &raquo;</button>
            </div>
        </div>
    </div></div>
</div>
<script>
$(document).on('appReady', function() {
    var pageSize = 50, offset = 0, isAdmin = $('#simplemdm-findings-page').attr('data-admin') === '1';
    if (!isAdmin) { $('#f-bulkbar, #f-selall').hide(); }

    function esc(v) { return $('<div>').text(String(v === null || v === undefined ? '' : v)).html(); }

    function currentFilters() {
        var statuses = ($('#f-status').val() || []).join(',');
        return {
            status: statuses, severity: $('#f-severity').val() || '',
            category: $('#f-category').val() || '', source: $('#f-source').val() || '',
            finding_type: $.trim($('#f-type').val() || '')
        };
    }
    function query(extra) {
        var f = $.extend(currentFilters(), extra || {});
        return Object.keys(f).filter(function(k) { return f[k] !== ''; })
            .map(function(k) { return k + '=' + encodeURIComponent(f[k]); }).join('&');
    }

    // Seed filters from the page URL (deep links from the dashboard widget).
    (function seedFromUrl() {
        var p = new URLSearchParams(window.location.search);
        ['severity', 'category', 'source'].forEach(function(k) { if (p.get(k)) { $('#f-' + (k === 'severity' ? 'severity' : k)).val(p.get(k)); } });
        if (p.get('finding_type')) { $('#f-type').val(p.get('finding_type')); }
        if (p.get('status')) { $('#f-status').val(p.get('status').split(',')); }
    })();

    // Filter dropdown options from stats.
    $.getJSON(window.simplemdmModuleUrl('get_mcp_finding_stats'), function(s) {
        Object.keys((s && s.by_category) || {}).sort().forEach(function(c) {
            $('#f-category').append($('<option>').val(c).text(c));
        });
        Object.keys((s && s.by_source) || {}).sort().forEach(function(src) {
            $('#f-source').append($('<option>').val(src).text(src));
        });
        var p = new URLSearchParams(window.location.search);
        if (p.get('category')) { $('#f-category').val(p.get('category')); }
        if (p.get('source')) { $('#f-source').val(p.get('source')); }
        load();
    });

    function load() {
        $.getJSON(window.simplemdmModuleUrl('get_mcp_findings') + '?' + query({ limit: pageSize, offset: offset }), function(data) {
            var rows = (data && data.findings) ? data.findings : [];
            var $tb = $('#f-table tbody').empty();
            rows.forEach(function(f) {
                var sev = String(f.severity || 'info').toLowerCase();
                var deviceUrl = appUrl + '/module/simplemdm/device/' + encodeURIComponent(String(f.serial_number || ''));
                $tb.append($('<tr>')
                    .append(isAdmin ? '<td><input type="checkbox" class="f-sel" value="' + Number(f.id) + '"></td>' : '<td></td>')
                    .append('<td><span class="badge alert-' + esc(sev) + '">' + esc(sev) + '</span></td>')
                    .append('<td>' + esc(f.status) + '</td>')
                    .append('<td>' + esc(f.finding_type) + '</td>')
                    .append('<td>' + esc(f.category || '') + '</td>')
                    .append('<td><a href="' + deviceUrl + '">' + esc(f.serial_number || '') + '</a></td>')
                    .append('<td>' + esc(f.message || '') + '</td>')
                    .append('<td>' + esc(f.source || '') + '</td>')
                    .append('<td>' + esc(String(f.last_seen_at || '').slice(0, 10)) + '</td>'));
            });
            if (!rows.length) { $tb.append('<tr><td colspan="9" class="text-muted">No findings match.</td></tr>'); }
            $('#f-pageinfo').text('rows ' + (offset + 1) + '-' + (offset + rows.length));
            $('#f-prev').prop('disabled', offset === 0);
            $('#f-next').prop('disabled', rows.length < pageSize);
            $('#f-selall').prop('checked', false); updateBulkbar();
        });
        $('#f-export-csv').attr('href', window.simplemdmModuleUrl('export_mcp_findings') + '?format=csv&' + query());
        $('#f-export-json').attr('href', window.simplemdmModuleUrl('export_mcp_findings') + '?format=json&' + query());
    }

    function selectedIds() { return $('.f-sel:checked').map(function() { return Number(this.value); }).get(); }
    function updateBulkbar() {
        var n = selectedIds().length;
        $('#f-bulkbar').toggle(isAdmin && n > 0);
        $('#f-selcount').text(n + ' selected');
    }

    $('#f-apply').on('click', function() { offset = 0; load(); });
    $('#f-prev').on('click', function() { offset = Math.max(0, offset - pageSize); load(); });
    $('#f-next').on('click', function() { offset += pageSize; load(); });
    $(document).on('change', '.f-sel, #f-selall', function() {
        if (this.id === 'f-selall') { $('.f-sel').prop('checked', this.checked); }
        updateBulkbar();
    });
    $('[data-bulk]').on('click', function() {
        var ids = selectedIds(); if (!ids.length) { return; }
        var action = $(this).attr('data-bulk');
        $.ajax({
            url: window.simplemdmModuleUrl(action + '_mcp_finding'),
            method: 'POST', contentType: 'application/json', data: JSON.stringify({ ids: ids })
        }).always(load);
    });
});
</script>
```

- [ ] **Step 2: Point the widget at the page.** In `views/simplemdm_mcp_findings_widget.php`:

Replace the `+N more` note markup with a link (inside `renderCategoryGroup`, keep surrounding code):

```javascript
            if (hidden > 0) {
                var pageUrl = appUrl + '/module/simplemdm/findings?finding_type=' + encodeURIComponent(type.name) + (group.name !== 'Uncategorized' ? '&category=' + encodeURIComponent(group.name) : '');
                $body.append('<span class="simplemdm-mcp-type-more">+' + hidden + ' more &mdash; <a href="' + pageUrl + '">view all ' + esc(type.name) + ' findings</a></span>');
            }
```

And the truncation note in `render()`:

```javascript
        if (total > findings.length) {
            moreNote.html('Fetched the ' + findings.length + ' most recent of ' + total + ' findings. <a href="' + appUrl + '/module/simplemdm/findings">Open findings browser</a>').show();
        } else {
            moreNote.hide();
        }
```

- [ ] **Step 3: Verify in browse** — page loads with rows; each filter narrows (spot-check `severity=info` shows only info); pagination Next/Prev on a >50 dataset; select 2 rows → bulk Acknowledge → statuses update; Export CSV link downloads with filters in the URL; deep link `/module/simplemdm/findings?finding_type=stale_device` arrives pre-filtered; widget "+N more" now links there. No console errors.

- [ ] **Step 4: Docs** — README widget section: change "+N more not shown" description to "links into the findings browser page (`/module/simplemdm/findings`)", and add the page to the README Table-of-Contents-adjacent route mentions; DEVELOPER_GUIDE §7 UI map: add `findings` page row; TESTING: new QA block "Findings browser page" (filters, pagination, bulk actions require admin, export links carry filters, deep links).

- [ ] **Step 5: Commit**

```bash
git add views/simplemdm_findings_page.php views/simplemdm_mcp_findings_widget.php README.md docs/DEVELOPER_GUIDE.md docs/TESTING.md
git commit -m "feat(simplemdm): findings browser page with filters, bulk actions, export"
```

---

## Slice D — Events summary integration (PRD §13)

### Task 7: Pure summary helper (TDD)

**Files:**
- Modify: `simplemdm_mcp_finding_model.php`
- Test: `tests/Unit/McpFindingModelTest.php`

**Interfaces:**
- Produces: `Simplemdm_mcp_finding_model::summarizeFindingsForEvent(array $sevCounts, int $warnThreshold): ?array` — input like `['danger'=>2,'warning'=>18,'info'=>3]` (active-status counts); returns `['type'=>'danger'|'warning'|'info', 'message'=>string]`, or `null` when nothing meets the bar (clear the event). Consumed by Task 8.

- [ ] **Step 1: Write failing tests** (append to `tests/Unit/McpFindingModelTest.php`):

```php
    public function testSummarizeFindingsDangerWins(): void
    {
        $s = Simplemdm_mcp_finding_model::summarizeFindingsForEvent(['danger' => 2, 'warning' => 9, 'info' => 1], 1);
        $this->assertSame('danger', $s['type']);
        $this->assertSame('SimpleMDM MCP: 2 danger findings require immediate attention.', $s['message']);
    }

    public function testSummarizeFindingsWarningsAtThreshold(): void
    {
        $s = Simplemdm_mcp_finding_model::summarizeFindingsForEvent(['danger' => 0, 'warning' => 18, 'info' => 0], 10);
        $this->assertSame('warning', $s['type']);
        $this->assertSame('SimpleMDM MCP: 18 warnings detected across the fleet.', $s['message']);
    }

    public function testSummarizeFindingsWarningsBelowThresholdFallsToInfo(): void
    {
        $s = Simplemdm_mcp_finding_model::summarizeFindingsForEvent(['danger' => 0, 'warning' => 3, 'info' => 2], 10);
        $this->assertSame('info', $s['type']);
        $this->assertSame('SimpleMDM MCP: informational findings available (3 warnings below threshold, 2 info).', $s['message']);
    }

    public function testSummarizeFindingsNothingOpenReturnsNull(): void
    {
        $this->assertNull(Simplemdm_mcp_finding_model::summarizeFindingsForEvent(['danger' => 0, 'warning' => 0, 'info' => 0], 1));
    }

    public function testSummarizeFindingsSingularDanger(): void
    {
        $s = Simplemdm_mcp_finding_model::summarizeFindingsForEvent(['danger' => 1, 'warning' => 0, 'info' => 0], 1);
        $this->assertSame('SimpleMDM MCP: 1 danger finding requires immediate attention.', $s['message']);
    }
```

- [ ] **Step 2: Run to fail** — `vendor/bin/phpunit --filter SummarizeFindings` → 5 failures ("undefined method").

- [ ] **Step 3: Implement** (add to `simplemdm_mcp_finding_model.php` beside the other static helpers):

```php
    /**
     * Map active-finding severity counts to a single MunkiReport Events
     * summary (PRD section 13.1). Returns null when there is nothing worth an
     * event (clear it). Severity model is the module's 3-value taxonomy.
     *
     * @param array $sevCounts   ['danger'=>int,'warning'=>int,'info'=>int]
     * @param int   $warnThreshold warnings needed before a warning-level event
     * @return array|null ['type'=>string,'message'=>string]
     **/
    public static function summarizeFindingsForEvent($sevCounts, $warnThreshold)
    {
        $danger  = max(0, (int) ($sevCounts['danger'] ?? 0));
        $warning = max(0, (int) ($sevCounts['warning'] ?? 0));
        $info    = max(0, (int) ($sevCounts['info'] ?? 0));
        $warnThreshold = max(1, (int) $warnThreshold);

        if ($danger > 0) {
            return [
                'type'    => 'danger',
                'message' => sprintf(
                    'SimpleMDM MCP: %d danger finding%s require%s immediate attention.',
                    $danger, $danger === 1 ? '' : 's', $danger === 1 ? 's' : ''
                ),
            ];
        }
        if ($warning >= $warnThreshold) {
            return [
                'type'    => 'warning',
                'message' => sprintf('SimpleMDM MCP: %d warnings detected across the fleet.', $warning),
            ];
        }
        if ($warning > 0 || $info > 0) {
            return [
                'type'    => 'info',
                'message' => sprintf(
                    'SimpleMDM MCP: informational findings available (%d warnings below threshold, %d info).',
                    $warning, $info
                ),
            ];
        }
        return null;
    }
```

- [ ] **Step 4: Run to pass** — `vendor/bin/phpunit` → all green.

- [ ] **Step 5: Commit**

```bash
git add simplemdm_mcp_finding_model.php tests/Unit/McpFindingModelTest.php
git commit -m "feat(simplemdm): summarizeFindingsForEvent helper for Events integration"
```

### Task 8: Wire the summary event into ingest + admin actions

**Files:**
- Modify: `simplemdm_controller.php` (new private method; calls at end of `ingest_mcp_findings` success path and `applyFindingStatusAction` success path; two new keys in the admin-settings allowlist used by `save_config`/`get_config` — pattern-match how `mcp_findings_enabled` is registered)
- Modify: `views/simplemdm_admin.php` ("MCP Findings Settings" panel gains the two fields — copy the exact markup pattern of the existing `mcp_findings_enabled` toggle and `mcp_findings_metadata_max_bytes` number input)
- Modify: `docs/API_REFERENCE.md` (settings table), `docs/SECURITY.md` (note the event write), `README.md` (settings reference), `docs/TESTING.md` (QA steps)

**Interfaces:**
- Consumes: Task 7's `summarizeFindingsForEvent`; existing `store_event(serial, module, type, message, payload)` host helper and `Event_model` (both already used at controller lines ~347/363); existing settings helpers used by `mcp_findings_enabled` (read via the same config-get pattern — locate `mcp_findings_enabled()` private method ~line 6400 and mirror it).
- Produces: settings `mcp_findings_event_enabled` (default `'0'` — **off by default**, existing installs' Events UI must not change without opt-in) and `mcp_findings_event_warning_threshold` (default `'1'`, min 1); one deduplicated event under module key `simplemdm_mcp_findings_summary`.

**Design note (PRD deviation, documented):** MunkiReport events are machine-scoped (the Events UI joins `event` to `machine`/`reportdata` by serial — a fleet-level row would never render; see DEVELOPER_GUIDE "Important UI note"). The summary event is therefore anchored to the *worst* device: highest-severity active finding, tie-broken by most active findings, then lowest serial for determinism. Clicking the event thus leads somewhere useful. The previous anchor's row is deleted when the anchor moves or the summary clears.

- [ ] **Step 1: Add the sync method to the controller**

```php
    /**
     * Upsert/clear the single fleet-summary event (PRD section 13), anchored
     * to the worst affected device because host events are machine-scoped.
     *
     * @return void
     **/
    private function sync_mcp_findings_summary_event()
    {
        if ($this->get_module_setting('mcp_findings_event_enabled', '0') !== '1') {
            return;
        }

        $active = Simplemdm_mcp_finding_model::whereIn('status', Simplemdm_mcp_finding_model::ACTIVE_STATUSES)
            ->get(['serial_number', 'severity']);
        $counts = ['danger' => 0, 'warning' => 0, 'info' => 0];
        $perDevice = [];
        foreach ($active as $row) {
            $sev = in_array($row->severity, ['danger', 'warning', 'info'], true) ? $row->severity : 'info';
            $counts[$sev]++;
            $serial = (string) $row->serial_number;
            if ($serial === '') { continue; }
            if (! isset($perDevice[$serial])) { $perDevice[$serial] = ['danger' => 0, 'warning' => 0, 'info' => 0, 'total' => 0]; }
            $perDevice[$serial][$sev]++;
            $perDevice[$serial]['total']++;
        }

        $threshold = max(1, (int) $this->get_module_setting('mcp_findings_event_warning_threshold', '1'));
        $summary = Simplemdm_mcp_finding_model::summarizeFindingsForEvent($counts, $threshold);

        // Always clear the previous row first: the anchor device can change
        // between scans and stale rows must not linger on the old serial.
        Event_model::where('module', $this->simplemdm_event_module('mcp_findings_summary'))->delete();
        if ($summary === null || empty($perDevice)) {
            return;
        }

        uasort($perDevice, function ($a, $b) {
            if ($a['danger'] !== $b['danger']) { return $b['danger'] - $a['danger']; }
            if ($a['warning'] !== $b['warning']) { return $b['warning'] - $a['warning']; }
            return $b['total'] - $a['total'];
        });
        $serials = array_keys($perDevice);
        $anchor = $serials[0];
        // Deterministic tie-break: among equal-worst devices pick lowest serial.
        foreach ($serials as $s) {
            $t = $perDevice[$s]; $w = $perDevice[$anchor];
            if ($t['danger'] === $w['danger'] && $t['warning'] === $w['warning'] && $t['total'] === $w['total'] && strcmp($s, $anchor) < 0) {
                $anchor = $s;
            }
        }

        store_event(
            $anchor,
            $this->simplemdm_event_module('mcp_findings_summary'),
            $summary['type'],
            $summary['message'],
            json_encode($counts)
        );
    }
```

If the controller's setting-reader has a different name than `get_module_setting($name, $default)`, locate the private method `mcp_findings_enabled()` (~line 6400), see what it calls, and use exactly that mechanism for both reads.

- [ ] **Step 2: Call it** at the end of `ingest_mcp_findings`'s success path (immediately before its final `jsonView([...'status' => 'success'...])`) and at the end of `applyFindingStatusAction` (before its success `jsonView`):

```php
        $this->sync_mcp_findings_summary_event();
```

- [ ] **Step 3: Register the two settings** in the same allowlist(s) where `mcp_findings_auto_resolve` lives (grep `mcp_findings_auto_resolve` in the controller; add adjacent entries, with the threshold clamped `max(1, (int) $value)` in the same place `mcp_findings_metadata_max_bytes` clamps its floor). Add the two fields to the "MCP Findings Settings" panel in `views/simplemdm_admin.php` by copying the exact toggle/number-input markup of those two existing settings.

- [ ] **Step 4: Verify end-to-end**

```bash
KEY=$(python3 -c "import sqlite3;print(sqlite3.connect('/Users/helpdesk/websites/munkireport-php/app/db/db.sqlite').execute(\"SELECT value FROM simplemdm_config WHERE name='api_key'\").fetchone()[0])")
# Default off: ingest something, confirm no event row
python3 -c "import sqlite3;print(sqlite3.connect('/Users/helpdesk/websites/munkireport-php/app/db/db.sqlite').execute(\"SELECT COUNT(*) FROM event WHERE module='simplemdm_mcp_findings_summary'\").fetchone())"
```
Expected `(0,)`. Then enable `mcp_findings_event_enabled` in the admin UI, push one finding via `ingest_mcp_findings` (curl, any test source with `replace:false`), and re-run the count → `(1,)`; check the row's serial is the worst device and message matches Task 7 wording; resolve everything from that test source and confirm the event row updates/clears per the summary logic. Confirm the event renders in `/show/listing/event/event` (anchor serial must exist in `machine` — pick a real serial for the test finding).

- [ ] **Step 5: Run PHPUnit** (`vendor/bin/phpunit`) — green.

- [ ] **Step 6: Docs** — API_REFERENCE settings table (+2 rows with defaults and clamp), SECURITY (event write path note: summary event writes only under module key `simplemdm_mcp_findings_summary`, gated by an off-by-default setting), README settings reference, TESTING QA block (enable setting → ingest → event appears; resolve → clears; anchor moves with worst device; built-in `simplemdm_*` events untouched — PRD 13.3).

- [ ] **Step 7: Commit**

```bash
git add simplemdm_controller.php views/simplemdm_admin.php docs/API_REFERENCE.md docs/SECURITY.md README.md docs/TESTING.md
git commit -m "feat(simplemdm): deduplicated fleet findings summary event (PRD 13), off by default"
```

---

## Slice E — Phase-2/3 widgets (PRD §14.1)

### Task 9: Timeline + top-devices data (TDD on pure helpers, then routes)

**Files:**
- Modify: `simplemdm_mcp_finding_model.php` (two pure static helpers)
- Modify: `simplemdm_controller.php` (extend `get_mcp_finding_stats` response with `top_devices`; new route `get_mcp_finding_timeline`; add it to `$token_read_actions`)
- Test: `tests/Unit/McpFindingModelTest.php`
- Modify: `docs/API_REFERENCE.md`

**Interfaces:**
- Produces:
  - `Simplemdm_mcp_finding_model::computeDeviceRiskRows(array $rows, int $limit): array` — input rows `[['serial_number'=>string,'severity'=>string], ...]` (active findings); output list sorted desc: `[['serial_number'=>s,'score'=>int,'danger'=>d,'warning'=>w,'info'=>i], ...]`, score = `3*danger + 2*warning + 1*info`.
  - `Simplemdm_mcp_finding_model::bucketFindingDates(array $rows, int $days, string $today): array` — input rows `[['first_seen_at'=>iso,'resolved_at'=>iso|null], ...]`; output `['labels'=>[...N ISO dates ending $today...], 'new'=>[ints], 'resolved'=>[ints]]`.
  - Route `GET get_mcp_finding_timeline?days=30` (token-readable) → that structure; `get_mcp_finding_stats` gains `top_devices` (max 10).

- [ ] **Step 1: Failing tests**

```php
    public function testComputeDeviceRiskRowsWeightsAndSorts(): void
    {
        $rows = [
            ['serial_number' => 'AAA', 'severity' => 'warning'],
            ['serial_number' => 'BBB', 'severity' => 'danger'],
            ['serial_number' => 'AAA', 'severity' => 'info'],
            ['serial_number' => 'BBB', 'severity' => 'danger'],
        ];
        $out = Simplemdm_mcp_finding_model::computeDeviceRiskRows($rows, 10);
        $this->assertSame('BBB', $out[0]['serial_number']); // 3+3=6 beats 2+1=3
        $this->assertSame(6, $out[0]['score']);
        $this->assertCount(2, $out);
    }

    public function testComputeDeviceRiskRowsDangerBreaksScoreTie(): void
    {
        $rows = [
            ['serial_number' => 'AAA', 'severity' => 'warning'],
            ['serial_number' => 'AAA', 'severity' => 'warning'],
            ['serial_number' => 'BBB', 'severity' => 'danger'],
            ['serial_number' => 'BBB', 'severity' => 'info'],
        ];
        $out = Simplemdm_mcp_finding_model::computeDeviceRiskRows($rows, 10);
        $this->assertSame(4, $out[0]['score']);
        $this->assertSame('BBB', $out[0]['serial_number']); // equal score 4, BBB has a danger
    }

    public function testComputeDeviceRiskRowsHonorsLimitAndSkipsEmptySerial(): void
    {
        $rows = [
            ['serial_number' => '', 'severity' => 'danger'],
            ['serial_number' => 'AAA', 'severity' => 'info'],
            ['serial_number' => 'BBB', 'severity' => 'warning'],
        ];
        $out = Simplemdm_mcp_finding_model::computeDeviceRiskRows($rows, 1);
        $this->assertCount(1, $out);
        $this->assertSame('BBB', $out[0]['serial_number']);
    }

    public function testBucketFindingDatesCountsNewAndResolvedPerDay(): void
    {
        $rows = [
            ['first_seen_at' => '2026-07-09T10:00:00+00:00', 'resolved_at' => '2026-07-10T09:00:00+00:00'],
            ['first_seen_at' => '2026-07-10T02:00:00+00:00', 'resolved_at' => null],
            ['first_seen_at' => '2026-06-01T00:00:00+00:00', 'resolved_at' => null], // outside window: ignored for 'new'
        ];
        $out = Simplemdm_mcp_finding_model::bucketFindingDates($rows, 3, '2026-07-11');
        $this->assertSame(['2026-07-09', '2026-07-10', '2026-07-11'], $out['labels']);
        $this->assertSame([1, 1, 0], $out['new']);
        $this->assertSame([0, 1, 0], $out['resolved']);
    }
```

- [ ] **Step 2: Run to fail** — `vendor/bin/phpunit --filter 'DeviceRisk|BucketFinding'` → failures.

- [ ] **Step 3: Implement**

```php
    /**
     * Rank devices by open-finding weight: 3*danger + 2*warning + 1*info.
     * Ties break danger-count-first, then warning count, then serial asc.
     *
     * @param array $rows  [['serial_number'=>string,'severity'=>string],...]
     * @param int   $limit
     * @return array
     **/
    public static function computeDeviceRiskRows($rows, $limit)
    {
        $devices = [];
        foreach ($rows as $row) {
            $serial = trim((string) ($row['serial_number'] ?? ''));
            if ($serial === '') { continue; }
            $sev = in_array($row['severity'] ?? '', ['danger', 'warning', 'info'], true) ? $row['severity'] : 'info';
            if (! isset($devices[$serial])) {
                $devices[$serial] = ['serial_number' => $serial, 'score' => 0, 'danger' => 0, 'warning' => 0, 'info' => 0];
            }
            $devices[$serial][$sev]++;
            $devices[$serial]['score'] += ($sev === 'danger' ? 3 : ($sev === 'warning' ? 2 : 1));
        }
        $out = array_values($devices);
        usort($out, function ($a, $b) {
            if ($a['score'] !== $b['score']) { return $b['score'] - $a['score']; }
            if ($a['danger'] !== $b['danger']) { return $b['danger'] - $a['danger']; }
            if ($a['warning'] !== $b['warning']) { return $b['warning'] - $a['warning']; }
            return strcmp($a['serial_number'], $b['serial_number']);
        });
        return array_slice($out, 0, max(1, (int) $limit));
    }

    /**
     * Bucket first_seen_at/resolved_at into daily counts for the last $days
     * days ending at $today (UTC date string). Pure and DB-agnostic: dates
     * compare via their ISO-8601 10-char prefix.
     *
     * @param array  $rows [['first_seen_at'=>string,'resolved_at'=>?string],...]
     * @param int    $days
     * @param string $today 'YYYY-MM-DD'
     * @return array ['labels'=>[], 'new'=>[], 'resolved'=>[]]
     **/
    public static function bucketFindingDates($rows, $days, $today)
    {
        $days = max(1, (int) $days);
        $labels = [];
        $base = strtotime($today . 'T00:00:00Z');
        for ($i = $days - 1; $i >= 0; $i--) {
            $labels[] = gmdate('Y-m-d', $base - $i * 86400);
        }
        $index = array_flip($labels);
        $new = array_fill(0, $days, 0);
        $resolved = array_fill(0, $days, 0);
        foreach ($rows as $row) {
            $first = substr((string) ($row['first_seen_at'] ?? ''), 0, 10);
            if (isset($index[$first])) { $new[$index[$first]]++; }
            $res = substr((string) ($row['resolved_at'] ?? ''), 0, 10);
            if ($res !== '' && isset($index[$res])) { $resolved[$index[$res]]++; }
        }
        return ['labels' => $labels, 'new' => $new, 'resolved' => $resolved];
    }
```

- [ ] **Step 4: Run to pass** — `vendor/bin/phpunit` → green.

- [ ] **Step 5: Wire routes.** In `get_mcp_finding_stats`, before the final `jsonView`, add:

```php
        $riskRows = $applyFilters(
            Simplemdm_mcp_finding_model::whereIn('status', Simplemdm_mcp_finding_model::ACTIVE_STATUSES)
        )->get(['serial_number', 'severity'])->map(function ($r) {
            return ['serial_number' => $r->serial_number, 'severity' => $r->severity];
        })->all();
        $top_devices = Simplemdm_mcp_finding_model::computeDeviceRiskRows($riskRows, 10);
```

and include `'top_devices' => $top_devices,` in the `jsonView` payload. New route method:

```php
    /**
     * Daily new/resolved finding counts for trend widgets.
     * GET /module/simplemdm/get_mcp_finding_timeline?days=30  (max 90)
     *
     * @return void
     **/
    public function get_mcp_finding_timeline()
    {
        if (! $this->mcp_findings_enabled()) {
            jsonView(['status' => 'error', 'message' => 'MCP findings are disabled'], 403);
            return;
        }
        $days = isset($_GET['days']) ? (int) $_GET['days'] : 30;
        if ($days < 1) { $days = 30; }
        if ($days > 90) { $days = 90; }
        $since = gmdate('c', time() - ($days + 1) * 86400);
        $rows = Simplemdm_mcp_finding_model::where(function ($q) use ($since) {
                $q->where('first_seen_at', '>=', $since)->orWhere('resolved_at', '>=', $since);
            })
            ->get(['first_seen_at', 'resolved_at'])
            ->map(function ($r) { return ['first_seen_at' => $r->first_seen_at, 'resolved_at' => $r->resolved_at]; })
            ->all();
        jsonView(Simplemdm_mcp_finding_model::bucketFindingDates($rows, $days, gmdate('Y-m-d')));
    }
```

Add `'get_mcp_finding_timeline'` to `$token_read_actions` (line 16).

- [ ] **Step 6: Verify via curl** — `get_mcp_finding_stats` now has `top_devices` (≤10, sorted); `get_mcp_finding_timeline?days=7` returns 7 labels ending today with plausible counts (your 2026-07-10 push should appear as a `new` spike).

- [ ] **Step 7: Docs + commit** — API_REFERENCE: document `top_devices` on stats and the new timeline route (token-readable).

```bash
git add simplemdm_mcp_finding_model.php simplemdm_controller.php tests/Unit/McpFindingModelTest.php docs/API_REFERENCE.md
git commit -m "feat(simplemdm): finding timeline route and top-devices risk ranking"
```

### Task 10: Severity + source chart widgets

**Files:**
- Create: `views/simplemdm_mcp_severity_widget.php`
- Create: `views/simplemdm_mcp_source_widget.php`
- Modify: `provides.yml` (two `widgets:` entries)

**Interfaces:**
- Consumes: `get_mcp_finding_stats` (`by_severity`, `by_source`); NVD3 donut conventions from `views/simplemdm_enrollment_widget.php` (svg-container + `simplemdmThemePalette()`); deep links to `/module/simplemdm/findings?severity=...` / `?source=...` (Task 6).
- Produces: widgets `simplemdm_mcp_severity` ("MCP Findings by Severity") and `simplemdm_mcp_source` ("MCP Findings by Source") registered in `provides.yml` (they then appear in the admin Widget Visibility list automatically).

- [ ] **Step 1: Severity donut widget** (`views/simplemdm_mcp_severity_widget.php`) — copy `simplemdm_enrollment_widget.php`'s exact NVD3 donut structure, with these substitutions (full file, following that template): widget id `simplemdm-mcp-severity-widget`, `data-widget="simplemdm_mcp_severity"`, title icon `fa-flag`, title text `MCP Findings by Severity`, data source:

```javascript
    $.getJSON(window.simplemdmModuleUrl('get_mcp_finding_stats'), function(stats) {
        var by = (stats && stats.by_severity) ? stats.by_severity : {};
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        var colorMap = { danger: palette.danger || '#c23b3b', warning: palette.warning || '#e6a23c', info: palette.info || '#4a90d9' };
        var chartData = ['danger', 'warning', 'info'].filter(function(s) { return Number(by[s] || 0) > 0; })
            .map(function(s) { return { label: s + ' (' + by[s] + ')', value: Number(by[s]), color: colorMap[s], key: s }; });
        // empty state, donut render, and list-group rows exactly per the enrollment template,
        // with each list row linking to appUrl + '/module/simplemdm/findings?severity=' + row.key
    });
```

- [ ] **Step 2: Source widget** (`views/simplemdm_mcp_source_widget.php`) — same template, id `simplemdm-mcp-source-widget`, title `MCP Findings by Source`, data from `stats.by_source` sorted by count desc, top 8 + an `other` bucket summing the rest, list rows linking to `/module/simplemdm/findings?source=<name>`.

- [ ] **Step 3: Register** in `provides.yml` under `widgets:`:

```yaml
    simplemdm_mcp_severity:
        view: simplemdm_mcp_severity_widget
    simplemdm_mcp_source:
        view: simplemdm_mcp_source_widget
```

- [ ] **Step 4: Verify in browse** — add both widgets to the dashboard (or view the module report page if it auto-includes), donut renders with correct counts (`by_severity` currently 182/1), legend rows deep-link correctly, empty-state shows `no data` when filters produce nothing, no console errors, both appear in admin Widget Visibility list.

- [ ] **Step 5: Commit**

```bash
git add views/simplemdm_mcp_severity_widget.php views/simplemdm_mcp_source_widget.php provides.yml
git commit -m "feat(simplemdm): findings-by-severity and findings-by-source widgets"
```

### Task 11: Open-danger findings widget

**Files:**
- Create: `views/simplemdm_mcp_critical_widget.php`
- Modify: `provides.yml`

**Interfaces:**
- Consumes: `get_mcp_findings?severity=danger&limit=25` (active statuses by default); `bindWheelScroll` pattern — the widget's list is scrollable, so it must use the `simplemdm-list-scroll` class AND get bound in `views/simplemdm_widget_modern_assets.php`'s `bindKnownScrollers()`.
- Produces: widget `simplemdm_mcp_critical` ("Open Danger Findings").

- [ ] **Step 1: Widget view** — structure copied from the MCP findings widget shell (panel + `simplemdm-list-scroll` + `list-group` with a unique id `simplemdm-mcp-critical-list`), body:

```javascript
$(document).on('appReady', function() {
    $.getJSON(window.simplemdmModuleUrl('get_mcp_findings') + '?severity=danger&limit=25', function(data) {
        var findings = (data && data.findings) ? data.findings : [];
        var $list = $('#simplemdm-mcp-critical-list').empty();
        if (!findings.length) {
            $('#simplemdm-mcp-critical-widget .panel-body').html('<p class="text-center">No open danger findings.</p>');
            return;
        }
        findings.forEach(function(f) {
            var deviceUrl = appUrl + '/module/simplemdm/device/' + encodeURIComponent(String(f.serial_number || ''));
            $list.append($('<span class="list-group-item">')
                .append('<strong>' + $('<i>').text(f.finding_type || '-').html() + '</strong> ')
                .append(f.serial_number ? '<a href="' + deviceUrl + '">' + $('<i>').text(f.serial_number).html() + '</a>' : '')
                .append('<span class="simplemdm-mcp-finding-message">' + $('<i>').text(f.message || '').html() + '</span>'));
        });
    });
});
```

- [ ] **Step 2: Bind the scroller.** In `views/simplemdm_widget_modern_assets.php`, `bindKnownScrollers()`, add:

```javascript
        bindWheelScroll(document.getElementById('simplemdm-mcp-critical-list'));
```

Also add the widget id to `markScrollableSimplemdmLists()`'s static-opt-in skip list (same pattern as `simplemdm-mcp-findings-widget`, ~line 2717).

- [ ] **Step 3: Register + verify + commit** — `provides.yml` entry `simplemdm_mcp_critical: {view: simplemdm_mcp_critical_widget}`; verify empty-state today (0 danger findings) and seeded-state by pushing one `severity:danger` test finding via curl (then resolving it); scroll binding attribute present.

```bash
git add views/simplemdm_mcp_critical_widget.php views/simplemdm_widget_modern_assets.php provides.yml
git commit -m "feat(simplemdm): open danger findings widget"
```

### Task 12: Findings timeline widget

**Files:**
- Create: `views/simplemdm_mcp_timeline_widget.php`
- Modify: `provides.yml`

**Interfaces:**
- Consumes: Task 9's `get_mcp_finding_timeline?days=30` → `{labels, new, resolved}`; NVD3 line/multiBar conventions — copy the chart scaffold from `views/simplemdm_trend_widget.php` if it exists (check `ls views/ | grep trend`; the module has `get_dashboard_trend`, so a trend widget exists — mirror its NVD3 lineChart usage exactly), otherwise from any nv.models usage in the module.
- Produces: widget `simplemdm_mcp_timeline` ("Findings Timeline") — two series ("New", "Resolved") over 30 days.

- [ ] **Step 1: Widget view** — panel shell (id `simplemdm-mcp-timeline-widget`, svg-container height 180) + script mapping the route payload to NVD3 series:

```javascript
        var series = [
            { key: 'New', color: palette.warning || '#e6a23c',
              values: data.labels.map(function(d, i) { return { x: i, y: data['new'][i] }; }) },
            { key: 'Resolved', color: palette.positive || '#2f9e44',
              values: data.labels.map(function(d, i) { return { x: i, y: data.resolved[i] }; }) }
        ];
```

x-axis tick format maps index → `data.labels[i].slice(5)` (MM-DD). Empty state when every count is 0: `No findings activity in the last 30 days.`

- [ ] **Step 2: Register, verify (your dataset shows a `new` spike on 2026-07-10), commit**

```bash
git add views/simplemdm_mcp_timeline_widget.php provides.yml
git commit -m "feat(simplemdm): findings timeline widget"
```

### Task 13: Top devices by risk widget

**Files:**
- Create: `views/simplemdm_mcp_top_devices_widget.php`
- Modify: `provides.yml`

**Interfaces:**
- Consumes: `get_mcp_finding_stats` → `top_devices` (Task 9: `[{serial_number, score, danger, warning, info}]`).
- Produces: widget `simplemdm_mcp_top_devices` ("Top Devices by Findings") — ranked list, device links, per-severity badges. Non-scrolling (max 10 rows) — do NOT add `simplemdm-list-scroll`.

- [ ] **Step 1: Widget view** — panel shell + list:

```javascript
    $.getJSON(window.simplemdmModuleUrl('get_mcp_finding_stats'), function(stats) {
        var rows = (stats && stats.top_devices) ? stats.top_devices : [];
        var $list = $('#simplemdm-mcp-top-devices-list').empty();
        if (!rows.length) {
            $('#simplemdm-mcp-top-devices-widget .panel-body').html('<p class="text-center">No active findings.</p>');
            return;
        }
        rows.forEach(function(d, i) {
            var deviceUrl = appUrl + '/module/simplemdm/device/' + encodeURIComponent(d.serial_number);
            var badges = ['danger', 'warning', 'info'].filter(function(s) { return d[s] > 0; })
                .map(function(s) { return '<span class="badge alert-' + s + '">' + d[s] + '</span>'; }).join(' ');
            $list.append('<span class="list-group-item">#' + (i + 1) + ' <a href="' + deviceUrl + '">' +
                $('<i>').text(d.serial_number).html() + '</a> <span class="pull-right">' + badges + ' <span class="badge">' + d.score + '</span></span></span>');
        });
    });
```

- [ ] **Step 2: Register, verify (182 stale devices → a full top-10 with warning badges), commit**

```bash
git add views/simplemdm_mcp_top_devices_widget.php provides.yml
git commit -m "feat(simplemdm): top devices by findings widget"
```

### Task 14: Release pass

**Files:**
- Modify: `CHANGELOG.md` (`[Unreleased]` → all slices), `README.md` (widget catalog: 5 new widgets + findings page), `docs/DEVELOPER_GUIDE.md` (File-Level Quick Reference: new views), `docs/TESTING.md` (§8 functional matrix rows)

- [ ] **Step 1: CHANGELOG entries** under `[Unreleased]`: Added — findings browser page, device-page findings section, 5 widgets, `get_mcp_finding_timeline`, `top_devices`, `finding_type` filter, events summary settings; Changed — admin-action routes accept global-admin session.
- [ ] **Step 2: README widget catalog + docs quick-reference updates.**
- [ ] **Step 3: Full regression** — `vendor/bin/phpunit` green; browse pass over dashboard (all widgets render, no console errors), device page, findings page; Safari spot-check by the user (scroll + clicks on the new widgets/page).
- [ ] **Step 4: Commit** (release cut itself happens on user request, per repo convention):

```bash
git add CHANGELOG.md README.md docs/DEVELOPER_GUIDE.md docs/TESTING.md
git commit -m "docs(simplemdm): document findings UI completion (PRD 13/14)"
```

---

## Self-Review Notes

- **Spec coverage:** §14.2 → Tasks 3-4 (all PRD-listed fields shown; `risk_score`/`recommendation`/`title` intentionally absent — scoped out, live in `data` disclosure); §14.3 → Tasks 5-6 (search=filters, sorting deferred: server route has fixed `id desc` ordering — acceptable v1, noted as future work); §13 → Tasks 7-8 (machine-scoped-events deviation documented inline); §14.1 Phase 2/3 → Tasks 9-13 ("Recent MCP Findings"/"Summary counts" already exist as the shipped main widget; "Generic Findings Summary" is PRD-Future, out of scope).
- **Type consistency:** `summarizeFindingsForEvent(array, int): ?array{type,message}` used identically in Tasks 7/8; `computeDeviceRiskRows`/`bucketFindingDates` signatures match between Task 9 steps; `top_devices` field name consistent Tasks 9/13; `finding_type` param name consistent Tasks 2/6.
- **Known judgment calls (senior-dev decisions, revisit if wrong):** events summary anchored to worst device (host events are machine-scoped; fleet-level rows never render); events integration off by default; findings page is a custom module page (not a core `listings:` registration) so findings for serials absent from `machine` still display; list sorting fixed at newest-first for v1.

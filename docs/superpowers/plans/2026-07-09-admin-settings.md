# Admin Settings (Scoped) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add three real, working admin settings to the `simplemdm` MunkiReport module — `mcp_findings_enabled` (gate ingest/read/admin-action routes), `mcp_findings_metadata_max_bytes` (configurable cap replacing the hardcoded 4096-char truncation), `mcp_findings_auto_resolve` (global kill-switch on ingest's auto-resolve sweep) — using the module's existing settings infrastructure, plus an admin UI panel to manage them.

**Architecture:** Three-task change inside the existing `simplemdm` module: (1) `simplemdm_controller.php` gains the three settings in the existing `get_config()`/`save_config()` machinery and wires them into `ingest_mcp_findings()`, `get_mcp_findings()`, and `applyFindingStatusAction()` (the shared helper behind all four admin action routes); (2) `views/simplemdm_admin.php` gains a new self-contained collapsible panel (own form, own submit handler, own populate-on-load lines) following the exact pattern already used by the existing Widget Visibility / Event Settings panels; (3) docs. No new tables, no new model.

**Tech Stack:** PHP 8.1, Illuminate/Database (Eloquent) with SQLite (local dev, `app/db/db.sqlite`, bind-mounted into the `munkireport-local` Docker container) and MySQL (production). Admin UI is jQuery + Bootstrap 3 (no build step — plain `<script>` block in the view file).

**Verification approach — deviates from strict PHPUnit TDD:** Matches prior slices (no PHPUnit coverage for this module's controllers/views): every task's "test" step is a live HTTP request (curl) against the running `munkireport-local` Docker container plus a direct `sqlite3` read of `app/db/db.sqlite`, and for the UI task, a live browser check of the rendered admin page.

## Global Constraints

- Design spec: `docs/superpowers/specs/2026-07-09-admin-settings-design.md` — this plan implements that spec; if any step here appears to contradict it, the spec governs and should be flagged.
- Only these three settings are implemented. Do NOT add `mcp_findings_event_enabled`, `mcp_findings_event_min_severity`, `mcp_findings_event_mode`, `mcp_findings_retention_days`, `mcp_findings_allow_success`, `mcp_findings_require_token`, or `mcp_findings_generic_ready` — all explicitly deferred per the design spec (they gate features that don't exist yet, or would weaken auth with nothing to replace it).
- `mcp_findings_enabled` gates `ingest_mcp_findings()`, `get_mcp_findings()`, and all four admin action routes (via the shared `applyFindingStatusAction()` helper — one call site, not four). It does NOT change `views/simplemdm_mcp_findings_widget.php` — the widget's existing `.fail()` handler already renders "Failed to load MCP findings." on a non-2xx response with no code change needed.
- `mcp_findings_metadata_max_bytes` default is `65536` — a deliberate behavior change from today's hardcoded `4096` (confirmed during design, not a bug).
- `mcp_findings_auto_resolve` is a kill-switch that overrides the per-request `replace` flag: when `0`, the auto-resolve sweep never runs regardless of what the request sends. When `1` (default), today's `replace`-flag-driven behavior is unchanged.
- Follow the module's existing settings-validation pattern in `save_config()` exactly (boolean keys: `$value === '1' ? '1' : '0'`; integer keys with a floor: `$v = (int)$value; if ($v < FLOOR) { $v = FLOOR; }`).
- Follow the module's existing admin-panel pattern in `views/simplemdm_admin.php` exactly: a `simplemdm-admin-collapsible` panel with `data-collapsible`/`data-collapsible-toggle`/`data-collapsible-body` attributes (the collapse/expand behavior is fully generic and auto-registers via these data attributes — no additional JS registration needed), a `<form>` with its own `submit` handler that posts to `save_config`, and its own status line using the existing `setFormStatus()` helper (`views/simplemdm_admin.php:2065`).

---

### Task 1: Controller — settings storage and wiring

**Files:**
- Modify: `simplemdm_controller.php` — `get_config()` (currently starts at line 3606, insert a new default-fill block after the existing widget-keys loop ending at line 3693), `save_config()` (currently starts at line 3734, add to the `$config_keys` array and its validation `elseif` chain), `ingest_mcp_findings()` (currently starts at line 6418; add an enabled-gate right after the existing token check at lines 6421-6424; replace the hardcoded truncation at lines 6487-6489; add the kill-switch to the auto-resolve block at lines 6549-6571), `get_mcp_findings()` (currently starts at line 6593; add an enabled-gate), `applyFindingStatusAction()` (currently starts at line 6675; add an enabled-gate right after its existing token check at lines 6678-6681)
- Test: manual (`curl` against the live container + `sqlite3` row assertions)

**Interfaces:**
- Consumes: `$this->get_config_value($name, $default)` (already defined at `simplemdm_controller.php:95` — no changes to this method), `Simplemdm_config_model::updateOrCreate()` (already used throughout `save_config()`).
- Produces: a new private helper `mcp_findings_enabled(): bool` that Task 2 (UI, if it needs to know enabled state server-side — it doesn't, this task's UI plan reads state entirely from `get_config()`'s JSON response) and no other task in this plan directly calls, but is the single source of truth other future slices should reuse rather than re-reading `get_config_value('mcp_findings_enabled', ...)` inline.

- [ ] **Step 1: Add the `mcp_findings_enabled()` helper**

Insert immediately before `public function ingest_mcp_findings()` (line 6418):

```php
    private function mcp_findings_enabled()
    {
        return $this->get_config_value('mcp_findings_enabled', '1') !== '0';
    }

```

- [ ] **Step 2: Gate `ingest_mcp_findings()`**

In `ingest_mcp_findings()`, immediately after the existing token check (after line 6424's closing `}`, before the blank line at 6425), insert:

```php

        if (! $this->mcp_findings_enabled()) {
            jsonView(['status' => 'error', 'message' => 'MCP findings are disabled'], 403);
            return;
        }
```

- [ ] **Step 3: Gate `get_mcp_findings()`**

`get_mcp_findings()` (line 6593) has no auth/token check today — it's a public read route. Insert the enabled-gate as the very first statement in the method body:

```php
    public function get_mcp_findings($serial_number = '')
    {
        if (! $this->mcp_findings_enabled()) {
            jsonView(['status' => 'error', 'message' => 'MCP findings are disabled'], 403);
            return;
        }

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
```

(This replaces only the opening `public function get_mcp_findings($serial_number = '')\n    {\n        $limit = isset($_GET['limit'])...` lines — everything else in the method is unchanged.)

- [ ] **Step 4: Gate `applyFindingStatusAction()`**

In `applyFindingStatusAction()` (line 6675), immediately after its existing token check (after line 6681's closing `}`, before the blank line at 6682), insert:

```php

        if (! $this->mcp_findings_enabled()) {
            jsonView(['status' => 'error', 'message' => 'MCP findings are disabled'], 403);
            return;
        }
```

This single insertion covers `acknowledge_mcp_finding`, `resolve_mcp_finding`, `ignore_mcp_finding`, and `suppress_mcp_finding` since all four delegate to this helper.

- [ ] **Step 5: Replace the hardcoded metadata truncation**

In `ingest_mcp_findings()`, replace lines 6487-6489:

```php
                if (strlen($extra) > 4096) {
                    $extra = substr($extra, 0, 4096);
                }
```

with:

```php
                $metadataMaxBytes = (int) $this->get_config_value('mcp_findings_metadata_max_bytes', 65536);
                if (strlen($extra) > $metadataMaxBytes) {
                    $extra = substr($extra, 0, $metadataMaxBytes);
                }
```

- [ ] **Step 6: Add the auto-resolve kill-switch**

In `ingest_mcp_findings()`, replace line 6559-6571:

```php
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
```

with:

```php
        $autoResolveEnabled = $this->get_config_value('mcp_findings_auto_resolve', '1') !== '0';

        $resolved = 0;
        if ($replace && $autoResolveEnabled) {
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
```

- [ ] **Step 7: Add the `get_config()` default-fill block**

In `get_config()`, immediately after the widget-keys loop (after line 3693's closing `}`, before the method's closing `jsonView($config);` — currently at what was line 3696 before this edit, i.e. right after the block ending at 3693), insert:

```php

        if (! isset($config['mcp_findings_enabled'])) {
            $config['mcp_findings_enabled'] = '1';
        }
        if (! isset($config['mcp_findings_metadata_max_bytes'])) {
            $config['mcp_findings_metadata_max_bytes'] = '65536';
        }
        if (! isset($config['mcp_findings_auto_resolve'])) {
            $config['mcp_findings_auto_resolve'] = '1';
        }
```

- [ ] **Step 8: Add the three keys to `save_config()`'s allowlist and validation**

In `save_config()`, add `'mcp_findings_enabled'`, `'mcp_findings_metadata_max_bytes'`, `'mcp_findings_auto_resolve'` to the `$config_keys` array (the array currently ends with `'custom_event_rules_json',` before its closing `];`) — append these three entries there.

In the same method's `foreach ($config_keys as $key) { if (array_key_exists($key, $post)) { ... } }` validation chain, extend the boolean-key `if` condition (the one currently checking `$key === 'sync_delta_enabled' || ... || $key === 'client_reporter_proxy_only_enabled'`) to also include `|| $key === 'mcp_findings_enabled' || $key === 'mcp_findings_auto_resolve'`, and add a new `elseif` branch for the integer key, placed alongside the other integer-with-floor branches (e.g. next to the `client_reporter_max_payload_bytes` branch):

```php
                } elseif ($key === 'mcp_findings_metadata_max_bytes') {
                    $v = (int) $value;
                    if ($v < 1024) {
                        $v = 1024;
                    }
                    $value = (string) $v;
```

- [ ] **Step 9: Verify PHP syntax**

```bash
docker compose -f <repo-root>/docker-compose.yml exec munkireport php -l /var/munkireport/local/modules/simplemdm/simplemdm_controller.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 10: Verify default state (nothing changed yet)**

```bash
TOKEN="<paste your simplemdm sync token here>"
BASE="http://localhost:8888/index.php?/module/simplemdm"

curl -s "$BASE/get_config" -H "X-SIMPLEMDM-API-KEY: $TOKEN" | python3 -c "import json,sys; d=json.load(sys.stdin); print(d.get('mcp_findings_enabled'), d.get('mcp_findings_metadata_max_bytes'), d.get('mcp_findings_auto_resolve'))"
```

Expected: `1 65536 1`.

```bash
curl -s -X POST "$BASE/ingest_mcp_findings" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"settings_test","replace":false,"findings":[{"serial_number":"C02SETTINGS1","finding_type":"settings_check","severity":"info","message":"baseline"}]}'
```

Expected JSON: `"inserted":1`.

- [ ] **Step 11: Verify `mcp_findings_enabled=0` gates all three route groups**

```bash
curl -s -X POST "$BASE/save_config" -H "X-SIMPLEMDM-API-KEY: $TOKEN" -d "mcp_findings_enabled=0"
```

```bash
curl -s -X POST "$BASE/ingest_mcp_findings" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"settings_test","findings":[]}'
```

Expected: HTTP 403, `{"status":"error","message":"MCP findings are disabled"}`.

```bash
curl -s "$BASE/get_mcp_findings" -H "X-SIMPLEMDM-API-KEY: $TOKEN"
```

Expected: HTTP 403, same error body.

```bash
sqlite3 <repo-root>/app/db/db.sqlite \
  "SELECT id FROM simplemdm_mcp_finding WHERE source='settings_test';"
```

Note the id (call it `$ID`).

```bash
curl -s -X POST "$BASE/acknowledge_mcp_finding" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d "{\"id\": $ID}"
```

Expected: HTTP 403, same error body.

Restore default:

```bash
curl -s -X POST "$BASE/save_config" -H "X-SIMPLEMDM-API-KEY: $TOKEN" -d "mcp_findings_enabled=1"
curl -s "$BASE/get_mcp_findings" -H "X-SIMPLEMDM-API-KEY: $TOKEN"
```

Expected: HTTP 200, normal response (findings list, not the disabled error).

- [ ] **Step 12: Verify `mcp_findings_metadata_max_bytes` default and override**

```bash
PAYLOAD_5000="$(python3 -c "print('x' * 5000)")"
curl -s -X POST "$BASE/ingest_mcp_findings" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d "{\"source\":\"settings_test2\",\"replace\":false,\"findings\":[{\"serial_number\":\"C02SETTINGS2\",\"finding_type\":\"metadata_check\",\"severity\":\"info\",\"message\":\"m\",\"data\":\"$PAYLOAD_5000\"}]}"
```

```bash
sqlite3 <repo-root>/app/db/db.sqlite \
  "SELECT length(data) FROM simplemdm_mcp_finding WHERE source='settings_test2';"
```

Expected: `5000` (NOT truncated to 4096 — confirms the new 65536 default is in effect).

```bash
curl -s -X POST "$BASE/save_config" -H "X-SIMPLEMDM-API-KEY: $TOKEN" -d "mcp_findings_metadata_max_bytes=100"
sqlite3 <repo-root>/app/db/db.sqlite \
  "SELECT value FROM simplemdm_config WHERE name='mcp_findings_metadata_max_bytes';"
```

Expected: `1024` (the save-time floor from Step 8 clamps `100` up to `1024` — this confirms the floor is enforced, not a bug).

```bash
PAYLOAD_2000="$(python3 -c "print('y' * 2000)")"
curl -s -X POST "$BASE/ingest_mcp_findings" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d "{\"source\":\"settings_test3\",\"replace\":false,\"findings\":[{\"serial_number\":\"C02SETTINGS3\",\"finding_type\":\"metadata_check2\",\"severity\":\"info\",\"message\":\"m\",\"data\":\"$PAYLOAD_2000\"}]}"
```

```bash
sqlite3 <repo-root>/app/db/db.sqlite \
  "SELECT length(data) FROM simplemdm_mcp_finding WHERE source='settings_test3';"
```

Expected: `1024` (truncated to the floor-clamped cap — a value below `1024` cannot be set, by design, so this is the smallest cap reachable via `save_config`).

Restore default:

```bash
curl -s -X POST "$BASE/save_config" -H "X-SIMPLEMDM-API-KEY: $TOKEN" -d "mcp_findings_metadata_max_bytes=65536"
```

- [ ] **Step 13: Verify `mcp_findings_auto_resolve=0` kill-switch**

```bash
curl -s -X POST "$BASE/ingest_mcp_findings" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"settings_test4","replace":true,"findings":[{"serial_number":"C02SETTINGS4","finding_type":"autoresolve_check","severity":"info","message":"present"}]}'
curl -s -X POST "$BASE/save_config" -H "X-SIMPLEMDM-API-KEY: $TOKEN" -d "mcp_findings_auto_resolve=0"
curl -s -X POST "$BASE/ingest_mcp_findings" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"settings_test4","replace":true,"findings":[]}'
```

Expected JSON: `"resolved":0` (kill-switch prevented the sweep even though `replace:true` was sent).

```bash
sqlite3 <repo-root>/app/db/db.sqlite \
  "SELECT status FROM simplemdm_mcp_finding WHERE source='settings_test4';"
```

Expected: `open` (NOT resolved).

Restore default and verify normal behavior returns:

```bash
curl -s -X POST "$BASE/save_config" -H "X-SIMPLEMDM-API-KEY: $TOKEN" -d "mcp_findings_auto_resolve=1"
curl -s -X POST "$BASE/ingest_mcp_findings" -H "Content-Type: application/json" -H "X-SIMPLEMDM-API-KEY: $TOKEN" \
  -d '{"source":"settings_test4","replace":true,"findings":[]}'
```

Expected JSON: `"resolved":1`.

- [ ] **Step 14: Clean up test rows**

```bash
sqlite3 <repo-root>/app/db/db.sqlite \
  "DELETE FROM simplemdm_mcp_finding WHERE source IN ('settings_test','settings_test2','settings_test3','settings_test4');"
```

- [ ] **Step 15: Commit**

```bash
cd <repo-root>
git add local/modules/simplemdm/simplemdm_controller.php
git commit -m "feat(simplemdm): add mcp_findings_enabled/metadata_max_bytes/auto_resolve admin settings"
```

---

### Task 2: Admin UI — settings panel

**Files:**
- Modify: `views/simplemdm_admin.php` — add a new panel HTML block after the existing "Event Settings" panel (which closes at line 850, immediately before the "Manual / Outside-Module Access" panel opens at line 852), add three lines to `renderConfig(data)` (currently starts at line 1777; insert alongside the existing `client_reporter_*` populate lines around 1809-1816), add a new `submit` handler (insert alongside the existing form-submit handlers, e.g. immediately after the `#simplemdm-widget-form` handler ending at line 2818, before line 2820's `#simplemdm-advanced-form` handler)
- Test: manual (browser check via curl/`get_config` round-trip, plus a live page load)

**Interfaces:**
- Consumes: `get_config()`'s JSON response (Task 1) — reads `mcp_findings_enabled`, `mcp_findings_metadata_max_bytes`, `mcp_findings_auto_resolve` keys, all guaranteed present by Task 1's default-fill block.
- Produces: nothing consumed by later tasks — Task 3 (docs) does not depend on UI internals, only on the settings' existence and behavior (already fully described by Task 1).

- [ ] **Step 1: Add the new panel HTML**

Insert immediately after line 850 (the closing `</div>` of the Event Settings panel), before line 852 (`<div class="panel panel-default ... data-collapsible="manual"`):

```html
            <div class="panel panel-default simplemdm-modern-widget simplemdm-admin-collapsible" data-collapsible="mcpfindings" data-default-open="0">
                <div class="panel-heading" data-collapsible-toggle="mcpfindings">
                    <div class="simplemdm-admin-heading-wrap">
                        <div class="simplemdm-admin-heading-main">
                            <h3 class="panel-title"><i class="fa fa-flag"></i> MCP Findings Settings</h3>
                            <div class="simplemdm-admin-heading-summary" id="summary-mcpfindings">Enable/disable, metadata size cap, and auto-resolve behavior</div>
                        </div>
                        <span class="simplemdm-admin-heading-toggle" id="toggle-mcpfindings">Expand</span>
                    </div>
                </div>
                <div class="panel-body" data-collapsible-body="mcpfindings" style="display:none;">
                    <p class="text-muted">Controls for the MCP findings ingest/read/admin-action routes (<code>ingest_mcp_findings</code>, <code>get_mcp_findings</code>, and the acknowledge/resolve/ignore/suppress admin actions).</p>
                    <form id="simplemdm-mcpfindings-form">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="mcp_findings_enabled" name="mcp_findings_enabled" value="1">
                                Enable MCP findings ingest, read, and admin-action routes
                            </label>
                            <p class="help-block">When off, <code>ingest_mcp_findings</code>, <code>get_mcp_findings</code>, and the acknowledge/resolve/ignore/suppress routes all return a 403 disabled error. The dashboard widget stays visible and shows its normal "failed to load" message.</p>
                        </div>
                        <div class="form-group">
                            <label for="mcp_findings_metadata_max_bytes">Metadata Max Bytes</label>
                            <input type="number" min="1024" step="1" class="form-control" id="mcp_findings_metadata_max_bytes" name="mcp_findings_metadata_max_bytes" placeholder="65536">
                            <p class="help-block">Maximum size (in characters) of each finding's <code>data</code> field. Larger payloads are truncated at ingest time.</p>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="mcp_findings_auto_resolve" name="mcp_findings_auto_resolve" value="1">
                                Enable complete-scan auto-resolve
                            </label>
                            <p class="help-block">When off, a complete scan (<code>replace: true</code>) never auto-resolves findings absent from the scan, regardless of what the push request sends.</p>
                        </div>
                        <button type="submit" class="btn btn-primary">Save MCP Findings Settings</button>
                        <span id="mcpfindings-save-status" style="margin-left: 10px;"></span>
                    </form>
                </div>
            </div>

```

- [ ] **Step 2: Populate on load**

In `renderConfig(data)`, immediately after the existing line `$('#client_reporter_allowed_fact_keys_json').val(data.client_reporter_allowed_fact_keys_json || '');` (line 1813), insert:

```javascript
        $('#mcp_findings_enabled').prop('checked', String(data.mcp_findings_enabled || '1') === '1');
        $('#mcp_findings_metadata_max_bytes').val(pickValue(data.mcp_findings_metadata_max_bytes, '65536'));
        $('#mcp_findings_auto_resolve').prop('checked', String(data.mcp_findings_auto_resolve || '1') === '1');
```

(`pickValue` is already defined earlier in the same `renderConfig` function, at line 1786-1788 — reuse it, do not redefine it.)

- [ ] **Step 3: Add the submit handler**

Insert immediately after line 2818 (the closing `});` of the `#simplemdm-widget-form` submit handler), before line 2820 (`$('#simplemdm-advanced-form').on('submit', ...)`):

```javascript

    $('#simplemdm-mcpfindings-form').on('submit', function(e) {
        e.preventDefault();
        $('#mcpfindings-save-status').text('Saving...').removeClass().addClass('text-info');

        var payload = {
            mcp_findings_enabled: $('#mcp_findings_enabled').is(':checked') ? '1' : '0',
            mcp_findings_metadata_max_bytes: String($('#mcp_findings_metadata_max_bytes').val() || '65536'),
            mcp_findings_auto_resolve: $('#mcp_findings_auto_resolve').is(':checked') ? '1' : '0'
        };

        $.post(appUrl + '/module/simplemdm/save_config', payload, function(data) {
            if (data.status === 'success') {
                setFormStatus('#mcpfindings-save-status', 'Saved successfully!', 'text-success', 3000);
            } else {
                setFormStatus('#mcpfindings-save-status', 'Error: ' + (data.message || 'Unknown'), 'text-danger');
            }
        }, 'json').fail(function(xhr) {
            var msg = 'Request failed';
            if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            }
            setFormStatus('#mcpfindings-save-status', 'Error: ' + msg, 'text-danger');
        });
    });
```

- [ ] **Step 4: Verify PHP syntax**

```bash
docker compose -f <repo-root>/docker-compose.yml exec munkireport php -l /var/munkireport/local/modules/simplemdm/views/simplemdm_admin.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 5: Verify the panel renders and round-trips**

Use this skill's project browse tooling (or any available headless/browser tool) to load `http://localhost:8888/index.php?/module/simplemdm/admin`, then:
- Confirm a panel titled "MCP Findings Settings" is present in the panel list, collapsed by default (matching `data-default-open="0"`).
- Click its heading to expand it; confirm the checkbox, number input, and second checkbox render with their labels and help text.
- Confirm the checkbox states and number-input value reflect the current saved config (from Task 1's Step 11-13 verification, these should be back at their defaults: enabled checked, metadata `65536`, auto-resolve checked — if Task 1's cleanup wasn't run, defaults may differ; note this in your report but it's not a bug).
- Uncheck "Enable MCP findings...", click "Save MCP Findings Settings", confirm the status line shows "Saved successfully!".
- Reload the page, expand the panel again, confirm the checkbox is now unchecked (state persisted).
- Re-check it and save again to restore the default (enabled) state before finishing.

- [ ] **Step 6: Commit**

```bash
cd <repo-root>
git add local/modules/simplemdm/views/simplemdm_admin.php
git commit -m "feat(simplemdm): add MCP Findings Settings panel to admin UI"
```

---

### Task 3: Documentation update

**Files:**
- Modify: `docs/API_REFERENCE.md` (Admin Configuration or a new subsection; note the three new settings and their effect on the ingest/read/admin-action routes)
- Modify: `CHANGELOG.md` (add new entry)
- Modify: `README.md` (one-paragraph mention)

**Interfaces:**
- Consumes: nothing (docs only) — describes the exact behavior implemented in Tasks 1-2.
- Produces: nothing consumed by later tasks — this is the last task in this plan.

- [ ] **Step 1: Add a settings table to `docs/API_REFERENCE.md`**

Append a new subsection after the existing "## 12) MCP Findings Admin Actions" section's "### Interaction with the ingest lifecycle" subsection (added in the prior slice), titled "### Admin settings":

```markdown

### Admin settings

Three settings control MCP findings behavior, managed via the module's `save_config`/`get_config` routes (same mechanism as all other module settings) or the "MCP Findings Settings" panel in the admin UI:

| Setting | Default | Effect |
|---|---|---|
| `mcp_findings_enabled` | `1` | When `0`: `ingest_mcp_findings`, `get_mcp_findings`, and the four admin action routes (`acknowledge_mcp_finding`, `resolve_mcp_finding`, `ignore_mcp_finding`, `suppress_mcp_finding`) all return `403 {"status":"error","message":"MCP findings are disabled"}`. The dashboard widget keeps rendering and shows its existing "Failed to load MCP findings." fallback. |
| `mcp_findings_metadata_max_bytes` | `65536` | Maximum size (characters) of each finding's `data` field in `ingest_mcp_findings`; larger payloads are truncated. Replaces a previously-hardcoded 4096-char cap. |
| `mcp_findings_auto_resolve` | `1` | Global kill-switch on `ingest_mcp_findings`' complete-scan auto-resolve sweep. When `0`, the sweep never runs — even if the request sends `replace: true` — overriding the per-request flag. When `1` (default), the existing `replace`-flag-driven behavior is unchanged. |
```

- [ ] **Step 2: Add a `CHANGELOG.md` entry**

Add to the top of the `## [Unreleased]` section:

```markdown
### Added
- Three admin settings for MCP findings: `mcp_findings_enabled` (disable ingest/read/admin-action routes), `mcp_findings_metadata_max_bytes` (configurable `data` field truncation cap, now defaulting to 65536 instead of a hardcoded 4096), and `mcp_findings_auto_resolve` (global kill-switch overriding the per-request `replace` flag's auto-resolve behavior). Managed via the existing `save_config`/`get_config` routes and a new "MCP Findings Settings" panel in the admin UI.
```

- [ ] **Step 3: Update `README.md`**

Immediately after the paragraph added in the admin-action-routes slice (search for "admin action routes" or "acknowledge_mcp_finding" in README.md), add:

```markdown
Ingest, read, and admin-action behavior for MCP findings can be tuned via three admin settings (`mcp_findings_enabled`, `mcp_findings_metadata_max_bytes`, `mcp_findings_auto_resolve`) — see the "MCP Findings Settings" panel in the module's admin UI, or `docs/API_REFERENCE.md` for the full effect of each.
```

- [ ] **Step 4: Commit**

```bash
cd <repo-root>
git add local/modules/simplemdm/README.md local/modules/simplemdm/CHANGELOG.md local/modules/simplemdm/docs/API_REFERENCE.md
git commit -m "docs(simplemdm): document MCP findings admin settings"
```

---

## Explicitly Out of Scope for This Slice

- `mcp_findings_event_enabled`, `mcp_findings_event_min_severity`, `mcp_findings_event_mode` — Event widget integration doesn't exist (PRD §13).
- `mcp_findings_retention_days` — no retention/purge job exists.
- `mcp_findings_allow_success` — no `success` severity value exists in this module's taxonomy.
- `mcp_findings_require_token` — sync-token auth stays hardcoded-required; no alternative auth path exists.
- `mcp_findings_generic_ready` — no-op placeholder, nothing reads it.
- Any widget (`views/simplemdm_mcp_findings_widget.php`) code changes — its existing error handling already covers the disabled-state response.

## Self-Review Notes

- **Spec coverage check:** Design spec's three settings table → Task 1 Steps 1-8 (helper, gating, truncation, kill-switch, storage). Route gating scope (ingest + read + admin-actions, not widget) → Task 1 Steps 2-4, verified in Step 11. Metadata cap default change (4096→65536) → Task 1 Step 5, verified in Step 12. Auto-resolve kill-switch semantics (overrides `replace`) → Task 1 Step 6, verified in Step 13. Admin UI panel → Task 2 in full. "Explicitly deferred" settings list → confirmed absent from every task's `$config_keys`/HTML/docs additions. All design sections have a corresponding task step.
- **Backward compatibility check:** `get_config()`'s new default-fill block means existing installs with no stored value for these three keys get the new defaults (`1`, `65536`, `1`) — for `mcp_findings_enabled` and `mcp_findings_auto_resolve` this preserves today's behavior exactly (both were implicitly "on" before this slice existed). For `mcp_findings_metadata_max_bytes`, this is a deliberate, documented behavior change (was hardcoded 4096, is now 65536) — flagged in Global Constraints and the CHANGELOG entry, not a silent regression.

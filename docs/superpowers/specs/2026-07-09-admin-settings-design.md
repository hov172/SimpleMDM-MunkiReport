# Admin Settings (Scoped to PRD §11.5) — Design

**Status:** Approved
**Date:** 2026-07-09
**PRD reference:** `SimpleMDM_MCP_MunkiReport_Findings_Platform_PRD_SDS_v4` §11.5 (Admin settings table), §11.2 ("Admin settings for thresholds, enabling/disabling Event summaries, severity thresholds, retention, and suppression rules").

## Context

PRD §11.5 lists ten admin settings for the MCP findings feature. Several gate features that don't exist yet in this codebase: Event widget integration (`mcp_findings_event_enabled`, `mcp_findings_event_min_severity`, `mcp_findings_event_mode` — PRD §13, not built), a retention/purge job (`mcp_findings_retention_days` — no purge job exists), a `success` severity (`mcp_findings_allow_success` — this module's severity taxonomy is only `danger`/`warning`/`info`, confirmed in `ingest_mcp_findings()` and the widget). Adding these as real settings now would be inert — visible in the admin UI but doing nothing, which is worse than not having them.

This slice implements only the three PRD §11.5 settings that have a real, immediate effect on already-built code, using the module's existing settings infrastructure (`Simplemdm_config_model`, `get_config_value()`, `get_config()`, `save_config()`, `views/simplemdm_admin.php`).

## Scope

**In scope — three settings:**

| Setting | Default | Effect |
|---|---|---|
| `mcp_findings_enabled` | `1` | When `0`: `ingest_mcp_findings`, `get_mcp_findings`, and the four admin action routes (`acknowledge_mcp_finding`, `resolve_mcp_finding`, `ignore_mcp_finding`, `suppress_mcp_finding`) all return `403 {"status":"error","message":"MCP findings are disabled"}`. |
| `mcp_findings_metadata_max_bytes` | `65536` | Replaces the hardcoded `4096`-char truncation cap on the `data` field in `ingest_mcp_findings()` (`simplemdm_controller.php:6487-6488`). This is a real behavior change from today (was hardcoded 4096) — the new out-of-the-box default is the PRD's `65536`, not today's `4096`, per explicit decision during design. |
| `mcp_findings_auto_resolve` | `1` | Global kill-switch on the auto-resolve sweep in `ingest_mcp_findings()` (`simplemdm_controller.php:6559-6567`). When `0`, the sweep never runs — **even if the request sends `replace: true`** — overriding the existing per-request `$replace` flag (`simplemdm_controller.php:6549`). When `1` (default), today's `replace`-flag-driven behavior is unchanged. |

**Explicitly deferred (not implemented — no inert settings added):**
- `mcp_findings_event_enabled`, `mcp_findings_event_min_severity`, `mcp_findings_event_mode` — Event widget integration doesn't exist (PRD §13).
- `mcp_findings_retention_days` — no retention/purge job exists.
- `mcp_findings_allow_success` — no `success` severity value exists in this module's taxonomy (`danger`/`warning`/`info` only).
- `mcp_findings_require_token` — sync-token auth stays hardcoded-required for these routes; no alternative auth path exists (a deliberate decision from the admin-action-routes slice). Adding a toggle to disable auth entirely, with nothing built to replace it, is out of scope and risky.
- `mcp_findings_generic_ready` — a forward-compat no-op placeholder; nothing in this codebase reads a `source_module`/`source` split that this would gate, so it has no effect either way.

Each deferred setting belongs in the same later slice that builds the feature it gates, not added now as a non-functional placeholder.

## Design

### Settings storage — reuse existing infrastructure

No new tables, no new model. Three new entries are added to `save_config()`'s existing `$config_keys` allowlist array (`simplemdm_controller.php:3754-3793`) and `get_config()`'s existing default-fill pattern (`simplemdm_controller.php:3606-3696`), following the module's established conventions exactly:

- `mcp_findings_enabled`: boolean-style key, validated the same way as `client_reporter_enabled`/`supplemental_enabled` (`$value === '1' ? '1' : '0'`).
- `mcp_findings_metadata_max_bytes`: integer key with a floor, validated the same way as `client_reporter_max_payload_bytes` (`$v = (int)$value; if ($v < 1024) { $v = 1024; }`) — 1024-byte floor prevents an admin from accidentally truncating all metadata to near-zero.
- `mcp_findings_auto_resolve`: boolean-style key, same pattern as `mcp_findings_enabled`.

`get_config()` gains a default-fill block (matching the existing pattern at `simplemdm_controller.php:3661-3667` for `event_stale_threshold_hours` etc.):

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

### Route gating (`mcp_findings_enabled`)

A private helper checks the setting and returns a bool:

```php
private function mcp_findings_enabled()
{
    return $this->get_config_value('mcp_findings_enabled', '1') !== '0';
}
```

Each gated route calls it immediately after its existing auth check (token/session check stays first — an unauthenticated caller should still get 401/403 for auth, not leak whether the feature is enabled). If disabled, respond and return before touching the database:

```php
if (! $this->mcp_findings_enabled()) {
    jsonView(['status' => 'error', 'message' => 'MCP findings are disabled'], 403);
    return;
}
```

Applied to: `ingest_mcp_findings()`, `get_mcp_findings()`, `acknowledge_mcp_finding()`/`resolve_mcp_finding()`/`ignore_mcp_finding()`/`suppress_mcp_finding()` (via the shared `applyFindingStatusAction()` helper, so it's one call site covering all four routes, not four separate checks).

**Widget impact:** No widget code changes. `views/simplemdm_mcp_findings_widget.php`'s `$.getJSON(...).fail(...)` handler already renders `<p class="text-danger text-center">Failed to load MCP findings.</p>` on any non-2xx response (confirmed by reading the current widget JS) — a 403 from a disabled `get_mcp_findings` triggers this existing fallback with no changes needed.

### Metadata cap (`mcp_findings_metadata_max_bytes`)

`simplemdm_controller.php:6487-6488` currently:

```php
if (strlen($extra) > 4096) {
    $extra = substr($extra, 0, 4096);
}
```

becomes:

```php
$metadataMaxBytes = (int) $this->get_config_value('mcp_findings_metadata_max_bytes', 65536);
if (strlen($extra) > $metadataMaxBytes) {
    $extra = substr($extra, 0, $metadataMaxBytes);
}
```

### Auto-resolve kill-switch (`mcp_findings_auto_resolve`)

`simplemdm_controller.php:6549-6567` currently computes `$replace` from the request body, then runs the auto-resolve sweep `if ($replace)`. The kill-switch gates the sweep itself, independent of `$replace`:

```php
$replace = ! isset($data['replace']) || $data['replace'] !== false;
$autoResolveEnabled = $this->get_config_value('mcp_findings_auto_resolve', '1') !== '0';

// ... existing all-findings-failed-validation guard, unchanged ...

$resolved = 0;
if ($replace && $autoResolveEnabled) {
    // existing sweep body, unchanged
}
```

When `mcp_findings_auto_resolve` is `0`, `$resolved` stays `0` and the response's `resolved` field correctly reflects that nothing was auto-resolved — no response-shape change, just a code path that never executes the sweep.

### Admin UI (`views/simplemdm_admin.php`)

Three new form fields added to the existing settings form, in the same section as the other MCP-findings-adjacent settings if one exists, otherwise a new fieldset following the visual pattern already used for other boolean/integer settings in this view (checkbox for booleans, number input for the integer). Exact markup will mirror whatever pattern an existing field like `client_reporter_enabled` or `supplemental_default_stale_after_minutes` already uses in this file, to keep the page visually consistent — the implementation task will read the surrounding markup before adding the new fields rather than inventing new styling.

### Testing approach

Same as prior slices (no PHPUnit coverage for controllers in this module): live curl + `sqlite3` verification against the running `munkireport-local` Docker container, covering:
- Default state (`mcp_findings_enabled` unset/`1`): all gated routes work normally.
- Set `mcp_findings_enabled=0` via `save_config`; verify `ingest_mcp_findings`, `get_mcp_findings`, and one admin action route (e.g. `acknowledge_mcp_finding`) all return 403 with the disabled message; verify `get_config` reflects `mcp_findings_enabled=0`. Reset to `1` afterward.
- Push a finding with a `data` payload larger than 4096 bytes but smaller than 65536; verify it is NOT truncated at 4096 (confirms the new default takes effect over the old hardcoded value). Set `mcp_findings_metadata_max_bytes=100` and push a finding with a 200-byte `data` payload; verify it truncates at 100.
- Set `mcp_findings_auto_resolve=0`; push a finding, then push a `replace:true` payload that omits it; verify the finding stays in its prior status (not resolved) and the response's `resolved` count is `0`. Set `mcp_findings_auto_resolve=1` (restore default) and repeat; verify it resolves as before.
- Admin UI: load `views/simplemdm_admin.php` in a browser, confirm the three new fields render and round-trip through save/reload correctly.

## Open questions / risks

None outstanding — all scoping and behavior decisions (which settings to implement, `require_token` exclusion, auto-resolve kill-switch semantics, metadata-cap default change, enabled-gate route scope) were resolved during brainstorming.

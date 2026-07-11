# MCP Findings Retention Setting — Design

**Date:** 2026-07-12
**PRD reference:** `SimpleMDM_MCP_MunkiReport_Findings_Platform_PRD_SDS_v4` §11.5 — `mcp_findings_retention_days`, default `0`, "0 means retain indefinitely." Deliberately deferred by the 2026-07-09 admin-settings slice because no purge job existed; this slice builds the purge, so the setting stops being inert.

## Problem

MCP findings accumulate forever. Every resolved, ignored, or suppressed finding stays in `simplemdm_mcp_finding` indefinitely — there is no retention control and no cleanup mechanism anywhere in the module. Large fleets with frequent scans grow the table without bound, and old finding rows retain device security posture data (a data-minimization concern noted in SECURITY.md's threat framing).

## Decision summary

One new admin setting, one new model helper, one call site in ingest, no new routes, no schema changes.

| Piece | Decision |
|---|---|
| Setting key | `mcp_findings_retention_days` (PRD-literal name) |
| Default | `'0'` — retain indefinitely (purge disabled) |
| Clamp | `max(0, (int) $value)`, stored as string like every other config value |
| What gets purged | Rows with **non-active status** (`resolved`, `ignored`, `suppressed`) whose `last_seen_at` is older than N days. `reported_at` substitutes when `last_seen_at` is null (pre-lifecycle-migration rows). |
| Never purged | Rows in `ACTIVE_STATUSES` (`open`, `acknowledged`, `in_progress`), regardless of age. |
| Trigger | Lazily at the end of `ingest_mcp_findings`, after the auto-resolve sweep. Best-effort: a purge failure is caught and never fails the ingest. |
| Reporting | `purged` count added to the ingest JSON response (0 when disabled or nothing matched). |

## Why these semantics

**Active findings are never deleted.** An open/acknowledged/in_progress finding is a live problem statement; deleting it by age would silently hide real issues, and the next scan would re-create it as a fresh `open` row — losing acknowledged/in-progress state and `occurrence_count` history. Ingest's auto-resolve sweep (not retention) is the mechanism that closes findings which stop occurring.

**Staleness is measured by `last_seen_at`, and that is safe for suppressions.** `computeUpsertUpdate` (model, line ~108) bumps `last_seen_at` on *every* re-observed finding, including suppressed/ignored ones (their status stays untouched, `kind: 'unchanged'`). So a suppressed finding that still occurs keeps a fresh `last_seen_at` and is never purged — purging cannot undo an admin's active suppression. A suppressed finding whose condition stopped occurring N+ days ago is purged, and stays gone because nothing re-reports it. Verified against the real upsert code, not assumed.

**Lazy trigger, no new route.** The module has no PHP scheduler — every maintenance behavior is request-triggered. Findings data only grows when publishers push, so purging on push runs exactly as often as needed. A dedicated purge route (rejected) would add auth surface, `provides.yml` changes, and §19 scheduling docs for no benefit; hooking the Python sync worker (rejected) couples retention to SimpleMDM sync, which is unrelated to MCP findings.

## Components

### 1. Model: `Simplemdm_mcp_finding_model::purgeExpired($retentionDays, $now)`

New public static on `simplemdm_mcp_finding_model.php`, alongside the other pure/query helpers.

```php
/**
 * Deletes non-active findings not seen within the retention window.
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

Notes:
- `$now` is the ingest's existing `gmdate('c')` string, so the cutoff derives from the same clock as the rest of the request.
- Timestamps are stored as ISO-8601 UTC strings (`gmdate('c')`) in `dateTime` columns; lexicographic/string comparison via `where(... '<' ...)` is correct in both MySQL and the SQLite test harness because all values share the same format and zone.
- Delete is hard (row removal), matching the intent of "retention." No soft-delete column exists or is added.

### 2. Controller: setting registration + ingest call site

All in `simplemdm_controller.php`, following each existing pattern exactly:

- **`get_config()` defaults block** (~line 3705–3724): add `if (! isset($config['mcp_findings_retention_days'])) { $config['mcp_findings_retention_days'] = '0'; }`.
- **`save_config()` whitelist** (`$config_keys`, ~3828–3832): add `'mcp_findings_retention_days'`.
- **`save_config()` clamp chain** (~3907–3933): `$v = max(0, (int) $value); $value = (string) $v;` — the `mcp_findings_event_warning_threshold` pattern with a 0 floor instead of 1.
- **Ingest call site** (end of `ingest_mcp_findings`, after the auto-resolve block at ~6663, before the summary-event try/catch):

```php
$purged = 0;
try {
    $retentionDays = (int) $this->get_config_value('mcp_findings_retention_days', '0');
    $purged = Simplemdm_mcp_finding_model::purgeExpired($retentionDays, $now);
} catch (\Throwable $e) {
    // Best-effort: retention purge must never fail an ingest.
}
```

- **Response**: add `'purged' => $purged,` to the ingest `jsonView` payload (after `'resolved'`).

Placement rationale: running after the upsert loop and auto-resolve sweep means rows touched by this request always carry `last_seen_at = $now` and can never be purged by the same request; rows auto-resolved by this request get `resolved_at = $now` but keep their older `last_seen_at` — they become purge-eligible only N days after they were last actually observed, which is the intended reading of retention.

### 3. Admin UI: one numeric field

`views/simplemdm_admin.php`, MCP Findings Settings panel (~863–893):

- Input row after Metadata Max Bytes, same markup pattern: `<input type="number" min="0" step="1" class="form-control" id="mcp_findings_retention_days" name="mcp_findings_retention_days" placeholder="0">` with label `Retention Days` and help text "Days to keep resolved/ignored/suppressed findings after they were last seen. 0 keeps them forever."
- Populate-from-config JS (~1864–1868): `$('#mcp_findings_retention_days').val(pickValue(data.mcp_findings_retention_days, '0'));`
- Saved by the existing panel `$.post` to `save_config`; no JS changes beyond the populate line.

### 4. Tests

`tests/Unit/McpFindingPurgeDbTest.php` (new, DB-backed, follows `McpFindingUpsertDbTest`'s setUp/truncate pattern against the in-memory SQLite from `tests/bootstrap.php`):

1. Resolved row with `last_seen_at` older than the window → purged.
2. Resolved row with fresh `last_seen_at` → kept.
3. Open/acknowledged/in_progress rows with ancient `last_seen_at` → kept (active never purged).
4. Suppressed and ignored rows older than the window → purged.
5. `retention_days = 0` (and negative) → no-op, returns 0, nothing deleted.
6. Non-active row with `last_seen_at = null` and old `reported_at` → purged via the fallback; with recent `reported_at` → kept.
7. Return value equals the number of rows actually deleted.

The `save_config` clamp and admin-view field are not unit-testable here (controller/view have no PHPUnit coverage — established gap); they get manual steps in `docs/TESTING.md` instead, mirroring the existing metadata_max_bytes floor check.

### 5. Documentation

- `docs/API_REFERENCE.md` (~1014–1018): add the `mcp_findings_retention_days | 0 | ...` row to the admin settings table; document the `purged` field in the `ingest_mcp_findings` response.
- `README.md` (~109): "five admin settings" → "six", with a clause naming retention.
- `docs/DEVELOPER_GUIDE.md` (~190–192): add the key to the MCP Findings Settings panel bullet list.
- `docs/TESTING.md` (~629–634): manual steps — clamp check (enter -5, expect saved 0) and a purge round-trip (set retention 1, seed an old resolved finding, push, verify deletion + `purged` count).
- `docs/SECURITY.md`: one line noting retention as the data-minimization control for stored finding history.
- `CHANGELOG.md`: `[Unreleased]` → Added.

## Out of scope

- Per-source or per-severity retention windows (YAGNI; PRD specifies one global setting).
- A manual "purge now" admin button or route.
- Retention for other module tables (events, config, device cache) — findings only, per PRD.
- Any schema change or new index. The purge query filters on `status` (indexed) and the table is bounded by exactly this feature; a dedicated composite index can come later if a real fleet shows slow purges.

## Error handling

- Purge wrapped in try/catch inside ingest; failure logs nothing today (matches the summary-event best-effort convention) and never affects the ingest result.
- `purgeExpired` itself validates `$retentionDays <= 0` as a no-op, so a mis-set config value can never mass-delete: negative and zero both disable, and the clamp prevents negatives from persisting anyway.

## Acceptance criteria

1. Fresh install shows Retention Days = 0 in the admin panel; `get_config` returns `'0'` for the key with no row present.
2. Saving a negative value persists `'0'`; saving `30` persists `'30'`.
3. With retention 30: a resolved finding last seen 31 days ago is deleted on the next ingest to any source; an open finding last seen 31 days ago survives; a suppressed finding re-observed today survives.
4. With retention 0: nothing is ever deleted; ingest response reports `purged: 0`.
5. Full PHPUnit suite green in the `munkireport-local` container.

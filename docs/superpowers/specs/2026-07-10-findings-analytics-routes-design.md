# Findings Analytics Routes (Slice B) — Design

**Status:** Approved
**Date:** 2026-07-10
**PRD reference:** `SimpleMDM_MCP_MunkiReport_Findings_Platform_PRD_SDS_v4` §15.3 (Admin/status routes table: `get_mcp_finding_stats` — "Return counts by severity/status/category/source"; `export_mcp_findings` — "Export CSV/JSON"; `get_mcp_scan_status` — "Show last scan, last ingest, failures, and counts").

## Context

This is Slice B, built on top of Slice A (already merged — `category` field + fingerprint rescoping). It implements the three read-only routes PRD §15.3 lists alongside the admin-action routes, deliberately excluded from that earlier slice since they're analytics/reporting routes, not status-mutating actions.

`get_mcp_scan_status`'s PRD spec includes "failures," but nothing in this codebase logs failed/rejected `ingest_mcp_findings` calls — the route either succeeds or returns a synchronous validation error to the caller; nothing persists. Building failure tracking would require a new table and wiring into the existing ingest route — comparable in size to Slice A, and out of scope for "add 3 read-only routes." The user chose to derive everything else from existing finding rows and simply omit failure data from the response.

## Scope

**In scope — three GET routes:**

1. `get_mcp_finding_stats` — four independent count breakdowns.
2. `export_mcp_findings` — CSV/JSON bulk export.
3. `get_mcp_scan_status` — per-source last-scan summary.

**Explicitly out of scope:**
- Ingest-attempt/failure logging (a new table + ingest wiring) — `get_mcp_scan_status` omits "failures" entirely rather than stubbing it to zero.
- Any admin-action or write routes — all three routes in this slice are read-only.
- `risk_score`, `device_id`, `udid`, `device_name` — still deferred from the very first slice.
- Any UI/widget changes.

## Design

### `get_mcp_finding_stats`

Four independent breakdowns in one response:

```json
{
  "by_status":   { "open": N, "acknowledged": N, "in_progress": N, "resolved": N, "ignored": N, "suppressed": N },
  "by_severity": { "danger": N, "warning": N, "info": N },
  "by_category": { "FileVault": N, "...": N },
  "by_source":   { "stale_devices": N, "...": N }
}
```

- `by_status`: unconditional — counts every row regardless of status, mirroring `get_mcp_findings`' existing `status_totals` field's precedent (already global/unfiltered).
- `by_severity`, `by_category`, `by_source`: each scoped to `ACTIVE_STATUSES` only (`open`/`acknowledged`/`in_progress`), mirroring `get_mcp_findings`' existing `totals` field's precedent.
- `category`/`source` group keys omit rows where that field is null/empty (a finding with no category doesn't get a `""` bucket).
- Optional query filters `source`, `category`, `since`, `scan_id` (same param names, same parsing as `get_mcp_findings`) further narrow ALL FOUR breakdowns uniformly. No `severity`/`status` filter params — those are the breakdown axes themselves, not filters.

### `export_mcp_findings`

Same filter set as `get_mcp_findings` (`severity`, `status`, `source`, `category`, `since`, `scan_id` — `status` defaults to active-only, identical to `get_mcp_findings`' existing default behavior) plus a new `format` param (`csv` or `json`, default `json`).

No offset-based pagination — this route is a single full pull, not a paginated browse. Hard cap: 10,000 rows. If the result set exceeds the cap, the response is truncated to the first 10,000 (ordered the same way `get_mcp_findings` orders — `id` descending) and a `truncated: true` flag is included (JSON: a top-level key; CSV: an HTTP response header, e.g. `X-Export-Truncated: true`, since a CSV body can't carry a JSON-shaped flag).

CSV output: one row per finding, columns matching the same fields `get_mcp_findings` already returns per-row (`id`, `source`, `serial_number`, `finding_type`, `category`, `severity`, `status`, `message`, `data`, `scan_id`, `occurrence_count`, `reported_at`, `first_seen_at`, `last_seen_at`, `resolved_at`). Response sets `Content-Type: text/csv` and `Content-Disposition: attachment; filename="mcp_findings_export_<UTC-timestamp>.csv"` for direct-download friendliness when an admin hits the route from a browser.

JSON output: same shape `get_mcp_findings` already uses for its `findings` array (reuses that exact per-row structure), just without the `limit`/`offset` pagination semantics — always returns everything up to the 10,000 cap in one response.

### `get_mcp_scan_status`

Grouped by `source` (each MCP publisher/source has its own independent scan cadence):

```json
{
  "sources": [
    {
      "source": "stale_devices",
      "last_scan_id": "scan_20260707T222314Z_a1b2c3d4",
      "last_ingest_at": "2026-07-07T22:23:14+00:00",
      "counts": { "danger": N, "warning": N, "info": N, "total": N }
    }
  ]
}
```

- `last_scan_id`: the `scan_id` of the most recently-ingested finding for that source (max `reported_at`, tie-broken by max `id`).
- `last_ingest_at`: that finding's `reported_at`.
- `counts`: severity breakdown (plus a `total`) scoped to ONLY the findings that carry that specific `last_scan_id` — i.e., "what did the last scan report," not "what's currently active for this source" (which is what `get_mcp_finding_stats` with a `source` filter would answer instead).
- Optional `source` query param scopes the response to one source (returns a `sources` array with zero or one entry, same shape, for consistency rather than a special single-object response shape).
- No "failures" field — omitted entirely per the scope decision above, not stubbed to `0` or `null` (a stubbed zero would misleadingly imply failure tracking exists and simply found none).

### Testing approach

Same as every prior slice (no PHPUnit for this module's controllers): live curl + `sqlite3` verification against the running `munkireport-local` Docker container, covering:
- `get_mcp_finding_stats`: unfiltered call matches manually-computed counts from `sqlite3`; a `source`/`category` filter narrows all four breakdowns consistently; a category-less finding doesn't appear as a `""` key in `by_category`.
- `export_mcp_findings`: `format=json` matches `get_mcp_findings`' per-row shape for the same filter set; `format=csv` produces a valid CSV with a header row and correct `Content-Type`/`Content-Disposition`; pushing more than 10,000 findings (or a smaller test threshold substituted for practicality) triggers `truncated: true`.
- `get_mcp_scan_status`: two sequential scans from the same source show `last_scan_id` advancing to the newer one, with `counts` reflecting only the newer scan's findings, not the union of both.

## Open questions / risks

None outstanding — auth model, response conventions, and scope boundaries all inherited from prior slices' established patterns.

# Category Field + Fingerprint Rescoping — Design

**Status:** Approved
**Date:** 2026-07-09
**PRD reference:** `SimpleMDM_MCP_MunkiReport_Findings_Platform_PRD_SDS_v4` §16.1 ("fingerprint ... Unique per source/serial/type/category"), §15.2 (`get_mcp_findings` filter example includes `category=FileVault`), §15.3 (`get_mcp_finding_stats`: "Return counts by severity/status/category/source").

## Context

This is Slice A of a two-slice plan. The user asked to plan the three read-only routes PRD §15.3 lists alongside the admin-action routes (`get_mcp_finding_stats`, `export_mcp_findings`, `get_mcp_scan_status`). During brainstorming it became clear `get_mcp_finding_stats`'s spec ("counts by severity/status/category/source") depends on a `category` field this codebase deliberately deferred in the very first slice (along with `risk_score`/`device_id`/`udid`). The user chose to add `category` now, with fingerprint rescoping (matching PRD §16.1's original intent that the fingerprint is unique per `source/serial/type/category`, not just `source/serial/type`).

Adding `category` and rescoping the fingerprint is a real behavior change to the existing, already-merged ingest pipeline (the dedup contract changes), independent of and prerequisite to the three read-only routes (Slice B). The user chose to sequence these as two separate slices rather than one combined plan, matching how every prior slice in this project has worked (plan → implement → review → merge, one slice at a time).

## Scope

**In scope:**
- New `category` column on `simplemdm_mcp_finding`, nullable, stored as-given (case preserved), capped at 128 chars.
- Fingerprint formula becomes `hash('sha256', strtolower(source) . '|' . strtolower(serial_number) . '|' . strtolower(finding_type) . '|' . strtolower(category))`.
- Migration backfills existing rows' fingerprints using `category=''` (safe, non-breaking — see Compatibility below).
- `ingest_mcp_findings` accepts an optional `category` field per finding.
- `get_mcp_findings` gains a `category` query filter (comma-separated list, same pattern as `status`/`severity`/`source`).

**Explicitly out of scope (this slice):**
- `get_mcp_finding_stats`, `export_mcp_findings`, `get_mcp_scan_status` — Slice B, built on top of this slice's `category` column.
- `risk_score`, `device_id`, `udid`, `device_name` — still deferred, not part of this slice.
- Any UI/widget changes — the widget doesn't currently display category and this slice doesn't require it to.

## Design

### Migration

New nullable `category` string column on `simplemdm_mcp_finding`. Backfill recomputes every existing row's `fingerprint` using the new 4-field formula with `category=''` for all legacy rows (none have a category today). No change to the `(source, fingerprint)` unique index structure — only the fingerprint *values* change.

### Compatibility

This is the load-bearing safety property of this slice: an MCP publisher that does NOT send `category` in its ingest payload continues to hash against `category=''` both before and after this migration — so its dedup/reopen/auto-resolve behavior is byte-for-byte unchanged. Only publishers that start sending a real `category` value get more-specific fingerprints (a finding with the same `source`/`serial`/`finding_type` but a different `category` becomes a distinct row instead of colliding into one — matching PRD §16.1's stated intent, which this codebase's first slice had temporarily under-scoped).

### Model (`Simplemdm_mcp_finding_model`)

`computeFingerprint($source, $serialNumber, $findingType, $category = '')` — one new trailing parameter with a `''` default (so the method's public signature stays backward-compatible for any external caller, though there are none today). Hashed with the same `strtolower((string) $x)` treatment as every other field. `category` added to `$fillable`.

### Controller (`ingest_mcp_findings`)

- New optional per-finding `category` field: trimmed, capped at 128 chars — same length/trim treatment as `finding_type`.
- Case is preserved in storage (so `"FileVault"` displays as given), but lowercased only inside the fingerprint hash — mirrors how `finding_type` itself is already handled (display case preserved, hash normalized).
- Absent/empty `category` is stored as `null`; hashed as `''` in the fingerprint (mirrors `serial_number`'s existing nullable-cast-to-string pattern).

### Controller (`get_mcp_findings`)

New `category` query param, comma-separated list — exact same `where`/`whereIn` pattern already implemented for `status`, `severity`, and `source`. No other code change needed for `category` to appear in each finding's response row — `$row->toArray()` automatically includes any real column, including the new one.

### Testing approach

Same as every prior slice (no PHPUnit for this module's controllers): live curl + `sqlite3` verification against the running `munkireport-local` Docker container, covering:
- A finding pushed WITHOUT `category` before this migration lands, then re-pushed (same source/serial/finding_type) after the migration — confirms it still dedupes to the same row (fingerprint compatibility).
- Two findings with identical `source`/`serial_number`/`finding_type` but different `category` values — confirms they create two distinct rows, not one.
- `get_mcp_findings?category=X` filters correctly, including a comma-separated multi-category query.
- Category case preservation in storage vs. case-insensitive fingerprint matching (e.g. `"FileVault"` and `"filevault"` sent for the same device/finding_type dedupe to the same row, but the stored `category` value reflects whichever was sent — this is a nuance worth explicitly testing so a future reader isn't surprised).

## Open questions / risks

None outstanding — auth model, lookup semantics, and scope boundaries all inherited from prior slices' established patterns; the one novel design decision (fingerprint rescoping vs. store-only) was resolved during brainstorming in favor of matching the PRD's original intent.

# MCP Findings Widget — Scrolling + Category Grouping — Design

**Status:** Approved
**Date:** 2026-07-10
**PRD reference:** none — dashboard UX improvement, not a PRD-listed route/feature.

## Context

The MCP Findings dashboard widget (`views/simplemdm_mcp_findings_widget.php`) fetches `?limit=5` from `get_mcp_findings` and renders at most `findings.slice(0, maxRows)` (`maxRows = 5`) — so even when many findings exist, only the 5 most recent are ever visible, with a "Showing N of M findings" note but no way to see the rest.

The dashboard already has a generic mechanism for this exact situation: `markScrollableSimplemdmLists()` in `simplemdm_widget_modern_assets.php` auto-adds a `simplemdm-list-scroll` class (CSS: `max-height: 260px; overflow-y: auto`) to any `.simplemdm-modern-widget` with more than 12 rendered `.list-group-item`s. The MCP findings widget never reaches that threshold because its own render logic caps at 5 — the scrolling infrastructure exists and is unused by this widget.

Separately, the user asked for the widget to be "expandable by type of alerts" — clarified as: group findings into collapsible sections by `category` (FileVault, SIP, Compliance, etc.), while keeping the existing per-finding severity badge visible inside each group. `views/simplemdm_group_apps_widget.php` already has an established, reusable collapsible-section pattern (`data-section-toggle` / `.simplemdm-section-body` / delegated click handler / `slideToggle(160)` / `simplemdmReflowDashboardGrid()`) that this design reuses rather than inventing new markup or JS conventions.

## Scope

**In scope — one file, `views/simplemdm_mcp_findings_widget.php`:**

1. Raise the fetch limit from 5 to 100 (matches `get_mcp_findings`'s own server-side default; no route change needed).
2. Remove the `maxRows`-based render cap — render every fetched finding, grouped, not sliced.
3. Group findings client-side by `category` (empty/null → `"Uncategorized"` bucket).
4. Render each category as a collapsible section (header + count + severity mini-badges + expand/collapse toggle), reusing `simplemdm_group_apps_widget.php`'s existing `data-section-toggle`/`.simplemdm-section-body` pattern and its shared CSS/JS conventions verbatim — no new collapsible-section markup pattern introduced.
5. Groups containing at least one `danger`-severity finding start expanded; all other groups start collapsed.
6. Sort groups: any group containing a `danger` finding first, then by finding count descending, then alphabetically by category name.
7. Apply the existing `simplemdm-list-scroll` class to the widget's panel body statically (in the PHP template), reusing the existing shared CSS rule (`simplemdm_widget_modern_assets.php:666-671`) rather than depending on the dynamic 12-item threshold — guarantees scroll behavior regardless of whether the widget participates in the dynamic dashboard grid on a given page.
8. Replace the "Showing N of M findings" note: only shown when the fetch itself was truncated at the 100-item cap (`total > findings.length`), e.g. "Showing 100 of 137 findings."

**Explicitly out of scope:**

- No server/route changes — `get_mcp_findings` already supports `limit` up to 500; this widget only changes what it requests and how it renders the response.
- No new shared CSS/JS conventions — every visual/interaction pattern used here (scrollable list, collapsible section) already exists elsewhere in this module's views and is reused as-is.
- No change to the top-of-widget severity totals badge row, the per-finding severity badge, the device-link behavior, or the `data` tooltip — all unchanged.
- No persistence of expand/collapse state across page loads (matches `simplemdm_group_apps_widget.php`'s own behavior — recomputed fresh each render based on the danger-severity rule).

## Design

### Data flow (client-side JS, inside the widget's existing `$(document).on('appReady', ...)` block)

```
$.getJSON(get_mcp_findings + '?limit=100', function(data) {
  // totals badge row: unchanged

  var findings = data.findings || [];
  var groups = groupByCategory(findings);   // { categoryName: { findings: [...], counts: {danger,warning,info} } }
  var ordered = sortGroups(groups);         // danger-containing first, then count desc, then alpha

  ordered.forEach(function(group) {
    renderCategorySection(group);           // header + toggle + .simplemdm-section-body of .list-group-item rows
  });

  if (total > findings.length) {
    moreNote.text('Showing ' + findings.length + ' of ' + total + ' findings.').show();
  }
});
```

`groupByCategory`: buckets by `finding.category || 'Uncategorized'`. Each finding still renders via the SAME per-item markup the widget already builds (severity badge, finding_type, serial link, message, meta line) — only the wrapping structure (flat list → grouped sections) changes.

### Markup shape, per group

```html
<div class="simplemdm-mcp-category-group">
  <div class="simplemdm-section-head" data-section-toggle="<slugified-category>">
    <span class="simplemdm-mcp-category-name">FileVault</span>
    <span class="simplemdm-mcp-category-badges">
      <span class="badge alert-danger">2</span><span class="badge alert-warning">1</span>
    </span>
    <button type="button" class="btn btn-xs btn-default simplemdm-section-toggle">- Collapse</button>
  </div>
  <div class="simplemdm-section-body" id="simplemdm-section-<slugified-category>" style="display:block;">
    <!-- existing .list-group-item rows, unchanged per-item markup -->
  </div>
</div>
```

Category names are slugified (lowercase, non-alphanumeric → `-`) for use in `data-section-toggle`/`id` values, since category is free-form text from MCP publishers and must be a safe DOM id.

### Toggle interaction

Reuses `simplemdm_group_apps_widget.php`'s exact pattern: a delegated click handler on `[data-section-toggle]` within the widget's namespace, `slideToggle(160)` on the `.simplemdm-section-body`, button text swap between `"+ Expand"`/`"- Collapse"`, and a call to `window.simplemdmReflowDashboardGrid()` (guarded by `typeof === 'function'`, since it's only defined when the dashboard grid module has loaded) after toggling, so the dashboard's masonry layout recalculates if active.

### Scrolling

The widget's panel body div gets the `simplemdm-list-scroll` class added directly in the static PHP template (alongside the existing `simplemdm-modern-widget` class), so `max-height: 260px; overflow-y: auto` from the shared stylesheet applies unconditionally — independent of the dynamic per-widget item-count threshold in `markScrollableSimplemdmLists()`.

## Non-goals

No route/server changes, no new shared CSS/JS patterns, no persistence of UI state, no changes to non-grouping-related parts of the widget (totals row, per-item markup, tooltip, device link).

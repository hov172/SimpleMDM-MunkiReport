# MCP Findings Widget — Scrolling + Category Grouping Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** The MCP Findings dashboard widget currently shows only the 5 most recent findings with no way to see the rest. Make it fetch and render up to 100 findings, grouped into collapsible sections by category (severity badge still visible per finding), with the whole widget scrollable via the module's existing shared CSS.

**Architecture:** Single-file change to `views/simplemdm_mcp_findings_widget.php`. Reuses two pieces of existing shared infrastructure verbatim rather than inventing anything new: the `simplemdm-list-scroll` CSS class (`views/simplemdm_widget_modern_assets.php:666-671`) for the outer scroll area, and the `data-section-toggle`/`.simplemdm-section-body`/`slideToggle` collapsible-section JS pattern already used by `views/simplemdm_group_apps_widget.php:302-383`.

**Tech Stack:** PHP (server-rendered template shell), vanilla jQuery (matches the rest of this module's views — no build step, no JS test framework exists in this codebase for view files).

## Global Constraints

- No server/route changes — `get_mcp_findings` already supports `limit` up to 500; only the widget's own request/render changes.
- No new shared CSS/JS conventions — every visual/interaction pattern (scrollable list, collapsible section) must reuse what already exists in `simplemdm_widget_modern_assets.php` / `simplemdm_group_apps_widget.php`, not a new one invented for this widget.
- Groups containing at least one `danger`-severity finding start expanded; all other groups start collapsed. Sort order: danger-containing groups first, then by finding count descending, then alphabetically by category name.
- This codebase has no JS test framework for view files. Verification is via `/browse` (gstack skill) loading the real dashboard in the Docker container and visually confirming behavior + a screenshot — not `phpunit` (that only covers the PHP model/controller layer from the prior slice, unrelated to this view-only change).
- The `.mcp-widget-shot.png` file already sitting in the repo root (untracked, from a prior session's widget work) establishes screenshot-based visual verification as this widget's established QA pattern — follow it.

---

### Task 1: Rewrite the widget's fetch/grouping/render logic

**Files:**
- Modify: `views/simplemdm_mcp_findings_widget.php` (full-file rewrite — the whole file is 113 lines and every part of it is touched by this change: markup, CSS, and JS)

**Interfaces:** None — this is a leaf view template, nothing else in the codebase includes or calls into it beyond MunkiReport's own dashboard-widget loader (unchanged contract: same `panel-heading[data-widget="simplemdm_mcp_findings"]`, same outer `#simplemdm-mcp-findings-widget` id, same `get_mcp_findings` endpoint call).

- [ ] **Step 1: Read the current file once more to confirm no drift**

Run: `cat views/simplemdm_mcp_findings_widget.php`

Confirm it still matches the 113-line version this plan was written against (fetch `?limit=' + maxRows` with `maxRows = 5`, flat `.list-group`, `.simplemdm-mcp-more` note). If it has changed meaningfully since this plan was written, stop and report rather than blindly overwriting.

- [ ] **Step 2: Replace the file with the following content**

```php
<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>
<style>
#simplemdm-mcp-findings-widget .simplemdm-mcp-finding-message {
    display: block;
    margin-top: 4px;
    white-space: normal;
    word-break: break-word;
    overflow-wrap: anywhere;
}

#simplemdm-mcp-findings-widget .simplemdm-mcp-finding-meta {
    display: block;
    margin-top: 4px;
    font-size: 11px;
}

#simplemdm-mcp-findings-widget .simplemdm-mcp-totals .badge {
    margin-right: 6px;
}

#simplemdm-mcp-findings-widget .simplemdm-mcp-category-group {
    border: 1px solid var(--simplemdm-border);
    border-radius: 10px;
    margin-bottom: 8px;
    overflow: hidden;
}

#simplemdm-mcp-findings-widget .simplemdm-mcp-category-group .simplemdm-section-head {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    cursor: pointer;
}

#simplemdm-mcp-findings-widget .simplemdm-mcp-category-name {
    font-weight: 800;
    color: var(--simplemdm-ink);
    flex: 1 1 auto;
    min-width: 0;
}

#simplemdm-mcp-findings-widget .simplemdm-mcp-category-badges .badge {
    margin-right: 4px;
}

#simplemdm-mcp-findings-widget .simplemdm-section-body .list-group-item:last-child {
    border-bottom: 0;
}
</style>

<div class="col-lg-4 col-md-6">
    <div class="panel panel-default simplemdm-modern-widget simplemdm-list-scroll" id="simplemdm-mcp-findings-widget">
        <div class="panel-heading" data-widget="simplemdm_mcp_findings">
            <h3 class="panel-title">
                <i class="fa fa-flag"></i>
                <span data-i18n="simplemdm.widget.mcp_findings">MCP Findings</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="simplemdm-mcp-totals"></div>
            <div class="list-group simplemdm-mini-list" id="simplemdm-mcp-findings-groups"></div>
            <p class="text-muted text-center simplemdm-mcp-more" style="display:none;"></p>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    var fetchLimit = 100;
    var $widget = $('#simplemdm-mcp-findings-widget');

    function esc(v) {
        return $('<div>').text(String(v === null || v === undefined ? '' : v)).html();
    }

    function slugify(v) {
        return String(v || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') || 'uncategorized';
    }

    function severityBadge(severity) {
        var s = String(severity || 'info').toLowerCase();
        if (s !== 'danger' && s !== 'warning' && s !== 'info') {
            s = 'info';
        }
        return '<span class="badge alert-' + s + '">' + esc(s) + '</span>';
    }

    function renderFindingItem(finding) {
        var serial = String(finding.serial_number || '');
        var serialHtml = '';
        if (serial) {
            var deviceUrl = appUrl + '/module/simplemdm/device/' + encodeURIComponent(serial);
            serialHtml = ' <a href="' + deviceUrl + '">' + esc(serial) + '</a>';
        }
        var meta = [];
        if (finding.source) { meta.push(esc(finding.source)); }
        if (finding.reported_at) { meta.push(esc(String(finding.reported_at).replace('T', ' ').replace(/\+.*$/, ' UTC'))); }

        var item = $('<span class="list-group-item">')
            .append(severityBadge(finding.severity))
            .append(' <strong>' + esc(finding.finding_type || '-') + '</strong>')
            .append(serialHtml)
            .append('<span class="simplemdm-mcp-finding-message">' + esc(finding.message || '') + '</span>')
            .append('<span class="simplemdm-mcp-finding-meta text-muted">' + meta.join(' &middot; ') + '</span>');
        if (finding.data) {
            item.attr('title', String(finding.data));
        }
        return item;
    }

    function groupByCategory(findings) {
        var groups = {};
        findings.forEach(function(finding) {
            var name = (finding.category && String(finding.category).trim()) ? String(finding.category).trim() : 'Uncategorized';
            if (!groups[name]) {
                groups[name] = { name: name, findings: [], counts: { danger: 0, warning: 0, info: 0 } };
            }
            groups[name].findings.push(finding);
            var sev = String(finding.severity || 'info').toLowerCase();
            if (sev !== 'danger' && sev !== 'warning' && sev !== 'info') { sev = 'info'; }
            groups[name].counts[sev]++;
        });
        return Object.keys(groups).map(function(k) { return groups[k]; });
    }

    function sortGroups(groups) {
        return groups.sort(function(a, b) {
            var aDanger = a.counts.danger > 0 ? 1 : 0;
            var bDanger = b.counts.danger > 0 ? 1 : 0;
            if (aDanger !== bDanger) { return bDanger - aDanger; }
            if (a.findings.length !== b.findings.length) { return b.findings.length - a.findings.length; }
            return a.name.localeCompare(b.name);
        });
    }

    function renderCategoryGroup(group) {
        var sectionId = slugify(group.name);
        var expanded = group.counts.danger > 0;
        var badges = '';
        ['danger', 'warning', 'info'].forEach(function(sev) {
            if (group.counts[sev]) {
                badges += '<span class="badge alert-' + sev + '">' + group.counts[sev] + '</span>';
            }
        });

        var $card = $('<div class="simplemdm-mcp-category-group">');
        var $head = $('<div class="simplemdm-section-head" data-section-toggle="' + sectionId + '">')
            .append('<span class="simplemdm-mcp-category-name">' + esc(group.name) + '</span>')
            .append('<span class="simplemdm-mcp-category-badges">' + badges + '</span>')
            .append('<button type="button" class="btn btn-xs btn-default simplemdm-section-toggle">' + (expanded ? '- Collapse' : '+ Expand') + '</button>');
        var $body = $('<div class="simplemdm-section-body" id="simplemdm-section-' + sectionId + '" style="display:' + (expanded ? 'block' : 'none') + ';">');

        group.findings.forEach(function(finding) {
            $body.append(renderFindingItem(finding));
        });

        $card.append($head).append($body);
        return $card;
    }

    if (! $widget.data('simplemdmMcpFindingsBound')) {
        $widget.data('simplemdmMcpFindingsBound', 1);
        $(document).off('click.simplemdmMcpFindingsToggle', '#simplemdm-mcp-findings-widget [data-section-toggle]')
            .on('click.simplemdmMcpFindingsToggle', '#simplemdm-mcp-findings-widget [data-section-toggle]', function(ev) {
                if (ev && ev.preventDefault) { ev.preventDefault(); }
                var sectionId = String($(this).attr('data-section-toggle') || '');
                if (!sectionId) { return; }
                var $body = $('#simplemdm-section-' + sectionId);
                var $btn = $(this).find('.simplemdm-section-toggle');
                var expand = !$body.is(':visible');
                $body.stop(true, true).slideToggle(160);
                $btn.text(expand ? '- Collapse' : '+ Expand');
                if (typeof window.simplemdmReflowDashboardGrid === 'function') {
                    window.simplemdmReflowDashboardGrid();
                    setTimeout(window.simplemdmReflowDashboardGrid, 120);
                    setTimeout(window.simplemdmReflowDashboardGrid, 420);
                }
            });
    }

    $.getJSON(window.simplemdmModuleUrl('get_mcp_findings') + '?limit=' + fetchLimit, function(data) {
        var panelBody = $widget.find('.panel-body');
        var totalsRow = panelBody.find('.simplemdm-mcp-totals');
        var groupsWrap = panelBody.find('#simplemdm-mcp-findings-groups');
        var moreNote = panelBody.find('.simplemdm-mcp-more');
        totalsRow.empty();
        groupsWrap.empty();

        var totals = (data && data.totals) ? data.totals : {};
        var total = Number(totals.danger || 0) + Number(totals.warning || 0) + Number(totals.info || 0);
        if (!total) {
            panelBody.html('<p class="text-center">No MCP findings pushed yet.</p>');
            return;
        }

        [
            { label: 'Danger', count: Number(totals.danger || 0), cls: 'danger' },
            { label: 'Warning', count: Number(totals.warning || 0), cls: 'warning' },
            { label: 'Info', count: Number(totals.info || 0), cls: 'info' }
        ].forEach(function(row) {
            if (!row.count) { return; }
            totalsRow.append(
                '<span class="badge alert-' + row.cls + '">' + row.count + ' ' + row.label + '</span>'
            );
        });

        var findings = (data && data.findings) ? data.findings : [];
        var groups = sortGroups(groupByCategory(findings));
        groups.forEach(function(group) {
            groupsWrap.append(renderCategoryGroup(group));
        });

        if (total > findings.length) {
            moreNote.text('Showing ' + findings.length + ' of ' + total + ' findings.').show();
        } else {
            moreNote.hide();
        }
    }).fail(function() {
        $widget.find('.panel-body').html('<p class="text-danger text-center">Failed to load MCP findings.</p>');
    });
});
</script>
```

- [ ] **Step 3: Sanity-check the PHP file for syntax errors**

Run: `docker compose run --rm munkireport php -l local/modules/simplemdm/views/simplemdm_mcp_findings_widget.php` (from the host repo root, `<repo-root>`)

Expected: `No syntax errors detected`. This file is almost entirely a `<script>` block, so `php -l` mainly confirms the surrounding PHP tags and heredoc-free HTML/JS embedding didn't break PHP parsing — it will NOT catch JS bugs, which Task 2 covers.

- [ ] **Step 4: Commit**

```bash
git add views/simplemdm_mcp_findings_widget.php
git commit -m "feat(simplemdm): group MCP findings widget by category with scroll + collapsible sections"
```

---

### Task 2: Browser verification

**Files:** none (verification only, no commit)

**Interfaces:** none.

- [ ] **Step 1: Ensure the Docker container is running with fresh code**

Run (from the host repo root, `<repo-root>`): `docker compose up -d munkireport` — the compose file bind-mounts `./local` into the container, so Task 1's edit is already live without a rebuild; this just ensures the container is up. Confirm with `docker compose ps` that `munkireport-local` is running and healthy.

- [ ] **Step 2: Seed at least two categories of findings via the MCP ingest route**

There is no existing seed data guaranteed in this dev environment. POST a small synthetic payload directly to the module's ingest route so the widget has something real to render across multiple categories and severities. Use the module's own sync token (check `local/modules/simplemdm/app/db` or the admin UI's stored API key — if none is configured yet, set one via the admin settings UI first, or use `SIMPLEMDM_TEST_MODE`-style local config if the running container already has `SIMPLEMDM_API_KEY` set per `docker-compose.yml`'s environment block).

Example payload (adjust `source`/token header to match the running instance's actual configured key):

```bash
curl -s -X POST http://localhost:8888/module/simplemdm/ingest_mcp_findings \
  -H "X-SimpleMDM-API-Key: <the configured key>" \
  -H "Content-Type: application/json" \
  -d '{
    "source": "manual_qa",
    "findings": [
      {"finding_type": "filevault_disabled", "category": "FileVault", "severity": "warning", "message": "FileVault is disabled.", "serial_number": "C02QA0001"},
      {"finding_type": "os_eol", "category": "OS", "severity": "danger", "message": "OS is end-of-life.", "serial_number": "C02QA0002"},
      {"finding_type": "cve_exposure", "category": "Compliance", "severity": "danger", "message": "3 unfixed CVEs.", "serial_number": "C02QA0003"},
      {"finding_type": "xprotect_outdated", "category": "XProtect", "severity": "warning", "message": "XProtect outdated.", "serial_number": "C02QA0004"}
    ]
  }'
```

If the API key isn't known/configured, use the `/browse` skill to open the admin UI first (`http://localhost:8888/module/simplemdm`) and check the configured key there, or set one, before running the curl above. Report exactly what you did if this step requires improvising beyond what's written here — this is the one step in this plan most likely to need environment-specific adaptation.

- [ ] **Step 3: Load the dashboard and verify visually via `/browse`**

Use the `/browse` skill to navigate to `http://localhost:8888/` (or the dashboard URL the running instance actually serves — confirm via the admin UI navigation if `/` redirects elsewhere) and locate the "MCP Findings" widget.

Confirm, and describe what you observe for each:
1. The widget shows category sections (e.g. "OS", "Compliance", "FileVault", "XProtect" from the seed data above), not a flat list.
2. Categories containing a `danger` finding (OS, Compliance in the seed data) are expanded by default; others (FileVault, XProtect — warning-only) start collapsed.
3. Clicking a collapsed category's header (or its "+ Expand" button) expands it via the slide animation; clicking again collapses it.
4. Each finding row still shows its severity badge, finding_type, serial link, message, and source/reported_at meta line — unchanged from before this plan.
5. If total findings pushed exceed what's visible without scrolling inside the widget's fixed height, the widget scrolls internally (you may need to seed more than the 4 example findings above to see this in practice, given the 260px scroll threshold — seeding ~15-20 findings across a couple of categories, all expanded, is a reasonable way to force this if the 4-finding seed doesn't visibly overflow).

- [ ] **Step 4: Screenshot**

Take a screenshot via `/browse` of the widget in a state showing at least one expanded and one collapsed category group. Save it to the module root as `.mcp-widget-shot.png` (overwriting the existing stale one from prior work), consistent with this widget's established verification convention.

- [ ] **Step 5: Report**

No commit for this task. Summarize what was observed for each of the 5 checks in Step 3, and note the exact commands/URLs used in Step 2 so the verification is reproducible. If anything in Step 3 did NOT match expectations, stop and report the discrepancy — do not silently patch Task 1's code without going back through the same review loop.

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

    function renderCategoryGroup(group, index) {
        var sectionId = slugify(group.name) + '-' + index;
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

        // Safari applies its own elastic bounce to any overflow:auto element on
        // trackpad input; CSS (overscroll-behavior) doesn't suppress that bounce
        // in Safari, only chaining to an ancestor. Clamp scroll at the boundary
        // ourselves and preventDefault only the wheel deltas that would overscroll,
        // so Safari never starts the bounce animation. Scoped to this widget's own
        // list only (not the shared .simplemdm-list-scroll rule) to avoid affecting
        // other widgets' scroll/click behavior.
        var scrollEl = document.getElementById('simplemdm-mcp-findings-groups');
        if (scrollEl && !scrollEl.getAttribute('data-simplemdm-wheel-bound')) {
            scrollEl.setAttribute('data-simplemdm-wheel-bound', '1');
            scrollEl.addEventListener('wheel', function(ev) {
                var atTop = scrollEl.scrollTop <= 0;
                var atBottom = Math.ceil(scrollEl.scrollTop + scrollEl.clientHeight) >= scrollEl.scrollHeight;
                if ((atTop && ev.deltaY < 0) || (atBottom && ev.deltaY > 0)) {
                    ev.preventDefault();
                }
            }, { passive: false });
        }
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
        groups.forEach(function(group, index) {
            groupsWrap.append(renderCategoryGroup(group, index));
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

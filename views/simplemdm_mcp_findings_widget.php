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
</style>

<div class="col-lg-4 col-md-6">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-mcp-findings-widget">
        <div class="panel-heading" data-widget="simplemdm_mcp_findings">
            <h3 class="panel-title">
                <i class="fa fa-flag"></i>
                <span data-i18n="simplemdm.widget.mcp_findings">MCP Findings</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="simplemdm-mcp-totals"></div>
            <div class="list-group simplemdm-mini-list"></div>
            <p class="text-muted text-center simplemdm-mcp-more" style="display:none;"></p>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    var maxRows = 5;

    function esc(v) {
        return $('<div>').text(String(v === null || v === undefined ? '' : v)).html();
    }

    function severityBadge(severity) {
        var s = String(severity || 'info').toLowerCase();
        if (s !== 'danger' && s !== 'warning' && s !== 'info') {
            s = 'info';
        }
        return '<span class="badge alert-' + s + '">' + esc(s) + '</span>';
    }

    $.getJSON(window.simplemdmModuleUrl('get_mcp_findings') + '?limit=' + maxRows, function(data) {
        var panelBody = $('#simplemdm-mcp-findings-widget .panel-body');
        var totalsRow = panelBody.find('.simplemdm-mcp-totals');
        var listGroup = panelBody.find('.list-group');
        var moreNote = panelBody.find('.simplemdm-mcp-more');
        totalsRow.empty();
        listGroup.empty();

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
        findings.slice(0, maxRows).forEach(function(finding) {
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
            listGroup.append(item);
        });

        if (total > findings.length) {
            moreNote.text('Showing ' + Math.min(findings.length, maxRows) + ' of ' + total + ' findings.').show();
        } else {
            moreNote.hide();
        }
    }).fail(function() {
        $('#simplemdm-mcp-findings-widget .panel-body').html('<p class="text-danger text-center">Failed to load MCP findings.</p>');
    });
});
</script>

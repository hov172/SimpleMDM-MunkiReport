<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-4 col-md-6">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-mcp-findings-widget">
        <div class="panel-heading" data-widget="simplemdm_mcp_findings">
            <h3 class="panel-title">
                <i class="fa fa-flag"></i>
                <span data-i18n="simplemdm.widget.mcp_findings">MCP Findings</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="list-group simplemdm-mini-list"></div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    $.getJSON(window.simplemdmModuleUrl('get_mcp_findings') + '?limit=1', function(data) {
        var panelBody = $('#simplemdm-mcp-findings-widget .panel-body');
        var listGroup = panelBody.find('.list-group');
        listGroup.empty();

        var totals = (data && data.totals) ? data.totals : {};
        var total = Number(totals.danger || 0) + Number(totals.warning || 0) + Number(totals.info || 0);
        if (!total) {
            panelBody.html('<p class="text-center">No MCP findings pushed yet.</p>');
            return;
        }
        var rows = [
            { label: 'Danger', count: Number(totals.danger || 0), cls: 'danger' },
            { label: 'Warning', count: Number(totals.warning || 0), cls: 'warning' },
            { label: 'Info', count: Number(totals.info || 0), cls: 'info' }
        ];
        rows.forEach(function(row) {
            if (!row.count) { return; }
            listGroup.append(
                $('<span class="list-group-item">')
                    .append($('<span class="badge">').addClass('alert-' + row.cls).text(row.count))
                    .append(document.createTextNode(row.label))
            );
        });
    });
});
</script>

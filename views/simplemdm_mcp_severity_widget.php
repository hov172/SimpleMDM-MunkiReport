<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-4 col-md-6">
    <div class="panel panel-default simplemdm-modern-widget simplemdm-status-widget" id="simplemdm-mcp-severity-widget">
        <div class="panel-heading" data-widget="simplemdm_mcp_severity">
            <h3 class="panel-title">
                <i class="fa fa-flag"></i>
                <span data-i18n="simplemdm.widget.mcp_severity">MCP Findings by Severity</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="svg-container" style="height: 160px">
                <svg style="height: 160px"></svg>
            </div>
            <div class="list-group"></div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function(e, lang) {
    var widgetId = '#simplemdm-mcp-severity-widget';
    var severityLabels = {
        danger: 'Danger',
        warning: 'Warning',
        info: 'Info'
    };

    function esc(v) {
        return $('<div>').text(String(v === null || v === undefined ? '' : v)).html();
    }

    function findingsUrl(query) {
        return window.simplemdmModuleUrl('findings') + (query ? '?' + query : '');
    }

    function renderSeverity() {
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        $.getJSON(window.simplemdmModuleUrl('get_mcp_finding_stats'), function(stats) {
        var panelBody = $(widgetId + ' .panel-body');
        var listGroup = panelBody.find('.list-group');
        listGroup.empty();

        var by = (stats && stats.by_severity) ? stats.by_severity : {};
        var colorMap = { danger: palette.danger || '#c23b3b', warning: palette.warning || '#e6a23c', info: palette.info || '#4a90d9' };
        var rows = ['danger', 'warning', 'info']
            .filter(function(s) { return Number(by[s] || 0) > 0; })
            .map(function(s) {
                return { key: s, label: severityLabels[s] || s, value: Number(by[s] || 0), color: colorMap[s] };
            });

        if (rows.length === 0) {
            panelBody.html('<p data-i18n="no_data" class="text-center"></p>');
            return;
        }

        // Format for NVD3 and List Group
        var chartData = [];
        rows.forEach(function(row) {
            chartData.push({
                label: row.label + ' (' + row.value + ')',
                value: row.value,
                color: row.color
            });

            var filterUrl = findingsUrl('severity=' + encodeURIComponent(row.key));
            listGroup.append(
                '<a href="' + filterUrl + '" class="list-group-item">' +
                '<span class="simplemdm-status-row">' +
                '<span class="simplemdm-status-label">' + esc(row.label) + '</span>' +
                '<span class="badge simplemdm-status-count">' + row.value + '</span>' +
                '</span>' +
                '</a>'
            );
        });

        // Add Donut Chart
        nv.addGraph(function() {
            var chart = nv.models.pieChart()
                .x(function(d) { return d.label })
                .y(function(d) { return d.value })
                .showLabels(false)
                .donut(true)
                .donutRatio(0.5)
                .showLegend(false)
                .margin({top: 0, right: 0, bottom: 0, left: 0})
                .color(chartData.map(function(d){ return d.color }));

            d3.select('#simplemdm-mcp-severity-widget svg')
                .datum(chartData)
                .transition().duration(1200)
                .call(chart);

            return chart;
        });
        });
    }

    renderSeverity();
    window.addEventListener('simplemdm:modechange', renderSeverity);
});
</script>

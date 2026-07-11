<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-4 col-md-6">
    <div class="panel panel-default simplemdm-modern-widget simplemdm-status-widget" id="simplemdm-mcp-source-widget">
        <div class="panel-heading" data-widget="simplemdm_mcp_source">
            <h3 class="panel-title">
                <i class="fa fa-flag"></i>
                <span data-i18n="simplemdm.widget.mcp_source">MCP Findings by Source</span>
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
    var widgetId = '#simplemdm-mcp-source-widget';
    var topCount = 8;

    function esc(v) {
        return $('<div>').text(String(v === null || v === undefined ? '' : v)).html();
    }

    function findingsUrl(query) {
        return window.simplemdmModuleUrl('findings') + (query ? '?' + query : '');
    }

    function seriesColor(palette, index) {
        var colors = [
            palette.accent || '#0a7fa8',
            palette.accentAlt || '#2da3cf',
            palette.s4 || '#6f42c1',
            palette.s5 || '#d63384',
            palette.s6 || '#198754',
            palette.s7 || '#fd7e14',
            palette.s8 || '#6c757d',
            palette.accentStrong || '#075f7d'
        ];
        return colors[index % colors.length];
    }

    function renderSource() {
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        $.getJSON(window.simplemdmModuleUrl('get_mcp_finding_stats'), function(stats) {
        var panelBody = $(widgetId + ' .panel-body');
        var listGroup = panelBody.find('.list-group');
        listGroup.empty();

        var by = (stats && stats.by_source) ? stats.by_source : {};
        var sorted = Object.keys(by)
            .map(function(name) { return { key: name, label: name, value: Number(by[name] || 0) }; })
            .filter(function(row) { return row.value > 0; })
            .sort(function(a, b) { return b.value - a.value; });

        if (sorted.length === 0) {
            panelBody.html('<p data-i18n="no_data" class="text-center"></p>');
            return;
        }

        var top = sorted.slice(0, topCount);
        var rest = sorted.slice(topCount);
        var otherTotal = rest.reduce(function(sum, row) { return sum + row.value; }, 0);

        var rows = top.slice();
        if (otherTotal > 0) {
            rows.push({ key: 'other', label: 'Other', value: otherTotal, isOther: true });
        }

        // Format for NVD3 and List Group
        var chartData = [];
        rows.forEach(function(row, index) {
            var color = seriesColor(palette, index);
            chartData.push({
                label: esc(row.label) + ' (' + row.value + ')',
                value: row.value,
                color: color
            });

            var listItem;
            if (row.isOther) {
                listItem = $('<span class="list-group-item">');
            } else {
                var filterUrl = findingsUrl('source=' + encodeURIComponent(row.key));
                listItem = $('<a>').attr('href', filterUrl).addClass('list-group-item');
            }
            listItem.append(
                '<span class="simplemdm-status-row">' +
                '<span class="simplemdm-status-label">' + esc(row.label) + '</span>' +
                '<span class="badge simplemdm-status-count">' + row.value + '</span>' +
                '</span>'
            );
            listGroup.append(listItem);
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

            d3.select('#simplemdm-mcp-source-widget svg')
                .datum(chartData)
                .transition().duration(1200)
                .call(chart);

            return chart;
        });
        });
    }

    renderSource();
    window.addEventListener('simplemdm:modechange', renderSource);
});
</script>

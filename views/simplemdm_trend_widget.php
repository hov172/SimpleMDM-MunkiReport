<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-12">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-trend-widget">
        <div class="panel-heading" data-widget="simplemdm_trend">
            <h3 class="panel-title">
                <i class="fa fa-line-chart"></i>
                <span data-i18n="simplemdm.widget.trend">SimpleMDM Trend (30 Days)</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="svg-container" style="height: 250px;">
                <svg style="height: 250px;"></svg>
            </div>
            <div class="simplemdm-chart-note text-muted small"></div>
            <div class="simplemdm-meta-row"></div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    var widgetId = '#simplemdm-trend-widget';
    function renderTrend() {
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        $.getJSON(window.simplemdmModuleUrl('get_dashboard_trend?days=30'), function(payload) {
        var panelBody = $(widgetId + ' .panel-body');
        var note = panelBody.find('.simplemdm-chart-note');
        var meta = panelBody.find('.simplemdm-meta-row');
        meta.empty();

        var rows = (payload && payload.series) ? payload.series : [];
        if (!rows.length) {
            panelBody.html('<p data-i18n="no_data" class="text-center"></p>');
            return;
        }

        var labels = [];
        var totalValues = [];
        var enrolledValues = [];
        var filevaultValues = [];

        rows.forEach(function(row, idx) {
            var d = String(row.date || '');
            labels.push(d.slice(5));
            totalValues.push({ x: idx, y: Number(row.device_total || 0) });
            enrolledValues.push({ x: idx, y: Number(row.enrolled_total || 0) });
            filevaultValues.push({ x: idx, y: Number(row.filevault_enabled_total || 0) });
        });

        var chartData = [
            { key: 'Total Devices', color: (palette.accent || '#0a7fa8'), values: totalValues },
            { key: 'Enrolled', color: (palette.positive || '#2f9e44'), values: enrolledValues },
            { key: 'FileVault Enabled', color: (palette.warning || '#f08c00'), values: filevaultValues }
        ];

        nv.addGraph(function() {
            var chart = nv.models.lineChart()
                .useInteractiveGuideline(true)
                .showLegend(true)
                .margin({ top: 12, right: 18, bottom: 42, left: 56 });

            chart.xAxis.tickFormat(function(d) {
                return labels[d] || '';
            });
            chart.yAxis.tickFormat(d3.format('d'));

            d3.select('#simplemdm-trend-widget svg')
                .datum(chartData)
                .transition().duration(450)
                .call(chart);

            nv.utils.windowResize(chart.update);
            return chart;
        });

        var latest = rows[rows.length - 1];
        var pieces = [
            { label: 'Devices', value: Number(latest.device_total || 0) },
            { label: 'Enrolled', value: Number(latest.enrolled_total || 0) },
            { label: 'Unenrolled', value: Number(latest.unenrolled_total || 0) },
            { label: 'Resources', value: Number(latest.resource_total || 0) }
        ];
        pieces.forEach(function(piece) {
            meta.append(
                '<span class="simplemdm-meta-pill">' +
                piece.label + ': <strong>' + piece.value + '</strong>' +
                '</span>'
            );
        });

        if (!payload.has_history) {
            note.text('Historical trend starts after successful sync snapshots are collected.');
        } else {
            note.text('Showing daily latest snapshot over the last ' + Number(payload.days || 30) + ' days.');
        }
        }).fail(function() {
            $(widgetId + ' .panel-body').html('<p class="text-danger text-center">Failed to load trend data.</p>');
        });
    }

    renderTrend();
    window.addEventListener('simplemdm:modechange', renderTrend);
});
</script>

<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-6">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-mcp-timeline-widget">
        <div class="panel-heading" data-widget="simplemdm_mcp_timeline">
            <h3 class="panel-title">
                <i class="fa fa-line-chart"></i>
                <span data-i18n="simplemdm.widget.mcp_timeline">Findings Timeline</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="svg-container" style="height: 180px">
                <svg style="height: 180px"></svg>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function(e, lang) {
    var widgetId = '#simplemdm-mcp-timeline-widget';

    function renderTimeline() {
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        $.getJSON(window.simplemdmModuleUrl('get_mcp_finding_timeline?days=30'), function(data) {
        var panelBody = $(widgetId + ' .panel-body');

        var labels = (data && data.labels) ? data.labels : [];
        var newCounts = (data && data['new']) ? data['new'] : [];
        var resolvedCounts = (data && data.resolved) ? data.resolved : [];

        var total = newCounts.concat(resolvedCounts).reduce(function(sum, v) { return sum + Number(v || 0); }, 0);

        if (labels.length === 0 || total === 0) {
            panelBody.html('<p class="text-center">No findings activity in the last 30 days.</p>');
            return;
        }

        var series = [
            { key: 'New', color: palette.warning || '#e6a23c',
              values: data.labels.map(function(d, i) { return { x: i, y: data['new'][i] }; }) },
            { key: 'Resolved', color: palette.positive || '#2f9e44',
              values: data.labels.map(function(d, i) { return { x: i, y: data.resolved[i] }; }) }
        ];

        nv.addGraph(function() {
            var chart = nv.models.lineChart()
                .useInteractiveGuideline(true)
                .showLegend(true)
                .margin({ top: 12, right: 18, bottom: 42, left: 44 });

            chart.xAxis.tickFormat(function(d) {
                return labels[d] ? labels[d].slice(5) : '';
            });
            chart.yAxis.tickFormat(d3.format('d'));

            d3.select(widgetId + ' svg')
                .datum(series)
                .transition().duration(450)
                .call(chart);

            nv.utils.windowResize(chart.update);
            return chart;
        });
        }).fail(function() {
            $(widgetId + ' .panel-body').html('<p class="text-danger text-center">Failed to load findings timeline.</p>');
        });
    }

    renderTimeline();
    window.addEventListener('simplemdm:modechange', renderTimeline);
});
</script>

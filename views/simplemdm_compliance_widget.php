<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-4 col-md-6">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-compliance-widget">
        <div class="panel-heading" data-widget="simplemdm_compliance">
            <h3 class="panel-title">
                <i class="fa fa-check-square-o"></i>
                <span data-i18n="simplemdm.widget.compliance">Compliance Summary</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="svg-container" style="height: 180px;">
                <svg style="height: 180px;"></svg>
            </div>
            <div class="list-group simplemdm-mini-list"></div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    function renderCompliance() {
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        $.getJSON(window.simplemdmModuleUrl('get_compliance_stats'), function(data) {
            var panelBody = $('#simplemdm-compliance-widget .panel-body');
            var listGroup = panelBody.find('.list-group');
            listGroup.empty();

            if (!data || Number(data.total || 0) === 0) {
                panelBody.html('<p data-i18n="no_data" class="text-center"></p>');
                return;
            }

            var compliant = Number(data.compliant || 0);
            var noncompliant = Number(data.noncompliant || 0);
            var chartData = [
                { label: 'Compliant', value: compliant, color: palette.positive || '#2f9e44' },
                { label: 'Noncompliant', value: noncompliant, color: palette.danger || '#c23b3b' }
            ];

            nv.addGraph(function() {
                var chart = nv.models.pieChart()
                    .x(function(d) { return d.label; })
                    .y(function(d) { return d.value; })
                    .showLabels(false)
                    .showLegend(false)
                    .donut(true)
                    .donutRatio(0.55)
                    .margin({ top: 0, right: 0, bottom: 0, left: 0 })
                    .color(chartData.map(function(d) { return d.color; }));

                d3.select('#simplemdm-compliance-widget svg')
                    .datum(chartData)
                    .transition().duration(350)
                    .call(chart);
                return chart;
            });

            listGroup.append('<span class="list-group-item">Total<span class="badge pull-right">' + Number(data.total || 0) + '</span></span>');
            if (data.min_os) {
                listGroup.append('<span class="list-group-item">Minimum OS<span class="badge pull-right">' + data.min_os + '</span></span>');
            }
            var reasons = data.reasons || {};
            Object.keys(reasons).forEach(function(key) {
                var val = Number(reasons[key] || 0);
                if (val <= 0) {
                    return;
                }
                var label = key.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
                listGroup.append('<span class="list-group-item">' + label + '<span class="badge pull-right">' + val + '</span></span>');
            });
        }).fail(function() {
            $('#simplemdm-compliance-widget .panel-body').html('<p class="text-danger text-center">Failed to load compliance stats.</p>');
        });
    }

    renderCompliance();
    window.addEventListener('simplemdm:modechange', renderCompliance);
});
</script>

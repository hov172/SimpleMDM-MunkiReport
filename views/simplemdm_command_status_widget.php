<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-4 col-md-6">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-command-status-widget">
        <div class="panel-heading" data-widget="simplemdm_command_status">
            <h3 class="panel-title">
                <i class="fa fa-terminal"></i>
                <span data-i18n="simplemdm.widget.command_status">MDM Command Status</span>
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
    function renderCommandStatus() {
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        $.getJSON(window.simplemdmModuleUrl('get_command_status_stats'), function(rows) {
            var panelBody = $('#simplemdm-command-status-widget .panel-body');
            var listGroup = panelBody.find('.list-group');
            listGroup.empty();
            if (!rows || !rows.length) {
                panelBody.html('<p data-i18n="no_data" class="text-center"></p>');
                return;
            }

            var colors = {
                completed: palette.positive || '#2f9e44',
                success: palette.positive || '#2f9e44',
                queued: palette.warning || '#f08c00',
                pending: palette.warning || '#f08c00',
                failed: palette.danger || '#c23b3b',
                error: palette.danger || '#c23b3b',
                cancelled: palette.muted || '#dbe7f3',
                unknown: palette.info || '#1c7ed6'
            };

            var chartData = [];
            rows.forEach(function(r) {
                var label = String(r.label || 'unknown');
                var val = Number(r.count || 0);
                var key = label.toLowerCase();
                chartData.push({ label: label, value: val, color: colors[key] || (palette.accent || '#0a7fa8') });
                listGroup.append('<span class="list-group-item">' + label + '<span class="badge pull-right">' + val + '</span></span>');
            });

            nv.addGraph(function() {
                var chart = nv.models.pieChart()
                    .x(function(d) { return d.label; })
                    .y(function(d) { return d.value; })
                    .showLabels(false)
                    .showLegend(false)
                    .donut(true)
                    .donutRatio(0.55)
                    .margin({ top: 0, right: 0, bottom: 0, left: 0 })
                    .color(chartData.map(function(d){ return d.color; }));

                d3.select('#simplemdm-command-status-widget svg')
                    .datum(chartData)
                    .transition().duration(350)
                    .call(chart);
                return chart;
            });
        }).fail(function() {
            $('#simplemdm-command-status-widget .panel-body').html('<p class="text-danger text-center">Failed to load command status.</p>');
        });
    }

    renderCommandStatus();
    window.addEventListener('simplemdm:modechange', renderCommandStatus);
});
</script>

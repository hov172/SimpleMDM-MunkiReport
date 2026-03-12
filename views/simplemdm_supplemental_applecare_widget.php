<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-4 col-md-6">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-supplemental-applecare-widget">
        <div class="panel-heading" data-widget="simplemdm_supplemental_applecare">
            <h3 class="panel-title">
                <i class="fa fa-life-ring"></i>
                <span>Supplemental AppleCare</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="svg-container" style="height: 180px;">
                <svg style="height: 180px;"></svg>
            </div>
            <div class="list-group"></div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    function listingUrl(query) {
        var path = '/show/listing/simplemdm/simplemdm';
        if (appUrl.indexOf('index.php?') !== -1) {
            return appUrl + path + (query ? '?' + query : '');
        }
        if (window.location.pathname.indexOf('/index.php') !== -1) {
            return appUrl + '/index.php?' + path + (query ? '?' + query : '');
        }
        return appUrl + path + (query ? '?' + query : '');
    }

    function renderWidget() {
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        $.getJSON(window.simplemdmModuleUrl('get_supplemental_applecare_stats'), function(rows) {
            var panelBody = $('#simplemdm-supplemental-applecare-widget .panel-body');
            var listGroup = panelBody.find('.list-group').empty();
            if (!rows || !rows.length) {
                panelBody.html('<p class="text-muted text-center">No AppleCare summary data.</p>');
                return;
            }

            var colors = {
                expired: palette.danger || '#c23b3b',
                expiring_30: palette.warning || '#f59f00',
                expiring_90: '#f4b942',
                covered: palette.positive || '#2f9e44',
                missing: palette.muted || '#94a3b8'
            };
            var chartData = rows.map(function(row) {
                return {
                    label: String(row.label || '').replace(/_/g, ' '),
                    value: Number(row.count || 0),
                    color: colors[row.label] || '#94a3b8'
                };
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
                    .color(chartData.map(function(d) { return d.color; }));

                d3.select('#simplemdm-supplemental-applecare-widget svg')
                    .datum(chartData)
                    .transition().duration(350)
                    .call(chart);
                return chart;
            });

            rows.forEach(function(row) {
                var query = '';
                if (row.label === 'expiring_30') {
                    query = 'supp_applecare=expiring_30';
                } else if (row.label === 'expiring_90') {
                    query = 'supp_applecare=expiring_90';
                } else if (row.label === 'expired') {
                    query = 'supp_applecare=expired';
                } else if (row.label === 'covered') {
                    query = 'supp_applecare=covered';
                }
                listGroup.append(
                    '<a href="' + listingUrl(query) + '" class="list-group-item">' +
                    String(row.label || '').replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); }) +
                    '<span class="badge pull-right">' + Number(row.count || 0) + '</span>' +
                    '</a>'
                );
            });
        }).fail(function() {
            $('#simplemdm-supplemental-applecare-widget .panel-body').html('<p class="text-danger text-center">Failed to load AppleCare summary.</p>');
        });
    }

    renderWidget();
    window.addEventListener('simplemdm:modechange', renderWidget);
});
</script>

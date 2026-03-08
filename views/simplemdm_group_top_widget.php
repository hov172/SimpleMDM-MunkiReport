<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-6 col-md-12">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-group-top-widget">
        <div class="panel-heading" data-widget="simplemdm_group_top">
            <h3 class="panel-title">
                <i class="fa fa-users"></i>
                <span data-i18n="simplemdm.widget.group_top">Top Assignment Groups</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="svg-container" style="height: 250px;">
                <svg style="height: 250px;"></svg>
            </div>
            <div class="list-group simplemdm-mini-list"></div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    var widgetId = '#simplemdm-group-top-widget';
    function simplemdmListingUrl(query) {
        var path = '/show/listing/simplemdm/simplemdm';
        if (appUrl.indexOf('index.php?') !== -1) {
            return appUrl + path + (query ? '?' + query : '');
        }
        if (window.location.pathname.indexOf('/index.php') !== -1) {
            return appUrl + '/index.php?' + path + (query ? '?' + query : '');
        }
        return appUrl + path + (query ? '?' + query : '');
    }

    function renderGroupTop() {
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        $.getJSON(appUrl + '/module/simplemdm/get_assignment_group_stats', function(rows) {
        var panelBody = $(widgetId + ' .panel-body');
        var listGroup = panelBody.find('.list-group');
        listGroup.empty();

        if (!rows || !rows.length) {
            panelBody.html('<p data-i18n="no_data" class="text-center"></p>');
            return;
        }

        var top = rows.slice(0, 8).map(function(r) {
            return {
                label: String(r.label || 'Unknown'),
                value: Number(r.count || 0)
            };
        });

        top.forEach(function(item) {
            listGroup.append(
                '<a href="' + simplemdmListingUrl('group=' + encodeURIComponent(item.label)) + '" class="list-group-item">' +
                item.label +
                '<span class="badge pull-right">' + item.value + '</span>' +
                '</a>'
            );
        });

        var chartData = [{
            key: 'Devices',
            color: (palette.accent || '#0a7fa8'),
            values: top
        }];

        nv.addGraph(function() {
            var chart = nv.models.discreteBarChart()
                .x(function(d) { return d.label; })
                .y(function(d) { return d.value; })
                .staggerLabels(true)
                .showValues(true)
                .duration(350)
                .margin({ top: 8, right: 12, bottom: 44, left: 56 });

            chart.yAxis.tickFormat(d3.format('d'));

            d3.select('#simplemdm-group-top-widget svg')
                .datum(chartData)
                .call(chart);

            chart.discretebar.dispatch.on('elementClick.simplemdm', function(e) {
                if (e && e.data && e.data.label) {
                    window.location = simplemdmListingUrl('group=' + encodeURIComponent(String(e.data.label)));
                }
            });

            nv.utils.windowResize(chart.update);
            return chart;
        });
        }).fail(function() {
            $(widgetId + ' .panel-body').html('<p class="text-danger text-center">Failed to load group data.</p>');
        });
    }

    renderGroupTop();
    window.addEventListener('simplemdm:modechange', renderGroupTop);
});
</script>

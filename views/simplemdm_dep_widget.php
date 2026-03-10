<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-4 col-md-6">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-dep-widget">
        <div class="panel-heading" data-widget="simplemdm_dep">
            <h3 class="panel-title">
                <i class="fa fa-building"></i>
                <span data-i18n="simplemdm.widget.dep_status"></span>
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
    var widgetId = '#simplemdm-dep-widget';
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

    function renderDep() {
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        $.getJSON(window.simplemdmModuleUrl('get_dep_stats'), function(data) {
        var panelBody = $(widgetId + ' .panel-body');
        var listGroup = panelBody.find('.list-group');
        listGroup.empty();

        if (data.length === 0) {
            panelBody.html('<p data-i18n="no_data" class="text-center"></p>');
            return;
        }

        var chartData = [];
        data.forEach(function(item) {
            var label = i18n.t('simplemdm.' + item.label);
            var color = item.label === 'dep_enrolled'
                ? (palette.positive || '#2f9e44')
                : (palette.warning || '#f08c00');
            
            chartData.push({
                label: label,
                value: item.count,
                color: color
            });

            var depValue = item.label === 'dep_enrolled' ? '1' : '0';
            var filterUrl = simplemdmListingUrl('dep=' + depValue);
            listGroup.append(
                '<a href="' + filterUrl + '" class="list-group-item">' +
                label +
                '<span class="badge pull-right">' + item.count + '</span>' +
                '</a>'
            );
        });

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

            d3.select('#simplemdm-dep-widget svg')
                .datum(chartData)
                .transition().duration(1200)
                .call(chart);

            return chart;
        });
        });
    }

    renderDep();
    window.addEventListener('simplemdm:modechange', renderDep);
});
</script>

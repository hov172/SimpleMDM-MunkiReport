<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-4 col-md-6">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-resources-listing-widget">
        <div class="panel-heading" data-widget="simplemdm_resources_listing">
            <h3 class="panel-title">
                <i class="fa fa-database"></i>
                <span data-i18n="simplemdm.widget.resources_listing">SimpleMDM API Resources</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="svg-container" style="height: 170px;">
                <svg style="height: 170px;"></svg>
            </div>
            <div class="simplemdm-kpi-value" id="simplemdm-resources-count">-</div>
            <div class="btn-group" role="group">
                <a class="btn btn-xs btn-primary" id="simplemdm-open-resources" href="#">Open Resources</a>
                <a class="btn btn-xs btn-default" id="simplemdm-open-installed-apps" href="#">Installed App</a>
                <a class="btn btn-xs btn-default" id="simplemdm-open-apps" href="#">Apps</a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    var widgetId = '#simplemdm-resources-listing-widget';
    function listingUrl(path, query) {
        if (appUrl.indexOf('index.php?') !== -1) {
            return appUrl + path + (query ? '?' + query : '');
        }
        if (window.location.pathname.indexOf('/index.php') !== -1) {
            return appUrl + '/index.php?' + path + (query ? '?' + query : '');
        }
        return appUrl + path + (query ? '?' + query : '');
    }

    $('#simplemdm-open-resources').attr('href', listingUrl('/show/listing/simplemdm/simplemdm_resources'));
    $('#simplemdm-open-installed-apps').attr('href', listingUrl('/show/listing/simplemdm/simplemdm_resources', 'type=installed_app'));
    $('#simplemdm-open-apps').attr('href', listingUrl('/show/listing/simplemdm/simplemdm_resources', 'type=app'));

    function renderResourcesListing() {
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        $.getJSON(appUrl + '/module/simplemdm/get_resource_type_stats', function(rows) {
        var total = 0;
        var sorted = (rows || []).slice().sort(function(a, b) {
            return Number(b.count || 0) - Number(a.count || 0);
        });
        sorted.forEach(function(r) {
            total += Number(r.count || 0);
        });
        $(widgetId + ' #simplemdm-resources-count').text(total);

        var top = sorted.slice(0, 5);
        var pieData = top.map(function(r, idx) {
            var type = String(r.resource_type || 'unknown');
            var paletteColors = [
                palette.accent || '#0a7fa8',
                palette.positive || '#2f9e44',
                palette.warning || '#f08c00',
                palette.s4 || '#6f42c1',
                palette.s5 || '#d63384'
            ];
            return {
                label: type.replace(/_/g, ' '),
                rawType: type,
                value: Number(r.count || 0),
                color: paletteColors[idx % paletteColors.length]
            };
        });

        nv.addGraph(function() {
            var chart = nv.models.pieChart()
                .x(function(d) { return d.label; })
                .y(function(d) { return d.value; })
                .showLabels(false)
                .showLegend(false)
                .donut(true)
                .donutRatio(0.5)
                .margin({ top: 0, right: 0, bottom: 0, left: 0 })
                .color(pieData.map(function(d) { return d.color; }));

            d3.select('#simplemdm-resources-listing-widget svg')
                .datum(pieData)
                .transition().duration(320)
                .call(chart);

            chart.pie.dispatch.on('elementClick.simplemdm', function(e) {
                if (!e || !e.data || !e.data.rawType) {
                    return;
                }
                window.location = listingUrl('/show/listing/simplemdm/simplemdm_resources', 'type=' + encodeURIComponent(String(e.data.rawType)));
            });
            return chart;
        });
        }).fail(function() {
            $(widgetId + ' #simplemdm-resources-count').text('0');
        });
    }

    renderResourcesListing();
    window.addEventListener('simplemdm:modechange', renderResourcesListing);
});
</script>

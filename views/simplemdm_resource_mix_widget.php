<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-6 col-md-12">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-resource-mix-widget">
        <div class="panel-heading" data-widget="simplemdm_resource_mix">
            <h3 class="panel-title">
                <i class="fa fa-pie-chart"></i>
                <span data-i18n="simplemdm.widget.resource_mix">Resource Type Mix</span>
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
    var widgetId = '#simplemdm-resource-mix-widget';
    function resourcesListingUrl(query) {
        var path = '/show/listing/simplemdm/simplemdm_resources';
        if (appUrl.indexOf('index.php?') !== -1) {
            return appUrl + path + (query ? '?' + query : '');
        }
        if (window.location.pathname.indexOf('/index.php') !== -1) {
            return appUrl + '/index.php?' + path + (query ? '?' + query : '');
        }
        return appUrl + path + (query ? '?' + query : '');
    }

    function renderResourceMix() {
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        $.getJSON(window.simplemdmModuleUrl('get_resource_type_stats'), function(rows) {
        var panelBody = $(widgetId + ' .panel-body');
        var listGroup = panelBody.find('.list-group');
        listGroup.empty();

        if (!rows || !rows.length) {
            panelBody.html('<p data-i18n="no_data" class="text-center"></p>');
            return;
        }

        rows = rows.slice().sort(function(a, b) {
            return Number(b.count || 0) - Number(a.count || 0);
        });

        var topLimit = 7;
        var pieRows = rows.slice(0, topLimit);
        var otherRows = rows.slice(topLimit);
        if (otherRows.length) {
            var otherCount = 0;
            otherRows.forEach(function(r) {
                otherCount += Number(r.count || 0);
            });
            pieRows.push({resource_type: 'other', count: otherCount});
        }

        var total = 0;
        var colors = [
            palette.accent || '#0a7fa8',
            palette.positive || '#2f9e44',
            palette.warning || '#f08c00',
            palette.s4 || '#6f42c1',
            palette.s5 || '#d63384',
            palette.s6 || '#198754',
            palette.s7 || '#fd7e14',
            palette.s8 || '#6c757d'
        ];
        var pieData = pieRows.map(function(r, idx) {
            var type = String(r.resource_type || 'unknown');
            var count = Number(r.count || 0);
            total += count;
            var label = (type === 'other') ? 'Other' : type.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
            return {
                label: label,
                rawType: type,
                value: count,
                color: colors[idx % colors.length]
            };
        });

        pieData.forEach(function(item) {
            if (item.rawType === 'other') {
                listGroup.append(
                    '<span class="list-group-item">' +
                    item.label +
                    '<span class="badge pull-right">' + item.value + '</span>' +
                    '</span>'
                );
                return;
            }

            listGroup.append(
                '<a href="' + resourcesListingUrl('type=' + encodeURIComponent(item.rawType)) + '" class="list-group-item">' +
                item.label +
                '<span class="badge pull-right">' + item.value + '</span>' +
                '</a>'
            );
        });

        nv.addGraph(function() {
            var chart = nv.models.pieChart()
                .x(function(d) { return d.label; })
                .y(function(d) { return d.value; })
                .showLabels(false)
                .donut(true)
                .donutRatio(0.48)
                .showLegend(true)
                .margin({ top: 0, right: 0, bottom: 0, left: 0 })
                .color(pieData.map(function(d) { return d.color; }));

            d3.select('#simplemdm-resource-mix-widget svg')
                .datum(pieData)
                .transition().duration(500)
                .call(chart);

            chart.pie.dispatch.on('elementClick.simplemdm', function(e) {
                if (!e || !e.data || !e.data.rawType || e.data.rawType === 'other') {
                    return;
                }
                window.location = resourcesListingUrl('type=' + encodeURIComponent(String(e.data.rawType)));
            });

            nv.utils.windowResize(chart.update);
            return chart;
        });

        listGroup.prepend(
            '<span class="list-group-item active" style="border-radius:11px; margin-bottom:8px;">' +
            'Total Resources <span class="badge pull-right">' + total + '</span>' +
            '</span>'
        );
        }).fail(function() {
            $(widgetId + ' .panel-body').html('<p class="text-danger text-center">Failed to load resource mix data.</p>');
        });
    }

    renderResourceMix();
    window.addEventListener('simplemdm:modechange', renderResourceMix);
});
</script>

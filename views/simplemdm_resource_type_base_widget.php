<?php
include_once __DIR__ . '/simplemdm_widget_modern_assets.php';
$widget_id = isset($widget_id) ? (string)$widget_id : 'simplemdm_rt_unknown';
$resource_type = isset($resource_type) ? (string)$resource_type : '';
$resource_label = isset($resource_label) ? (string)$resource_label : ucwords(str_replace('_', ' ', $resource_type));
?>
<div class="col-lg-4 col-md-6">
    <div class="panel panel-default simplemdm-modern-widget" id="<?= htmlspecialchars($widget_id, ENT_QUOTES, 'UTF-8') ?>-widget">
        <div class="panel-heading" data-widget="<?= htmlspecialchars($widget_id, ENT_QUOTES, 'UTF-8') ?>">
            <h3 class="panel-title"><i class="fa fa-tag"></i> <?= htmlspecialchars($resource_label, ENT_QUOTES, 'UTF-8') ?></h3>
        </div>
        <div class="panel-body">
            <div class="svg-container" style="height: 110px;">
                <svg style="height: 110px;"></svg>
            </div>
            <div class="simplemdm-rt-count simplemdm-kpi-value">-</div>
            <div class="simplemdm-rt-share">-</div>
            <a class="btn btn-xs btn-primary simplemdm-rt-link" href="#">View <?= htmlspecialchars($resource_label, ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    var widgetId = <?= json_encode($widget_id) ?>;
    var resourceType = <?= json_encode($resource_type) ?>;
    var pieChart = null;
    var typePalette = ['#4ecdc4', '#ffd166', '#ff6b6b', '#6ea8fe', '#c77dff', '#f4a261', '#7bd389', '#ff9f1c', '#72ddf7', '#f07167'];

    function stableTypeColor(type) {
        var t = String(type || '');
        var hash = 0;
        for (var i = 0; i < t.length; i++) {
            hash = ((hash << 5) - hash) + t.charCodeAt(i);
            hash |= 0;
        }
        var idx = Math.abs(hash) % typePalette.length;
        return typePalette[idx];
    }

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

    var target = $('#' + widgetId + '-widget');
    target.find('.simplemdm-rt-link').attr('href', resourcesListingUrl('type=' + encodeURIComponent(resourceType)));

    function renderResourceTypeWidget() {
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        var themeName = String((document.body && document.body.getAttribute('data-simplemdm-theme')) || '');
        var accent = stableTypeColor(resourceType) || palette.accentAlt || palette.accent || '#2da3cf';
        var otherColor = themeName === 'dark' ? '#14283a' : '#d7e3ef';
        $.when(
            $.getJSON(appUrl + '/module/simplemdm/get_resource_type_count/' + encodeURIComponent(resourceType)),
            $.getJSON(appUrl + '/module/simplemdm/get_resource_type_stats')
        ).done(function(countRes, statsRes) {
        var countData = countRes && countRes[0] ? countRes[0] : {};
        var statsRows = statsRes && statsRes[0] ? statsRes[0] : [];
        var count = Number((countData && countData.count !== undefined) ? countData.count : 0);
        target.find('.simplemdm-rt-count').text(count);

        var total = 0;
        (statsRows || []).forEach(function(r) {
            total += Number(r.count || 0);
        });
        var actualShare = total > 0 ? (count / total) * 100 : 0;
        var displayShare = actualShare;
        if (count > 0 && displayShare < 8) {
            displayShare = 8;
        } else if (count < total && displayShare > 92) {
            displayShare = 92;
        }
        var other = Math.max(100 - displayShare, 0);
        var pieData = [
            {label: 'This Type', value: displayShare, color: accent},
            {label: 'Other', value: other, color: otherColor}
        ];
        target.find('.simplemdm-rt-share').text((total > 0 ? actualShare.toFixed(1) : '0.0') + '% of all resources');

        if (!pieChart) {
            pieChart = nv.models.pieChart()
                .x(function(d) { return d.label; })
                .y(function(d) { return d.value; })
                .showLegend(false)
                .showLabels(false)
                .donut(true)
                .donutRatio(0.46)
                .margin({top: 0, right: 0, bottom: 0, left: 0});
        }
        pieChart.color(pieData.map(function(d){ return d.color; }));

        d3.select('#' + widgetId + '-widget svg')
            .datum(pieData)
            .transition().duration(260)
            .call(pieChart);
        }).fail(function() {
            target.find('.simplemdm-rt-count').text('0');
            target.find('.simplemdm-rt-share').text('0.0% of all resources');
        });
    }

    renderResourceTypeWidget();
    window.addEventListener('simplemdm:modechange', renderResourceTypeWidget);
});
</script>

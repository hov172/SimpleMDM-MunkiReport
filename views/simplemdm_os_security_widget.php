<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-6 col-md-12">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-os-security-widget">
        <div class="panel-heading" data-widget="simplemdm_os_security">
            <h3 class="panel-title">
                <i class="fa fa-shield"></i>
                <span data-i18n="simplemdm.widget.os_security">Enrollment/Security by OS</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="svg-container" style="height: 280px;">
                <svg style="height: 280px;"></svg>
            </div>
            <div class="list-group simplemdm-mini-list"></div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    var widgetId = '#simplemdm-os-security-widget';
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

    function renderOsSecurity() {
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        $.getJSON(window.simplemdmModuleUrl('get_os_security_stats'), function(rows) {
        var panelBody = $(widgetId + ' .panel-body');
        var listGroup = panelBody.find('.list-group');
        listGroup.empty();

        if (!rows || !rows.length) {
            panelBody.html('<p data-i18n="no_data" class="text-center"></p>');
            return;
        }

        var enrolled = [];
        var supervised = [];
        var filevault = [];
        rows.forEach(function(row) {
            var os = String(row.os_version || 'Unknown');
            enrolled.push({label: os, value: Number(row.enrolled_total || 0)});
            supervised.push({label: os, value: Number(row.supervised_total || 0)});
            filevault.push({label: os, value: Number(row.filevault_total || 0)});

            listGroup.append(
                '<a href="' + simplemdmListingUrl('os=' + encodeURIComponent(os)) + '" class="list-group-item">' +
                os +
                '<span class="badge pull-right">' + Number(row.total || 0) + '</span>' +
                '</a>'
            );
        });

        var data = [
            { key: 'Enrolled', color: (palette.positive || '#2f9e44'), values: enrolled },
            { key: 'Supervised', color: (palette.info || '#1c7ed6'), values: supervised },
            { key: 'FileVault', color: (palette.warning || '#f08c00'), values: filevault }
        ];

        nv.addGraph(function() {
            var chart = nv.models.multiBarHorizontalChart()
                .x(function(d) { return d.label; })
                .y(function(d) { return d.value; })
                .showControls(false)
                .showValues(false)
                .stacked(true)
                .margin({ top: 8, right: 12, bottom: 28, left: 128 });

            chart.yAxis.tickFormat(d3.format('d'));

            d3.select('#simplemdm-os-security-widget svg')
                .datum(data)
                .transition().duration(450)
                .call(chart);

            nv.utils.windowResize(chart.update);
            return chart;
        });
        }).fail(function() {
            $(widgetId + ' .panel-body').html('<p class="text-danger text-center">Failed to load OS security data.</p>');
        });
    }

    renderOsSecurity();
    window.addEventListener('simplemdm:modechange', renderOsSecurity);
});
</script>

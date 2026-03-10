<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-4 col-md-6">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-device-listing-widget">
        <div class="panel-heading" data-widget="simplemdm_device_listing">
            <h3 class="panel-title">
                <i class="fa fa-laptop"></i>
                <span data-i18n="simplemdm.widget.device_listing">SimpleMDM Device Listing</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="svg-container" style="height: 170px;">
                <svg style="height: 170px;"></svg>
            </div>
            <div class="simplemdm-kpi-value" id="simplemdm-device-count">-</div>
            <div class="btn-group" role="group">
                <a class="btn btn-xs btn-primary" id="simplemdm-open-devices" href="#">Open Devices</a>
                <a class="btn btn-xs btn-default" id="simplemdm-open-enrolled" href="#">Enrolled</a>
                <a class="btn btn-xs btn-default" id="simplemdm-open-unenrolled" href="#">Unenrolled</a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    var widgetId = '#simplemdm-device-listing-widget';
    function listingUrl(path, query) {
        if (appUrl.indexOf('index.php?') !== -1) {
            return appUrl + path + (query ? '?' + query : '');
        }
        if (window.location.pathname.indexOf('/index.php') !== -1) {
            return appUrl + '/index.php?' + path + (query ? '?' + query : '');
        }
        return appUrl + path + (query ? '?' + query : '');
    }

    $('#simplemdm-open-devices').attr('href', listingUrl('/show/listing/simplemdm/simplemdm'));
    $('#simplemdm-open-enrolled').attr('href', listingUrl('/show/listing/simplemdm/simplemdm', 'status=enrolled'));
    $('#simplemdm-open-unenrolled').attr('href', listingUrl('/show/listing/simplemdm/simplemdm', 'status=unenrolled'));

    function renderDeviceListing() {
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        $.when(
            $.getJSON(window.simplemdmModuleUrl('get_enrollment_stats')),
            $.getJSON(window.simplemdmModuleUrl('get_dep_stats')),
            $.getJSON(window.simplemdmModuleUrl('get_supervised_stats')),
            $.getJSON(window.simplemdmModuleUrl('get_filevault_stats'))
        ).done(function(enrollmentRes, depRes, supervisedRes, filevaultRes) {
        var enrollment = enrollmentRes && enrollmentRes[0] ? enrollmentRes[0] : [];
        var dep = depRes && depRes[0] ? depRes[0] : [];
        var supervised = supervisedRes && supervisedRes[0] ? supervisedRes[0] : [];
        var filevault = filevaultRes && filevaultRes[0] ? filevaultRes[0] : [];

        function countFor(rows, key) {
            var found = (rows || []).filter(function(r) { return String(r.label || '') === key; })[0];
            return Number(found && found.count !== undefined ? found.count : 0);
        }

        var enrolledCount = countFor(enrollment, 'enrolled');
        var total = enrolledCount + countFor(enrollment, 'unenrolled');
        $(widgetId + ' #simplemdm-device-count').text(total);

        var values = [
            { label: 'Enrolled', value: enrolledCount },
            { label: 'DEP', value: countFor(dep, 'dep_enrolled') },
            { label: 'Supervised', value: countFor(supervised, 'supervised') },
            { label: 'FileVault', value: countFor(filevault, 'enabled') }
        ];

        nv.addGraph(function() {
            var chart = nv.models.discreteBarChart()
                .x(function(d) { return d.label; })
                .y(function(d) { return d.value; })
                .staggerLabels(false)
                .showValues(true)
                .duration(320)
                .margin({ top: 6, right: 8, bottom: 28, left: 42 });

            chart.yAxis.tickFormat(d3.format('d'));

            d3.select('#simplemdm-device-listing-widget svg')
                .datum([{ key: 'Device Security Posture', color: (palette.accent || '#0a7fa8'), values: values }])
                .call(chart);

            return chart;
        });
        }).fail(function() {
            $(widgetId + ' #simplemdm-device-count').text('0');
        });
    }

    renderDeviceListing();
    window.addEventListener('simplemdm:modechange', renderDeviceListing);
});
</script>

<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-8 col-md-12">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-devices-table-widget">
        <div class="panel-heading" data-widget="simplemdm_devices_table">
            <h3 class="panel-title">
                <i class="fa fa-list"></i>
                <span>SimpleMDM Devices Table</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="simplemdm-kpi-label" style="margin-bottom:8px;">
                <span>Total Devices: </span><span id="simplemdm-devices-table-total">-</span>
            </div>
            <div class="simplemdm-table-wrap" style="max-height:320px;overflow:auto;">
                <table class="simplemdm-table-modern" id="simplemdm-devices-table-mini">
                    <thead>
                        <tr>
                            <th>Device</th>
                            <th>Serial</th>
                            <th>Status</th>
                            <th>OS</th>
                            <th>Group</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="5" class="text-muted">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:10px;">
                <a class="btn btn-xs btn-primary" id="simplemdm-open-devices-table-full" href="#">Open Full Device Listing</a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    var widgetId = '#simplemdm-devices-table-widget';
    function listingUrl(path, query) {
        if (appUrl.indexOf('index.php?') !== -1) {
            return appUrl + path + (query ? '?' + query : '');
        }
        if (window.location.pathname.indexOf('/index.php') !== -1) {
            return appUrl + '/index.php?' + path + (query ? '?' + query : '');
        }
        return appUrl + path + (query ? '?' + query : '');
    }

    $(widgetId + ' #simplemdm-open-devices-table-full').attr('href', listingUrl('/show/listing/simplemdm/simplemdm'));

    function esc(v) {
        return $('<div>').text(String(v === null || v === undefined ? '' : v)).html();
    }

    function statusBadge(status) {
        var s = String(status || '').toLowerCase();
        if (s === 'enrolled') {
            return '<span class="simplemdm-state simplemdm-state-yes">enrolled</span>';
        }
        if (!s) {
            return '<span class="simplemdm-state simplemdm-state-na">-</span>';
        }
        return '<span class="simplemdm-state simplemdm-state-no">' + esc(s) + '</span>';
    }

    function renderRows(rows) {
        var $tbody = $(widgetId + ' #simplemdm-devices-table-mini tbody').empty();
        var data = (rows || []).slice();
        data.sort(function(a, b) {
            return String(a.device_name || '').localeCompare(String(b.device_name || ''));
        });
        $(widgetId + ' #simplemdm-devices-table-total').text(data.length);

        if (!data.length) {
            $tbody.append('<tr><td colspan="5" class="text-muted">No devices found.</td></tr>');
            return;
        }

        data.slice(0, 50).forEach(function(row) {
            var serial = String(row.serial_number || '');
            var deviceUrl = appUrl + '/module/simplemdm/device/' + encodeURIComponent(serial);
            $tbody.append(
                '<tr>' +
                    '<td><a href="' + deviceUrl + '">' + esc(row.device_name || '-') + '</a></td>' +
                    '<td>' + (serial ? '<a href="' + deviceUrl + '">' + esc(serial) + '</a>' : '-') + '</td>' +
                    '<td>' + statusBadge(row.status) + '</td>' +
                    '<td>' + esc(row.os_version || '-') + '</td>' +
                    '<td>' + esc(row.assignment_group || '-') + '</td>' +
                '</tr>'
            );
        });
    }

    $.getJSON(appUrl + '/module/simplemdm/get_data', function(data) {
        renderRows(data || []);
    }).fail(function() {
        var $tbody = $(widgetId + ' #simplemdm-devices-table-mini tbody').empty();
        $tbody.append('<tr><td colspan="5" class="text-danger">Failed to load devices.</td></tr>');
        $(widgetId + ' #simplemdm-devices-table-total').text('0');
    });
});
</script>


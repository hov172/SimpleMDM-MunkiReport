<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-4 col-md-6">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-mcp-top-devices-widget">
        <div class="panel-heading" data-widget="simplemdm_mcp_top_devices">
            <h3 class="panel-title">
                <i class="fa fa-trophy"></i>
                <span data-i18n="simplemdm.widget.mcp_top_devices">Top Devices by Findings</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="list-group simplemdm-mini-list" id="simplemdm-mcp-top-devices-list"></div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    $.getJSON(window.simplemdmModuleUrl('get_mcp_finding_stats'), function(stats) {
        var rows = (stats && stats.top_devices) ? stats.top_devices : [];
        var $list = $('#simplemdm-mcp-top-devices-list').empty();
        if (!rows.length) {
            $('#simplemdm-mcp-top-devices-widget .panel-body').html('<p class="text-center">No active findings.</p>');
            return;
        }
        rows.forEach(function(d, i) {
            var serial = String(d.serial_number || '');
            var deviceUrl = appUrl + '/module/simplemdm/device/' + encodeURIComponent(serial);
            var badges = ['danger', 'warning', 'info'].filter(function(s) { return Number(d[s]) > 0; })
                .map(function(s) { return '<span class="badge alert-' + s + '">' + Number(d[s]) + '</span>'; }).join(' ');
            $list.append($('<span class="list-group-item">')
                .append('#' + (i + 1) + ' ')
                .append('<a href="' + deviceUrl + '">' + $('<i>').text(serial).html() + '</a> ')
                .append('<span class="pull-right">' + badges + ' <span class="badge">' + Number(d.score || 0) + '</span></span>'));
        });
    });
});
</script>

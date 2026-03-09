<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>
<style>
#simplemdm-sync-health-widget .simplemdm-metric-value {
    margin-left: 8px;
}

#simplemdm-sync-health-widget .simplemdm-metric-value-long {
    display: block;
    margin-top: 6px;
    margin-left: 0;
    max-width: 100%;
    white-space: normal;
    word-break: break-word;
    overflow-wrap: anywhere;
    text-align: left;
}
</style>

<div class="col-lg-4 col-md-6">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-sync-health-widget">
        <div class="panel-heading" data-widget="simplemdm_sync_health">
            <h3 class="panel-title">
                <i class="fa fa-heartbeat"></i>
                <span data-i18n="simplemdm.widget.sync_health">Sync Health</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="simplemdm-kpi-value" id="simplemdm-sync-status-pill">-</div>
            <div class="list-group simplemdm-mini-list"></div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    function renderSyncHealth() {
        $.getJSON(appUrl + '/module/simplemdm/get_sync_telemetry', function(data) {
            var panelBody = $('#simplemdm-sync-health-widget .panel-body');
            var listGroup = panelBody.find('.list-group');
            listGroup.empty();

            var status = String(data.last_sync_status || 'Unknown');
            var statusClass = status.toLowerCase() === 'success' ? 'text-success' : (status.toLowerCase() === 'failed' ? 'text-danger' : 'text-warning');
            panelBody.find('#simplemdm-sync-status-pill').removeClass('text-success text-danger text-warning').addClass(statusClass).text(status);

            var rows = [
                { label: 'Last Sync', value: data.last_sync_time || '-', long: true },
                { label: 'Duration (ms)', value: data.sync_last_duration_ms || '0' },
                { label: 'API Requests', value: data.sync_last_api_requests || '0' },
                { label: 'API Errors', value: data.sync_last_api_errors || '0' },
                { label: 'Rate Limits', value: data.sync_last_rate_limit_hits || '0' },
                { label: 'Delta Mode', value: String(data.sync_last_delta_mode || '0') === '1' ? 'Enabled' : 'Disabled' },
                { label: 'Scope', value: data.sync_last_scope || '-' },
            ];
            rows.forEach(function(row) {
                var valueClass = row.long ? 'simplemdm-metric-value simplemdm-metric-value-long' : 'simplemdm-metric-value badge pull-right';
                listGroup.append('<span class="list-group-item">' + row.label + '<span class="' + valueClass + '">' + row.value + '</span></span>');
            });
        }).fail(function() {
            $('#simplemdm-sync-health-widget .panel-body').html('<p class="text-danger text-center">Failed to load sync telemetry.</p>');
        });
    }

    renderSyncHealth();
    window.addEventListener('simplemdm:modechange', renderSyncHealth);
});
</script>

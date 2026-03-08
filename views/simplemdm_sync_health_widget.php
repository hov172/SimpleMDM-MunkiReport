<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

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
                ['Last Sync', data.last_sync_time || '-'],
                ['Duration (ms)', data.sync_last_duration_ms || '0'],
                ['API Requests', data.sync_last_api_requests || '0'],
                ['API Errors', data.sync_last_api_errors || '0'],
                ['Rate Limits', data.sync_last_rate_limit_hits || '0'],
                ['Delta Mode', String(data.sync_last_delta_mode || '0') === '1' ? 'Enabled' : 'Disabled'],
                ['Scope', data.sync_last_scope || '-'],
            ];
            rows.forEach(function(row) {
                listGroup.append('<span class="list-group-item">' + row[0] + '<span class="badge pull-right">' + row[1] + '</span></span>');
            });
        }).fail(function() {
            $('#simplemdm-sync-health-widget .panel-body').html('<p class="text-danger text-center">Failed to load sync telemetry.</p>');
        });
    }

    renderSyncHealth();
    window.addEventListener('simplemdm:modechange', renderSyncHealth);
});
</script>

<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-4 col-md-6">
    <div class="panel panel-default simplemdm-modern-widget simplemdm-list-scroll" id="simplemdm-mcp-critical-widget">
        <div class="panel-heading" data-widget="simplemdm_mcp_critical">
            <h3 class="panel-title">
                <i class="fa fa-exclamation-triangle"></i>
                <span data-i18n="simplemdm.widget.mcp_critical">Open Danger Findings</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="list-group simplemdm-mini-list" id="simplemdm-mcp-critical-list"></div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    $.getJSON(window.simplemdmModuleUrl('get_mcp_findings') + '?severity=danger&limit=25', function(data) {
        var findings = (data && data.findings) ? data.findings : [];
        var $list = $('#simplemdm-mcp-critical-list').empty();
        if (!findings.length) {
            $('#simplemdm-mcp-critical-widget .panel-body').html('<p class="text-center">No open danger findings.</p>');
            return;
        }
        findings.forEach(function(f) {
            var deviceUrl = appUrl + '/module/simplemdm/device/' + encodeURIComponent(String(f.serial_number || ''));
            $list.append($('<span class="list-group-item">')
                .append('<strong>' + $('<i>').text(f.finding_type || '-').html() + '</strong> ')
                .append(f.serial_number ? '<a href="' + deviceUrl + '">' + $('<i>').text(f.serial_number).html() + '</a>' : '')
                .append('<span class="simplemdm-mcp-finding-message">' + $('<i>').text(f.message || '').html() + '</span>'));
        });
    });
});
</script>

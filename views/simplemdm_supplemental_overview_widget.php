<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<div class="col-lg-4 col-md-6">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-supplemental-overview-widget">
        <div class="panel-heading" data-widget="simplemdm_supplemental_overview">
            <h3 class="panel-title">
                <i class="fa fa-plus-square"></i>
                <span>Supplemental Overview</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="list-group simplemdm-mini-list"></div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    function listingUrl(query) {
        var path = '/show/listing/simplemdm/simplemdm';
        if (appUrl.indexOf('index.php?') !== -1) {
            return appUrl + path + (query ? '?' + query : '');
        }
        if (window.location.pathname.indexOf('/index.php') !== -1) {
            return appUrl + '/index.php?' + path + (query ? '?' + query : '');
        }
        return appUrl + path + (query ? '?' + query : '');
    }

    function renderWidget() {
        $.getJSON(window.simplemdmModuleUrl('get_supplemental_overview_stats'), function(data) {
            var $list = $('#simplemdm-supplemental-overview-widget .list-group').empty();
            var items = [
                { label: 'Summary Rows', value: Number(data.with_summary || 0), query: '' },
                { label: 'Supplemental FileVault Off', value: Number(data.filevault_off || 0), query: 'supp_filevault=0' },
                { label: 'AppleCare Expiring 30d', value: Number(data.applecare_expiring_30 || 0), query: 'supp_applecare=expiring_30' },
                { label: 'Profiles Present', value: Number(data.profiles_present || 0), query: 'supp_profiles=1' },
                { label: 'ManagedInstalls Errors', value: Number(data.managedinstalls_errors || 0), query: 'supp_managedinstalls=error' }
            ];

            if (!items.some(function(item) { return item.value > 0; }) && Number(data.with_summary || 0) === 0) {
                $list.append('<span class="list-group-item text-muted">No supplemental summary data yet.</span>');
                return;
            }

            items.forEach(function(item) {
                var href = item.query ? listingUrl(item.query) : listingUrl();
                $list.append(
                    '<a href="' + href + '" class="list-group-item">' +
                    item.label +
                    '<span class="badge pull-right">' + item.value + '</span>' +
                    '</a>'
                );
            });
        }).fail(function() {
            $('#simplemdm-supplemental-overview-widget .panel-body').html('<p class="text-danger text-center">Failed to load supplemental overview.</p>');
        });
    }

    renderWidget();
    window.addEventListener('simplemdm:modechange', renderWidget);
});
</script>

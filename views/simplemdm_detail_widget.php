<div class="col-md-4">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="fa fa-shield"></i>
                <span data-i18n="simplemdm.title"></span>
            </h3>
        </div>
        <div class="panel-body">
            <div id="simplemdm-detail-widget-msg" class="alert alert-info" style="display:none;">
                <span data-i18n="simplemdm.no_data"></span>
            </div>
            <div id="simplemdm-detail-widget-data" style="display:none;">
                <table class="table table-condensed">
                    <tr>
                        <td data-i18n="simplemdm.status"></td>
                        <td class="simplemdm-dw-status text-right"></td>
                    </tr>
                    <tr>
                        <td data-i18n="simplemdm.is_supervised"></td>
                        <td class="simplemdm-dw-supervised text-right"></td>
                    </tr>
                    <tr>
                        <td data-i18n="simplemdm.is_dep_enrollment"></td>
                        <td class="simplemdm-dw-dep text-right"></td>
                    </tr>
                    <tr>
                        <td data-i18n="simplemdm.filevault_enabled"></td>
                        <td class="simplemdm-dw-filevault text-right"></td>
                    </tr>
                    <tr>
                        <td data-i18n="simplemdm.last_seen_at"></td>
                        <td class="simplemdm-dw-lastseen text-right"></td>
                    </tr>
                    <tr>
                        <td data-i18n="simplemdm.assignment_group"></td>
                        <td class="simplemdm-dw-group text-right"></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function(e, lang) {
    $.getJSON(appUrl + '/module/simplemdm/get_simplemdm_data/' + serialNumber, function(data) {
        if (data.length) {
            var d = data[0];

            var statusClass = d.status === 'enrolled' ? 'label-success' : 'label-danger';
            $('.simplemdm-dw-status').html('<span class="label ' + statusClass + '">' + (d.status || '-') + '</span>');

            var boolMap = {
                'simplemdm-dw-supervised': d.is_supervised,
                'simplemdm-dw-dep': d.is_dep_enrollment,
                'simplemdm-dw-filevault': d.filevault_enabled
            };

            $.each(boolMap, function(cls, val) {
                if (val === 1 || val === true) {
                    $('.' + cls).html('<span class="label label-success">' + i18n.t('yes') + '</span>');
                } else if (val === 0 || val === false) {
                    $('.' + cls).html('<span class="label label-danger">' + i18n.t('no') + '</span>');
                } else {
                    $('.' + cls).text('-');
                }
            });

            $('.simplemdm-dw-lastseen').text(d.last_seen_at || '-');
            $('.simplemdm-dw-group').text(d.assignment_group || '-');

            $('#simplemdm-detail-widget-data').show();
        } else {
            $('#simplemdm-detail-widget-msg').show();
        }
    });
});
</script>

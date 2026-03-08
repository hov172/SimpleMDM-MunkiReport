<?php $this->view('partials/head', ['page' => 'clients', 'scripts' => ['clients/client_list.js']]); ?>
<script>
$(document).on('appReady', function(e, lang) {
    var params = new URLSearchParams(window.location.search);
    var wantedStatus = (params.get('status') || '').toLowerCase();
    var wantedDep = params.get('dep');
    var wantedSupervised = params.get('supervised');
    var wantedFilevault = params.get('filevault');
    var wantedGroup = (params.get('group') || '').toLowerCase();
    var wantedOs = (params.get('os') || '').toLowerCase();

    function normalizeBool(v) {
        if (v === 1 || v === '1' || v === true || v === 'true') {
            return 1;
        }
        if (v === 0 || v === '0' || v === false || v === 'false') {
            return 0;
        }
        return null;
    }

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable !== $('.table').get(0)) {
            return true;
        }

        var row = settings.aoData[dataIndex] && settings.aoData[dataIndex]._aData ? settings.aoData[dataIndex]._aData : null;
        if (!row) {
            return true;
        }

        if (wantedStatus) {
            var rowStatus = String(row.status || '').toLowerCase();
            if (wantedStatus === 'enrolled' && rowStatus !== 'enrolled') {
                return false;
            }
            if (wantedStatus === 'unenrolled' && rowStatus === 'enrolled') {
                return false;
            }
        }

        var depFilter = normalizeBool(wantedDep);
        if (depFilter !== null && normalizeBool(row.is_dep_enrollment) !== depFilter) {
            return false;
        }

        var supFilter = normalizeBool(wantedSupervised);
        if (supFilter !== null && normalizeBool(row.is_supervised) !== supFilter) {
            return false;
        }

        var fvFilter = normalizeBool(wantedFilevault);
        if (fvFilter !== null && normalizeBool(row.filevault_enabled) !== fvFilter) {
            return false;
        }

        if (wantedGroup) {
            var rowGroupRaw = String(row.assignment_group || '').trim();
            var rowGroup = rowGroupRaw.toLowerCase();
            if (wantedGroup === 'unknown' || wantedGroup === 'no assignment group') {
                if (rowGroup !== '' && rowGroup !== 'unknown' && rowGroup !== 'null' && rowGroup !== '(null)') {
                    return false;
                }
            } else if (rowGroup !== wantedGroup) {
                return false;
            }
        }

        if (wantedOs) {
            var rowOs = String(row.os_version || '').toLowerCase();
            if (wantedOs === 'unknown') {
                if (rowOs !== '' && rowOs !== 'unknown') {
                    return false;
                }
            } else if (rowOs !== wantedOs) {
                return false;
            }
        }

        return true;
    });

    var oTable = $('.table').DataTable({
        serverSide: false,
        ajax: {
            url: appUrl + '/module/simplemdm/get_data',
            type: "GET",
            dataSrc: ""
        },
        columns: [
            {data: 'serial_number',
                render: function(data, type, full) {
                    if (type === 'display') {
                        var href = appUrl + '/module/simplemdm/device/' + encodeURIComponent(data);
                        if (full.has_reportdata === 1 || full.has_reportdata === '1' || full.has_reportdata === true) {
                            href = appUrl + '/clients/detail/' + data + '#tab_simplemdm-tab';
                        }
                        return '<a href="' + href + '" title="' + data + '">' + data + '</a>';
                    }
                    return data;
                }
            },
            {data: 'device_name',
                render: function(data, type, full) {
                    if (type === 'display' && full.simplemdm_id) {
                        return '<a href="https://a.simplemdm.com/devices/' + full.simplemdm_id + '" target="_blank">' + data + ' <i class="fa fa-external-link"></i></a>';
                    }
                    return data;
                }
            },
            {data: 'status',
                render: function(data, type, full) {
                    if (type === 'display') {
                        var labelClass = data === 'enrolled' ? 'label-success' : 'label-danger';
                        return '<span class="label ' + labelClass + '">' + data + '</span>';
                    }
                    return data;
                }
            },
            {data: 'model_name'},
            {data: 'os_version'},
            {data: 'is_supervised',
                render: function(data, type, full) {
                    if (type === 'display') {
                        if (data === 1 || data === true) {
                            return '<span class="label label-success">' + i18n.t('yes') + '</span>';
                        } else if (data === 0 || data === false) {
                            return '<span class="label label-danger">' + i18n.t('no') + '</span>';
                        }
                        return '-';
                    }
                    return data;
                }
            },
            {data: 'is_dep_enrollment',
                render: function(data, type, full) {
                    if (type === 'display') {
                        if (data === 1 || data === true) {
                            return '<span class="label label-success">' + i18n.t('yes') + '</span>';
                        } else if (data === 0 || data === false) {
                            return '<span class="label label-danger">' + i18n.t('no') + '</span>';
                        }
                        return '-';
                    }
                    return data;
                }
            },
            {data: 'filevault_enabled',
                render: function(data, type, full) {
                    if (type === 'display') {
                        if (data === 1 || data === true) {
                            return '<span class="label label-success">' + i18n.t('yes') + '</span>';
                        } else if (data === 0 || data === false) {
                            return '<span class="label label-danger">' + i18n.t('no') + '</span>';
                        }
                        return '-';
                    }
                    return data;
                }
            },
            {data: 'last_seen_at'},
            {data: 'assignment_group'}
        ],
        createdRow: function(nRow, aData, iDataIndex) {
            // Highlight unenrolled devices
            if (aData.status !== 'enrolled') {
                $(nRow).addClass('warning');
            }
        }
    });
});
</script>

<div class="col-lg-12">
    <h3 data-i18n="simplemdm.listing_title">SimpleMDM Devices</h3>
    <table class="table table-striped table-condensed table-bordered">
        <thead>
            <tr>
                <th data-i18n="simplemdm.serial_number" data-colname="serial_number">Serial Number</th>
                <th data-i18n="simplemdm.device_name" data-colname="device_name">Device Name</th>
                <th data-i18n="simplemdm.status" data-colname="status">Enrollment Status</th>
                <th data-i18n="simplemdm.model_name" data-colname="model_name">Model</th>
                <th data-i18n="simplemdm.os_version" data-colname="os_version">OS Version</th>
                <th data-i18n="simplemdm.is_supervised" data-colname="is_supervised">Supervised</th>
                <th data-i18n="simplemdm.is_dep_enrollment" data-colname="is_dep_enrollment">DEP Enrollment</th>
                <th data-i18n="simplemdm.filevault_enabled" data-colname="filevault_enabled">FileVault</th>
                <th data-i18n="simplemdm.last_seen_at" data-colname="last_seen_at">Last Seen</th>
                <th data-i18n="simplemdm.assignment_group" data-colname="assignment_group">Assignment Group</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<?php $this->view('partials/foot'); ?>

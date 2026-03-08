<?php $this->view('partials/head', ['page' => 'clients', 'scripts' => ['clients/client_list.js']]); ?>
<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>
<style>
.simplemdm-listing-wrap {
    margin-top: 10px;
}

.simplemdm-listing-controls {
    margin-bottom: 10px;
}

.simplemdm-listing-controls label {
    color: var(--simplemdm-muted);
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.35px;
}

.simplemdm-listing-controls .form-control {
    border: 1px solid var(--simplemdm-border);
    border-radius: 10px;
    background: var(--simplemdm-surface);
    color: var(--simplemdm-ink);
}

#simplemdm-devices-table_wrapper .dataTables_length,
#simplemdm-devices-table_wrapper .dataTables_filter,
#simplemdm-devices-table_wrapper .dataTables_info,
#simplemdm-devices-table_wrapper .dataTables_paginate {
    color: var(--simplemdm-muted) !important;
}

#simplemdm-devices-table_wrapper .dataTables_filter input,
#simplemdm-devices-table_wrapper .dataTables_length select {
    border: 1px solid var(--simplemdm-border);
    border-radius: 8px;
    background: var(--simplemdm-surface);
    color: var(--simplemdm-ink);
}

#simplemdm-devices-table.table > thead > tr > th {
    background: var(--simplemdm-heading-bg);
    color: var(--simplemdm-ink);
    border-bottom: 1px solid var(--simplemdm-border);
}

#simplemdm-devices-table.table > tbody > tr > td {
    border-color: var(--simplemdm-border);
    color: var(--simplemdm-ink);
}

#simplemdm-devices-table.table-striped > tbody > tr:nth-of-type(odd) {
    background: rgba(120, 160, 200, 0.08);
}

#simplemdm-devices-table tbody tr.warning {
    background: rgba(240, 140, 0, 0.12) !important;
}
</style>

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

    function updateFiltersFromUi() {
        wantedStatus = String($('#simplemdm-filter-status').val() || '').toLowerCase();
        wantedDep = String($('#simplemdm-filter-dep').val() || '');
        wantedSupervised = String($('#simplemdm-filter-supervised').val() || '');
        wantedFilevault = String($('#simplemdm-filter-filevault').val() || '');
        wantedGroup = String($('#simplemdm-filter-group').val() || '').trim().toLowerCase();
        wantedOs = String($('#simplemdm-filter-os').val() || '').trim().toLowerCase();
    }

    function syncUiFromFilters() {
        $('#simplemdm-filter-status').val(wantedStatus || '');
        $('#simplemdm-filter-dep').val(wantedDep !== null ? String(wantedDep) : '');
        $('#simplemdm-filter-supervised').val(wantedSupervised !== null ? String(wantedSupervised) : '');
        $('#simplemdm-filter-filevault').val(wantedFilevault !== null ? String(wantedFilevault) : '');
        $('#simplemdm-filter-group').val(params.get('group') || '');
        $('#simplemdm-filter-os').val(params.get('os') || '');
    }

    function syncUrlFromFilters() {
        var url = new URL(window.location.href);
        function setOrDelete(k, v) {
            if (v === null || v === undefined || String(v).trim() === '') {
                url.searchParams.delete(k);
            } else {
                url.searchParams.set(k, String(v));
            }
        }
        setOrDelete('status', wantedStatus);
        setOrDelete('dep', wantedDep);
        setOrDelete('supervised', wantedSupervised);
        setOrDelete('filevault', wantedFilevault);
        setOrDelete('group', wantedGroup);
        setOrDelete('os', wantedOs);
        window.history.replaceState({}, '', url.toString());
    }

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable !== $('#simplemdm-devices-table').get(0)) {
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

    syncUiFromFilters();

    var oTable = $('#simplemdm-devices-table').DataTable({
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
            if (aData.status !== 'enrolled') {
                $(nRow).addClass('warning');
            }
        }
    });

    $('#simplemdm-filters-apply').on('click', function() {
        updateFiltersFromUi();
        syncUrlFromFilters();
        oTable.draw();
    });

    $('#simplemdm-filters-reset').on('click', function() {
        $('#simplemdm-filter-status').val('');
        $('#simplemdm-filter-dep').val('');
        $('#simplemdm-filter-supervised').val('');
        $('#simplemdm-filter-filevault').val('');
        $('#simplemdm-filter-group').val('');
        $('#simplemdm-filter-os').val('');
        updateFiltersFromUi();
        syncUrlFromFilters();
        oTable.draw();
    });
});
</script>

<div class="col-lg-12 simplemdm-listing-wrap">
    <div class="simplemdm-modern-widget">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-laptop"></i> <span data-i18n="simplemdm.listing_title">SimpleMDM Devices</span></h3>
        </div>
        <div class="panel-body">
            <div class="row simplemdm-listing-controls">
                <div class="col-sm-2">
                    <label for="simplemdm-filter-status">Status</label>
                    <select id="simplemdm-filter-status" class="form-control input-sm">
                        <option value="">All</option>
                        <option value="enrolled">Enrolled</option>
                        <option value="unenrolled">Unenrolled</option>
                    </select>
                </div>
                <div class="col-sm-2">
                    <label for="simplemdm-filter-dep">DEP</label>
                    <select id="simplemdm-filter-dep" class="form-control input-sm">
                        <option value="">All</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="col-sm-2">
                    <label for="simplemdm-filter-supervised">Supervised</label>
                    <select id="simplemdm-filter-supervised" class="form-control input-sm">
                        <option value="">All</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="col-sm-2">
                    <label for="simplemdm-filter-filevault">FileVault</label>
                    <select id="simplemdm-filter-filevault" class="form-control input-sm">
                        <option value="">All</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="col-sm-2">
                    <label for="simplemdm-filter-group">Assignment Group</label>
                    <input id="simplemdm-filter-group" class="form-control input-sm" type="text" placeholder="Exact group">
                </div>
                <div class="col-sm-2">
                    <label for="simplemdm-filter-os">OS Version</label>
                    <input id="simplemdm-filter-os" class="form-control input-sm" type="text" placeholder="Exact OS">
                </div>
            </div>
            <div class="row simplemdm-listing-controls">
                <div class="col-sm-12">
                    <button id="simplemdm-filters-apply" class="btn btn-primary btn-sm">Apply Filters</button>
                    <button id="simplemdm-filters-reset" class="btn btn-default btn-sm">Reset Filters</button>
                </div>
            </div>
            <table id="simplemdm-devices-table" class="table table-striped table-condensed table-bordered">
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
    </div>
</div>

<?php $this->view('partials/foot'); ?>

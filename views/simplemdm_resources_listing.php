<?php $this->view('partials/head', ['page' => 'clients', 'scripts' => ['clients/client_list.js']]); ?>
<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>
<style>
.simplemdm-resources-wrap {
    margin-top: 10px;
}

.simplemdm-resources-title {
    margin: 0 0 12px;
    color: var(--simplemdm-ink);
    font-weight: 800;
}

.simplemdm-resources-controls {
    margin-bottom: 10px;
}

.simplemdm-resources-controls label {
    color: var(--simplemdm-muted);
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.35px;
}

.simplemdm-resources-controls .form-control {
    border: 1px solid var(--simplemdm-border);
    border-radius: 10px;
    background: var(--simplemdm-surface);
    color: var(--simplemdm-ink);
}

.simplemdm-resources-controls .btn {
    border-radius: 10px;
    border: 1px solid var(--simplemdm-border);
}

#simplemdm-resources-table_wrapper .dataTables_length,
#simplemdm-resources-table_wrapper .dataTables_filter,
#simplemdm-resources-table_wrapper .dataTables_info,
#simplemdm-resources-table_wrapper .dataTables_paginate {
    color: var(--simplemdm-muted) !important;
}

#simplemdm-resources-table_wrapper .dataTables_filter input,
#simplemdm-resources-table_wrapper .dataTables_length select {
    border: 1px solid var(--simplemdm-border);
    border-radius: 8px;
    background: var(--simplemdm-surface);
    color: var(--simplemdm-ink);
}

#simplemdm-resources-table.table > thead > tr > th {
    background: var(--simplemdm-heading-bg);
    color: var(--simplemdm-ink);
    border-bottom: 1px solid var(--simplemdm-border);
}

#simplemdm-resources-table.table > tbody > tr > td {
    border-color: var(--simplemdm-border);
    color: var(--simplemdm-ink);
}

#simplemdm-resources-table.table-striped > tbody > tr:nth-of-type(odd) {
    background: rgba(120, 160, 200, 0.08);
}

@media (max-width: 991px) {
    .simplemdm-resources-controls .col-sm-3 {
        margin-bottom: 8px;
    }
}
</style>
<script>
$(document).on('appReady', function() {
    var params = new URLSearchParams(window.location.search);
    var wantedType = (params.get('type') || '').toLowerCase();
    var wantedResourceId = (params.get('resource_id') || '').toLowerCase();
    var wantedEndpoint = (params.get('endpoint') || '').toLowerCase();
    var wantedEndpointLike = (params.get('endpoint_like') || '').toLowerCase();

    function applyUrlFilters() {
        var url = new URL(window.location.href);
        if (wantedType) {
            url.searchParams.set('type', wantedType);
        } else {
            url.searchParams.delete('type');
        }
        if (wantedResourceId) {
            url.searchParams.set('resource_id', wantedResourceId);
        } else {
            url.searchParams.delete('resource_id');
        }
        if (wantedEndpoint) {
            url.searchParams.set('endpoint', wantedEndpoint);
        } else {
            url.searchParams.delete('endpoint');
        }
        if (wantedEndpointLike) {
            url.searchParams.set('endpoint_like', wantedEndpointLike);
        } else {
            url.searchParams.delete('endpoint_like');
        }
        window.history.replaceState({}, '', url.toString());
    }

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable !== $('#simplemdm-resources-table').get(0)) {
            return true;
        }
        var row = settings.aoData[dataIndex] && settings.aoData[dataIndex]._aData ? settings.aoData[dataIndex]._aData : null;
        if (!row) {
            return true;
        }
        if (wantedType && String(row.resource_type || '').toLowerCase() !== wantedType) {
            return false;
        }
        if (wantedResourceId && String(row.resource_id || '').toLowerCase() !== wantedResourceId) {
            return false;
        }
        if (wantedEndpoint && String(row.source_endpoint || '').toLowerCase() !== wantedEndpoint) {
            return false;
        }
        if (wantedEndpointLike && String(row.source_endpoint || '').toLowerCase().indexOf(wantedEndpointLike) === -1) {
            return false;
        }
        return true;
    });

    var table = $('#simplemdm-resources-table').DataTable({
        serverSide: false,
        ajax: {
            url: appUrl + '/module/simplemdm/get_resources_data',
            type: 'GET',
            dataSrc: ''
        },
        columns: [
            {data: 'resource_type'},
            {data: 'resource_id'},
            {data: 'name',
                render: function(data) {
                    return data || '-';
                }
            },
            {data: 'source_endpoint',
                render: function(data) {
                    return data || '-';
                }
            },
            {data: 'synced_at',
                render: function(data) {
                    return data || '-';
                }
            }
        ],
        initComplete: function() {
            var dt = this.api();
            var typeValues = {};
            var endpointValues = {};
            dt.rows().every(function() {
                var row = this.data();
                var t = String((row && row.resource_type) || '').trim();
                var ep = String((row && row.source_endpoint) || '').trim();
                if (t) {
                    typeValues[t] = true;
                }
                if (ep) {
                    endpointValues[ep] = true;
                }
            });

            var $typeFilter = $('#resource-type-filter').empty();
            $typeFilter.append('<option value="">All Resource Types</option>');
            Object.keys(typeValues).sort().forEach(function(type) {
                var selected = wantedType === String(type).toLowerCase() ? ' selected' : '';
                $typeFilter.append('<option value="' + type + '"' + selected + '>' + type + '</option>');
            });

            var $endpointFilter = $('#endpoint-filter').empty();
            $endpointFilter.append('<option value="">All Endpoints</option>');
            Object.keys(endpointValues).sort().forEach(function(endpoint) {
                var selected = wantedEndpoint === String(endpoint).toLowerCase() ? ' selected' : '';
                $endpointFilter.append('<option value="' + endpoint + '"' + selected + '>' + endpoint + '</option>');
            });

            $('#resource-id-filter').val(params.get('resource_id') || '');
            $('#endpoint-like-filter').val(params.get('endpoint_like') || '');
        }
    });

    $('#resource-type-filter').on('change', function() {
        wantedType = String($(this).val() || '').toLowerCase();
        applyUrlFilters();
        table.draw();
    });

    $('#endpoint-filter').on('change', function() {
        wantedEndpoint = String($(this).val() || '').toLowerCase();
        applyUrlFilters();
        table.draw();
    });

    $('#resource-id-filter, #endpoint-like-filter').on('input', function() {
        var idVal = String($('#resource-id-filter').val() || '').trim();
        var epLikeVal = String($('#endpoint-like-filter').val() || '').trim();
        wantedResourceId = idVal.toLowerCase();
        wantedEndpointLike = epLikeVal.toLowerCase();
        applyUrlFilters();
        table.draw();
    });

    $('#simplemdm-resources-reset').on('click', function() {
        wantedType = '';
        wantedResourceId = '';
        wantedEndpoint = '';
        wantedEndpointLike = '';
        $('#resource-type-filter').val('');
        $('#resource-id-filter').val('');
        $('#endpoint-filter').val('');
        $('#endpoint-like-filter').val('');
        applyUrlFilters();
        table.draw();
    });
});
</script>

<div class="col-lg-12 simplemdm-resources-wrap">
    <div class="simplemdm-modern-widget">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-database"></i> <span data-i18n="simplemdm.resources_listing_title">SimpleMDM API Resources</span></h3>
        </div>
        <div class="panel-body">
    <div class="row simplemdm-resources-controls">
        <div class="col-sm-3">
            <label for="resource-type-filter">Resource Type:</label>
            <select id="resource-type-filter" class="form-control input-sm"></select>
        </div>
        <div class="col-sm-3">
            <label for="resource-id-filter">Resource ID:</label>
            <input id="resource-id-filter" class="form-control input-sm" type="text" placeholder="Exact ID">
        </div>
        <div class="col-sm-3">
            <label for="endpoint-filter">Endpoint (Exact):</label>
            <select id="endpoint-filter" class="form-control input-sm"></select>
        </div>
        <div class="col-sm-3">
            <label for="endpoint-like-filter">Endpoint Contains:</label>
            <input id="endpoint-like-filter" class="form-control input-sm" type="text" placeholder="e.g. /installs">
        </div>
    </div>
    <div class="row simplemdm-resources-controls">
        <div class="col-sm-12">
            <button id="simplemdm-resources-reset" class="btn btn-default btn-sm">Reset Filters</button>
        </div>
    </div>
    <table id="simplemdm-resources-table" class="table table-striped table-condensed table-bordered">
        <thead>
            <tr>
                <th data-i18n="simplemdm.resource_type">Resource Type</th>
                <th data-i18n="simplemdm.resource_id">Resource ID</th>
                <th data-i18n="simplemdm.resource_name">Name</th>
                <th data-i18n="simplemdm.source_endpoint">Endpoint</th>
                <th data-i18n="simplemdm.synced_at">Synced At</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
        </div>
    </div>
</div>

<?php $this->view('partials/foot'); ?>

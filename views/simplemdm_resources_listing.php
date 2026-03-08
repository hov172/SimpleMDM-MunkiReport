<?php $this->view('partials/head', ['page' => 'clients', 'scripts' => ['clients/client_list.js']]); ?>
<script>
$(document).on('appReady', function() {
    var params = new URLSearchParams(window.location.search);
    var wantedType = (params.get('type') || '').toLowerCase();
    var wantedResourceId = (params.get('resource_id') || '').toLowerCase();
    var wantedEndpoint = (params.get('endpoint') || '').toLowerCase();

    function applyUrl(type) {
        var url = new URL(window.location.href);
        if (type) {
            url.searchParams.set('type', type);
        } else {
            url.searchParams.delete('type');
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
            var values = {};
            dt.rows().every(function() {
                var row = this.data();
                var t = String((row && row.resource_type) || '').trim();
                if (t) {
                    values[t] = true;
                }
            });

            var $filter = $('#resource-type-filter').empty();
            $filter.append('<option value="">All Resource Types</option>');
            Object.keys(values).sort().forEach(function(type) {
                var selected = wantedType === String(type).toLowerCase() ? ' selected' : '';
                $filter.append('<option value="' + type + '"' + selected + '>' + type + '</option>');
            });
            $('#resource-id-filter').val(params.get('resource_id') || '');
            $('#endpoint-filter').val(params.get('endpoint') || '');
        }
    });

    $('#resource-type-filter').on('change', function() {
        wantedType = String($(this).val() || '').toLowerCase();
        applyUrl($(this).val() || '');
        table.draw();
    });

    $('#resource-id-filter, #endpoint-filter').on('input', function() {
        var idVal = String($('#resource-id-filter').val() || '').trim();
        var epVal = String($('#endpoint-filter').val() || '').trim();
        wantedResourceId = idVal.toLowerCase();
        wantedEndpoint = epVal.toLowerCase();
        var url = new URL(window.location.href);
        if (idVal) {
            url.searchParams.set('resource_id', idVal);
        } else {
            url.searchParams.delete('resource_id');
        }
        if (epVal) {
            url.searchParams.set('endpoint', epVal);
        } else {
            url.searchParams.delete('endpoint');
        }
        window.history.replaceState({}, '', url.toString());
        table.draw();
    });
});
</script>

<div class="col-lg-12">
    <h3 data-i18n="simplemdm.resources_listing_title">SimpleMDM API Resources</h3>
    <div class="row" style="margin-bottom: 10px;">
        <div class="col-sm-4">
            <label for="resource-type-filter" style="margin-right: 8px;">Resource Type:</label>
            <select id="resource-type-filter" class="form-control input-sm" style="display:inline-block; width:auto;"></select>
        </div>
        <div class="col-sm-3">
            <label for="resource-id-filter" style="margin-right: 8px;">Resource ID:</label>
            <input id="resource-id-filter" class="form-control input-sm" type="text" placeholder="Exact ID">
        </div>
        <div class="col-sm-5">
            <label for="endpoint-filter" style="margin-right: 8px;">Endpoint:</label>
            <input id="endpoint-filter" class="form-control input-sm" type="text" placeholder="Exact endpoint">
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

<?php $this->view('partials/foot'); ?>

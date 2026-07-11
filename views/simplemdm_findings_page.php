<?php $this->view('partials/head', ['page' => 'clients']); ?>
<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>
<style>
#simplemdm-findings-page .simplemdm-findings-toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0; align-items: center; }
#simplemdm-findings-page select, #simplemdm-findings-page input[type="text"] { max-width: 180px; }
/* Theme the native form controls like the admin page's .form-control rules:
   without these, dark mode falls back to the browser's light UA styling
   (light-gray boxes with washed-out text on the dark page). */
#simplemdm-findings-page select, #simplemdm-findings-page input[type="text"] {
    border-radius: 10px;
    border: 1px solid var(--simplemdm-border);
    background: var(--simplemdm-surface);
    color: var(--simplemdm-ink);
    padding: 4px 8px;
}
#simplemdm-findings-page select option {
    background: var(--simplemdm-surface);
    color: var(--simplemdm-ink);
}
#simplemdm-findings-page input[type="text"]::placeholder {
    color: var(--simplemdm-muted);
    opacity: 1;
}
#simplemdm-findings-page table { width: 100%; }
#simplemdm-findings-page td, #simplemdm-findings-page th { padding: 6px 8px; border-bottom: 1px solid var(--simplemdm-border); vertical-align: top; }
#simplemdm-findings-page .simplemdm-findings-pager { margin: 12px 0; display: flex; gap: 8px; align-items: center; }
</style>
<div class="container">
    <div class="row"><div class="col-lg-12">
        <h3><i class="fa fa-flag"></i> MCP Findings</h3>
        <div id="simplemdm-findings-page" data-admin="<?php echo !empty($is_global_admin) ? '1' : '0'; ?>">
            <div class="simplemdm-findings-toolbar">
                <select id="f-status" multiple size="3">
                    <option value="open" selected>open</option><option value="acknowledged" selected>acknowledged</option><option value="in_progress" selected>in_progress</option>
                    <option value="resolved">resolved</option><option value="ignored">ignored</option><option value="suppressed">suppressed</option>
                </select>
                <select id="f-severity"><option value="">any severity</option><option>danger</option><option>warning</option><option>info</option></select>
                <select id="f-category"><option value="">any category</option></select>
                <select id="f-source"><option value="">any source</option></select>
                <input type="text" id="f-type" placeholder="finding_type (comma-sep)">
                <button class="btn btn-xs btn-primary" id="f-apply">Apply</button>
                <span style="flex:1"></span>
                <a class="btn btn-xs btn-default" id="f-export-csv" href="#">Export CSV</a>
                <a class="btn btn-xs btn-default" id="f-export-json" href="#">Export JSON</a>
            </div>
            <div id="f-bulkbar" style="display:none; margin-bottom:8px;">
                <span id="f-selcount"></span>
                <button class="btn btn-xs btn-default" data-bulk="acknowledge">Acknowledge</button>
                <button class="btn btn-xs btn-default" data-bulk="resolve">Resolve</button>
                <button class="btn btn-xs btn-default" data-bulk="ignore">Ignore</button>
                <button class="btn btn-xs btn-default" data-bulk="suppress">Suppress</button>
            </div>
            <table id="f-table"><thead><tr>
                <th><input type="checkbox" id="f-selall"></th>
                <th>Severity</th><th>Status</th><th>Type</th><th>Category</th><th>Serial</th><th>Message</th><th>Source</th><th>Last seen</th>
            </tr></thead><tbody></tbody></table>
            <div class="simplemdm-findings-pager">
                <button class="btn btn-xs btn-default" id="f-prev">&laquo; Prev</button>
                <span id="f-pageinfo"></span>
                <button class="btn btn-xs btn-default" id="f-next">Next &raquo;</button>
            </div>
        </div>
    </div></div>
</div>
<script>
$(document).on('appReady', function() {
    var pageSize = 50, offset = 0, isAdmin = $('#simplemdm-findings-page').attr('data-admin') === '1';
    if (!isAdmin) { $('#f-bulkbar, #f-selall').hide(); }

    function esc(v) { return $('<div>').text(String(v === null || v === undefined ? '' : v)).html(); }

    function currentFilters() {
        var statuses = ($('#f-status').val() || []).join(',');
        return {
            status: statuses, severity: $('#f-severity').val() || '',
            category: $('#f-category').val() || '', source: $('#f-source').val() || '',
            finding_type: $.trim($('#f-type').val() || '')
        };
    }
    function query(extra) {
        var f = $.extend(currentFilters(), extra || {});
        return Object.keys(f).filter(function(k) { return f[k] !== ''; })
            .map(function(k) { return k + '=' + encodeURIComponent(f[k]); }).join('&');
    }

    // Seed filters from the page URL (deep links from the dashboard widget).
    (function seedFromUrl() {
        var p = new URLSearchParams(window.location.search);
        ['severity', 'category', 'source'].forEach(function(k) { if (p.get(k)) { $('#f-' + (k === 'severity' ? 'severity' : k)).val(p.get(k)); } });
        if (p.get('finding_type')) { $('#f-type').val(p.get('finding_type')); }
        if (p.get('status')) { $('#f-status').val(p.get('status').split(',')); }
    })();

    // Filter dropdown options from stats.
    $.getJSON(window.simplemdmModuleUrl('get_mcp_finding_stats'), function(s) {
        Object.keys((s && s.by_category) || {}).sort().forEach(function(c) {
            $('#f-category').append($('<option>').val(c).text(c));
        });
        Object.keys((s && s.by_source) || {}).sort().forEach(function(src) {
            $('#f-source').append($('<option>').val(src).text(src));
        });
        var p = new URLSearchParams(window.location.search);
        if (p.get('category')) { $('#f-category').val(p.get('category')); }
        if (p.get('source')) { $('#f-source').val(p.get('source')); }
    }).always(function() {
        load();
    });

    function load() {
        $.getJSON(window.simplemdmModuleUrl('get_mcp_findings') + '?' + query({ limit: pageSize, offset: offset }), function(data) {
            var rows = (data && data.findings) ? data.findings : [];
            var $tb = $('#f-table tbody').empty();
            rows.forEach(function(f) {
                var sev = String(f.severity || 'info').toLowerCase();
                if (sev !== 'danger' && sev !== 'warning') { sev = 'info'; }
                var deviceUrl = appUrl + '/module/simplemdm/device/' + encodeURIComponent(String(f.serial_number || ''));
                $tb.append($('<tr>')
                    .append(isAdmin ? '<td><input type="checkbox" class="f-sel" value="' + Number(f.id) + '"></td>' : '<td></td>')
                    .append('<td><span class="badge alert-' + esc(sev) + '">' + esc(sev) + '</span></td>')
                    .append('<td>' + esc(f.status) + '</td>')
                    .append('<td>' + esc(f.finding_type) + '</td>')
                    .append('<td>' + esc(f.category || '') + '</td>')
                    .append('<td><a href="' + deviceUrl + '">' + esc(f.serial_number || '') + '</a></td>')
                    .append('<td>' + esc(f.message || '') + '</td>')
                    .append('<td>' + esc(f.source || '') + '</td>')
                    .append('<td>' + esc(String(f.last_seen_at || '').slice(0, 10)) + '</td>'));
            });
            if (!rows.length) { $tb.append('<tr><td colspan="9" class="text-muted">No findings match.</td></tr>'); }
            $('#f-pageinfo').text('rows ' + (offset + 1) + '-' + (offset + rows.length));
            $('#f-prev').prop('disabled', offset === 0);
            $('#f-next').prop('disabled', rows.length < pageSize);
            $('#f-selall').prop('checked', false); updateBulkbar();
        }).fail(function() {
            $('#f-table tbody').empty().append('<tr><td colspan="9" class="text-danger">Failed to load findings.</td></tr>');
            $('#f-pageinfo').text('');
        });
        $('#f-export-csv').attr('href', window.simplemdmModuleUrl('export_mcp_findings') + '?format=csv&' + query());
        $('#f-export-json').attr('href', window.simplemdmModuleUrl('export_mcp_findings') + '?format=json&' + query());
    }

    function selectedIds() { return $('.f-sel:checked').map(function() { return Number(this.value); }).get(); }
    function updateBulkbar() {
        var n = selectedIds().length;
        $('#f-bulkbar').toggle(isAdmin && n > 0);
        $('#f-selcount').text(n + ' selected');
    }

    $('#f-apply').on('click', function() { offset = 0; load(); });
    $('#f-prev').on('click', function() { offset = Math.max(0, offset - pageSize); load(); });
    $('#f-next').on('click', function() { offset += pageSize; load(); });
    $(document).on('change', '.f-sel, #f-selall', function() {
        if (this.id === 'f-selall') { $('.f-sel').prop('checked', this.checked); }
        updateBulkbar();
    });
    $('[data-bulk]').on('click', function() {
        var ids = selectedIds(); if (!ids.length) { return; }
        var action = $(this).attr('data-bulk');
        $.ajax({
            url: window.simplemdmModuleUrl(action + '_mcp_finding'),
            method: 'POST', contentType: 'application/json', data: JSON.stringify({ ids: ids })
        }).always(load);
    });
});
</script>
<?php $this->view('partials/foot'); ?>

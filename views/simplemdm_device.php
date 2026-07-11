<?php $this->view('partials/head', ['page' => 'clients']); ?>
<script>window.SIMPLEMDM_DISABLE_DASHBOARD_GRID = true;</script>
<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

<style>
.simplemdm-device-wrap {
    margin-top: 12px;
}

.simplemdm-device-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 12px;
    margin-bottom: 12px;
}

.simplemdm-device-title {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
    color: var(--simplemdm-ink);
}

.simplemdm-device-subtitle {
    margin-top: 4px;
    color: var(--simplemdm-muted);
    font-size: 14px;
}

.simplemdm-device-chip {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 4px 10px;
    font-weight: 700;
    font-size: 12px;
    border: 1px solid var(--simplemdm-border);
    background: var(--simplemdm-surface-alt);
    color: var(--simplemdm-ink);
    margin-left: 6px;
}

.simplemdm-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 12px;
    margin-bottom: 12px;
}

.simplemdm-kpi-card {
    border: 1px solid var(--simplemdm-border);
    border-radius: 12px;
    background: var(--simplemdm-card-bg);
    padding: 12px;
}

.simplemdm-kpi-label {
    color: var(--simplemdm-muted);
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.simplemdm-kpi-value {
    color: var(--simplemdm-ink);
    font-size: 20px;
    font-weight: 800;
    margin-top: 4px;
}

.simplemdm-two-col {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
    gap: 12px;
    margin-bottom: 12px;
}

.simplemdm-kv-table {
    width: 100%;
    border-collapse: collapse;
}

.simplemdm-kv-table th,
.simplemdm-kv-table td {
    border-bottom: 1px solid var(--simplemdm-border);
    padding: 7px 4px;
    vertical-align: top;
}

.simplemdm-kv-table th {
    width: 220px;
    color: var(--simplemdm-muted);
    font-weight: 700;
}

.simplemdm-kv-table td {
    color: var(--simplemdm-ink);
    word-break: break-word;
    overflow-wrap: anywhere;
}

.simplemdm-state {
    display: inline-block;
    border-radius: 999px;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 800;
    border: 1px solid var(--simplemdm-border);
}

.simplemdm-state-yes {
    background: rgba(47, 158, 68, 0.18);
    color: var(--simplemdm-positive);
    border-color: rgba(47, 158, 68, 0.35);
}

.simplemdm-state-no {
    background: rgba(194, 59, 59, 0.18);
    color: var(--simplemdm-danger);
    border-color: rgba(194, 59, 59, 0.35);
}

.simplemdm-state-na {
    background: var(--simplemdm-surface-alt);
    color: var(--simplemdm-muted);
}

.simplemdm-section {
    border: 1px solid var(--simplemdm-border);
    border-radius: 11px;
    background: var(--simplemdm-surface);
    margin-bottom: 10px;
    overflow: hidden;
}

.simplemdm-section-head {
    background: var(--simplemdm-heading-bg);
    border-bottom: 1px solid var(--simplemdm-border);
    color: var(--simplemdm-ink);
    font-weight: 800;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 9px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
}

.simplemdm-section-toggle {
    border: 1px solid var(--simplemdm-border);
    background: var(--simplemdm-surface-alt);
    color: var(--simplemdm-ink);
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    padding: 2px 8px;
}

.simplemdm-section-body {
    padding: 10px 12px;
}

.simplemdm-list-pills .simplemdm-device-chip {
    margin: 0 8px 8px 0;
}

.simplemdm-table-modern {
    width: 100%;
    border-collapse: collapse;
}

.simplemdm-table-modern th,
.simplemdm-table-modern td {
    border-bottom: 1px solid var(--simplemdm-border);
    padding: 8px 7px;
    vertical-align: top;
}

.simplemdm-table-modern th {
    color: var(--simplemdm-muted);
    font-weight: 800;
}

.simplemdm-table-modern td {
    color: var(--simplemdm-ink);
}

.simplemdm-table-wrap {
    overflow-x: auto;
}

.simplemdm-source-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 800;
    border: 1px solid var(--simplemdm-border);
    white-space: nowrap;
}

.simplemdm-source-direct {
    color: #c7f5d2;
    background: rgba(47, 158, 68, 0.18);
    border-color: rgba(47, 158, 68, 0.35);
}

.simplemdm-source-derived {
    color: #ffe0b2;
    background: rgba(245, 159, 0, 0.18);
    border-color: rgba(245, 159, 0, 0.35);
}

@media (max-width: 1200px) {
    .simplemdm-kpi-grid {
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    }
}

@media (max-width: 991px) {
    .simplemdm-two-col {
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    }
}

@media (max-width: 640px) {
    .simplemdm-kpi-grid {
        grid-template-columns: 1fr;
    }
    .simplemdm-two-col {
        grid-template-columns: 1fr;
    }
    .simplemdm-kv-table th {
        width: 160px;
    }
}

.simplemdm-kv-row {
    padding: 8px 0;
    border-bottom: 1px solid var(--simplemdm-border);
}

.simplemdm-kv-row:last-child {
    border-bottom: none;
}

.simplemdm-finding-data {
    max-height: 160px;
    overflow-y: auto;
    font-size: 11px;
    margin: 4px 0 0;
}

.simplemdm-finding-actions {
    display: block;
    margin-top: 4px;
}
</style>

<div class="col-lg-12 simplemdm-device-wrap">
    <div class="simplemdm-device-header">
        <div>
            <h2 class="simplemdm-device-title">SimpleMDM Device Detail</h2>
            <div class="simplemdm-device-subtitle">
                <strong>Serial:</strong>
                <span id="simplemdm-serial"><?php echo htmlspecialchars($serial_number, ENT_QUOTES, 'UTF-8'); ?></span>
                <span id="simplemdm-status-badge" class="simplemdm-device-chip" style="display:none;"></span>
                <span id="simplemdm-group-badge" class="simplemdm-device-chip" style="display:none;"></span>
            </div>
        </div>
    </div>

    <div id="simplemdm-device-msg" class="alert alert-info" style="display:none;"></div>

    <div id="simplemdm-layout" style="display:none;">
        <div class="simplemdm-kpi-grid">
            <div class="simplemdm-kpi-card">
                <div class="simplemdm-kpi-label">Enrollment</div>
                <div class="simplemdm-kpi-value" id="simplemdm-kpi-enrollment">-</div>
            </div>
            <div class="simplemdm-kpi-card">
                <div class="simplemdm-kpi-label">DEP</div>
                <div class="simplemdm-kpi-value" id="simplemdm-kpi-dep">-</div>
            </div>
            <div class="simplemdm-kpi-card">
                <div class="simplemdm-kpi-label">Supervised</div>
                <div class="simplemdm-kpi-value" id="simplemdm-kpi-supervised">-</div>
            </div>
            <div class="simplemdm-kpi-card">
                <div class="simplemdm-kpi-label">FileVault</div>
                <div class="simplemdm-kpi-value" id="simplemdm-kpi-filevault">-</div>
            </div>
        </div>

        <div class="simplemdm-two-col">
            <div class="simplemdm-modern-widget">
                <div class="panel-heading"><h3 class="panel-title"><i class="fa fa-info-circle"></i> Overview</h3></div>
                <div class="panel-body">
                    <table class="simplemdm-kv-table"><tbody id="simplemdm-overview"></tbody></table>
                </div>
            </div>
            <div class="simplemdm-modern-widget">
                <div class="panel-heading"><h3 class="panel-title"><i class="fa fa-shield"></i> Security &amp; Compliance</h3></div>
                <div class="panel-body">
                    <table class="simplemdm-kv-table"><tbody id="simplemdm-security"></tbody></table>
                </div>
            </div>
        </div>

        <div class="simplemdm-modern-widget" style="margin-bottom:12px;">
            <div class="panel-heading"><h3 class="panel-title"><i class="fa fa-list-alt"></i> Attributes</h3></div>
            <div class="panel-body">
                <div id="simplemdm-attributes-sections"></div>
            </div>
        </div>

        <div class="simplemdm-modern-widget" style="margin-bottom:12px;">
            <div class="panel-heading">
                <h3 class="panel-title" style="display:flex;align-items:center;justify-content:space-between;">
                    <span><i class="fa fa-code-fork"></i> Relationships</span>
                    <span style="font-size:12px;text-transform:none;font-weight:700;">
                        <label style="margin:0;">
                            <input type="checkbox" id="simplemdm-show-technical"> Show technical details
                        </label>
                    </span>
                </h3>
            </div>
            <div class="panel-body">
                <table class="simplemdm-kv-table"><tbody id="simplemdm-relationships"></tbody></table>
            </div>
        </div>

        <div class="simplemdm-modern-widget" style="margin-bottom:12px;">
            <div class="panel-heading"><h3 class="panel-title"><i class="fa fa-link"></i> Connected Resources</h3></div>
            <div class="panel-body">
                <div id="simplemdm-connected-summary" class="simplemdm-list-pills">
                    <span class="text-muted">Loading...</span>
                </div>
                <div id="simplemdm-connected-groups">
                    <div class="text-muted">Loading...</div>
                </div>
            </div>
        </div>

        <div class="simplemdm-modern-widget" style="margin-bottom:12px;">
            <div class="panel-heading"><h3 class="panel-title"><i class="fa fa-database"></i> Synced Device Subresources</h3></div>
            <div class="panel-body">
                <div id="simplemdm-subresource-summary" class="simplemdm-list-pills">
                    <span class="text-muted">Loading...</span>
                </div>
                <div id="simplemdm-subresource-sections">
                    <div class="text-muted">Loading...</div>
                </div>
            </div>
        </div>

        <div class="simplemdm-modern-widget" style="margin-bottom:12px;">
            <div class="panel-heading"><h3 class="panel-title"><i class="fa fa-plus-square"></i> Supplemental Data</h3></div>
            <div class="panel-body">
                <div id="simplemdm-supplemental-summary" class="simplemdm-list-pills">
                    <span class="text-muted">Loading...</span>
                </div>
                <div id="simplemdm-supplemental-sections">
                    <div class="text-muted">Loading...</div>
                </div>
            </div>
        </div>

        <div class="simplemdm-modern-widget" style="margin-bottom:12px;">
            <div class="panel-heading"><h3 class="panel-title"><i class="fa fa-terminal"></i> Device Actions</h3></div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <label for="simplemdm-action-secret">Action Secret</label>
                        <input type="password" id="simplemdm-action-secret" class="form-control" placeholder="Set in Admin settings">
                    </div>
                    <div class="col-md-4">
                        <label for="simplemdm-action-name">Action</label>
                        <select id="simplemdm-action-name" class="form-control"></select>
                    </div>
                    <div class="col-md-4">
                        <label for="simplemdm-action-method">Method</label>
                        <input type="text" id="simplemdm-action-method" class="form-control" readonly>
                    </div>
                </div>
                <div style="margin-top:10px;">
                    <label for="simplemdm-action-payload">JSON Payload (optional)</label>
                    <textarea id="simplemdm-action-payload" class="form-control" rows="5" placeholder='{"notify_user": true}'></textarea>
                </div>
                <div style="margin-top:10px;">
                    <button id="simplemdm-run-action" class="btn btn-primary">Run Action</button>
                    <span id="simplemdm-action-status" style="margin-left:10px;"></span>
                </div>
                <div style="margin-top:10px;">
                    <pre id="simplemdm-action-result" style="max-height:260px;overflow:auto;display:none;"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.simplemdmIsGlobalAdmin = <?php echo !empty($is_global_admin) ? 'true' : 'false'; ?>;
$(document).on('appReady', function() {
    $('body').attr('data-simplemdm-disable-dashboard-grid', '1');
    var serial = $('#simplemdm-serial').text().trim();
    var url = appUrl + '/module/simplemdm/get_simplemdm_data/' + encodeURIComponent(serial);
    var showTechnical = false;
    var currentAttrs = {};
    var currentRels = {};
    var simplemdmDeviceId = '';

    function esc(val) {
        return $('<div>').text(String(val)).html();
    }

    function toTitle(key) {
        return String(key || '').replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
    }

    function isIsoDateString(v) {
        return typeof v === 'string' && /^\d{4}-\d{2}-\d{2}T/.test(v);
    }

    function formatDate(v) {
        try {
            var dt = new Date(v);
            if (isNaN(dt.getTime())) {
                return esc(v);
            }
            return esc(dt.toLocaleString());
        } catch (e) {
            return esc(v);
        }
    }

    function boolState(v) {
        if (v === 1 || v === true || v === '1') {
            return { text: 'Yes', cls: 'simplemdm-state-yes' };
        }
        if (v === 0 || v === false || v === '0') {
            return { text: 'No', cls: 'simplemdm-state-no' };
        }
        return { text: '-', cls: 'simplemdm-state-na' };
    }

    function boolBadge(v) {
        var s = boolState(v);
        return '<span class="simplemdm-state ' + s.cls + '">' + esc(s.text) + '</span>';
    }

    function renderScalar(val) {
        if (val === null || val === undefined || val === '') {
            return '<span class="text-muted">-</span>';
        }
        if (val === true || val === false || val === 1 || val === 0 || val === '1' || val === '0') {
            return boolBadge(val);
        }
        if (typeof val === 'number') {
            return esc(val);
        }
        if (isIsoDateString(val)) {
            return formatDate(val);
        }
        return esc(val);
    }

    function renderValue(val, depth) {
        depth = depth || 0;
        if (val === null || val === undefined || val === '') {
            return '<span class="text-muted">-</span>';
        }

        if (typeof val === 'string') {
            try {
                val = JSON.parse(val);
            } catch (e) {
                return renderScalar(val);
            }
        }

        if (typeof val !== 'object') {
            return renderScalar(val);
        }

        if (depth > 2) {
            if (Array.isArray(val)) {
                return '<span class="text-muted">List (' + val.length + ' items)</span>';
            }
            return '<span class="text-muted">Object (' + Object.keys(val).length + ' fields)</span>';
        }

        if (Array.isArray(val)) {
            if (!val.length) {
                return '<span class="text-muted">None</span>';
            }
            var allScalars = val.every(function(item) {
                return item === null || item === undefined || ['string', 'number', 'boolean'].indexOf(typeof item) !== -1;
            });
            if (allScalars) {
                return val.map(function(item) {
                    return '<span class="simplemdm-device-chip">' + esc(item === null ? '-' : item) + '</span>';
                }).join('');
            }
            var rows = val.map(function(item, idx) {
                return '<tr><th style="width:90px;">#' + (idx + 1) + '</th><td>' + renderValue(item, depth + 1) + '</td></tr>';
            }).join('');
            return '<table class="simplemdm-table-modern"><tbody>' + rows + '</tbody></table>';
        }

        var keys = Object.keys(val);
        if (!showTechnical) {
            keys = keys.filter(function(k) {
                return ['id', 'type', 'group_type'].indexOf(k) === -1;
            });
        }
        if (!keys.length) {
            return '<span class="text-muted">None</span>';
        }
        var objRows = keys.sort().map(function(key) {
            return '<tr><th style="width:240px;">' + esc(toTitle(key)) + '</th><td>' + renderValue(val[key], depth + 1) + '</td></tr>';
        }).join('');
        return '<table class="simplemdm-table-modern"><tbody>' + objRows + '</tbody></table>';
    }

    function renderOverviewValue(val) {
        if (typeof val === 'string' && /^https?:\/\//i.test(val)) {
            return '<a href="' + esc(val) + '" target="_blank" rel="noopener noreferrer">' + esc(val) + '</a>';
        }
        return esc(val);
    }

    function addRows($target, rows) {
        $target.empty();
        rows.forEach(function(row) {
            $target.append('<tr><th>' + esc(row.label) + '</th><td>' + row.value + '</td></tr>');
        });
    }

    function setKpi(selector, v) {
        var s = boolState(v);
        $(selector).html('<span class="simplemdm-state ' + s.cls + '">' + esc(s.text) + '</span>');
    }

    function resourcesListingUrl(query) {
        var path = '/show/listing/simplemdm/simplemdm_resources';
        if (appUrl.indexOf('index.php?') !== -1) {
            return appUrl + path + (query ? '?' + query : '');
        }
        if (window.location.pathname.indexOf('/index.php') !== -1) {
            return appUrl + '/index.php?' + path + (query ? '?' + query : '');
        }
        return appUrl + path + (query ? '?' + query : '');
    }

    function createSectionHtml(id, title, bodyHtml, expanded) {
        return '' +
            '<div class="simplemdm-section" data-section-id="' + esc(id) + '">' +
                '<div class="simplemdm-section-head" data-section-toggle="' + esc(id) + '">' +
                    '<span>' + esc(title) + '</span>' +
                    '<span class="simplemdm-section-toggle">' + (expanded ? '- Collapse' : '+ Expand') + '</span>' +
                '</div>' +
                '<div class="simplemdm-section-body" id="simplemdm-section-' + esc(id) + '" style="display:' + (expanded ? 'block' : 'none') + ';">' +
                    bodyHtml +
                '</div>' +
            '</div>';
    }

    function renderSimpleTable(columns, rows) {
        var thead = '<tr>' + columns.map(function(col) { return '<th>' + esc(col) + '</th>'; }).join('') + '</tr>';
        var tbody = rows.length ? rows.join('') : '<tr><td colspan="' + columns.length + '" class="text-muted">No rows</td></tr>';
        return '<div class="simplemdm-table-wrap"><table class="simplemdm-table-modern"><thead>' + thead + '</thead><tbody>' + tbody + '</tbody></table></div>';
    }

    function renderSubresources(data) {
        var apps = (data && data.installed_apps) ? data.installed_apps : [];
        var users = (data && data.users) ? data.users : [];
        var profiles = (data && data.profiles) ? data.profiles : [];
        var $summary = $('#simplemdm-subresource-summary').empty();
        var $sections = $('#simplemdm-subresource-sections').empty();

        if (data && data.device_id) {
            simplemdmDeviceId = String(data.device_id);
        }

        $summary
            .append('<span class="simplemdm-device-chip"><strong>Installed Apps:</strong>&nbsp;' + esc(apps.length) + '</span>')
            .append('<span class="simplemdm-device-chip"><strong>Users:</strong>&nbsp;' + esc(users.length) + '</span>')
            .append('<span class="simplemdm-device-chip"><strong>Profiles:</strong>&nbsp;' + esc(profiles.length) + '</span>')
            .append('<span class="simplemdm-source-badge simplemdm-source-direct">Direct per-device</span>');

        var appRows = apps.map(function(item) {
            var a = item.attributes || {};
            return '<tr><td>' + esc(item.name || '-') + '</td><td>' + esc(a.identifier || '-') + '</td><td>' + esc(a.short_version || a.version || '-') + '</td><td>' + esc(a.managed === true || a.managed === 1 ? 'Yes' : (a.managed === false || a.managed === 0 ? 'No' : '-')) + '</td><td><span class="simplemdm-source-badge simplemdm-source-direct">Direct per-device</span></td></tr>';
        });
        $sections.append(createSectionHtml('sub_apps', 'Installed Apps (' + apps.length + ')', renderSimpleTable(['Name', 'Identifier', 'Version', 'Managed', 'Source'], appRows), false));

        var userRows = users.map(function(item) {
            var a = item.attributes || {};
            return '<tr><td>' + esc(a.username || item.name || '-') + '</td><td>' + esc(a.full_name || '-') + '</td><td>' + esc(a.uid || '-') + '</td><td>' + esc(a.logged_in === true || a.logged_in === 1 ? 'Yes' : (a.logged_in === false || a.logged_in === 0 ? 'No' : '-')) + '</td><td><span class="simplemdm-source-badge simplemdm-source-direct">Direct per-device</span></td></tr>';
        });
        $sections.append(createSectionHtml('sub_users', 'Users (' + users.length + ')', renderSimpleTable(['Username', 'Full Name', 'UID', 'Logged In', 'Source'], userRows), false));

        var profileRows = profiles.map(function(item) {
            var a = item.attributes || {};
            return '<tr><td>' + esc(item.name || a.name || '-') + '</td><td>' + esc(a.profile_identifier || '-') + '</td><td>' + esc(item.type || '-') + '</td><td><span class="simplemdm-source-badge simplemdm-source-direct">Direct per-device</span></td></tr>';
        });
        $sections.append(createSectionHtml('sub_profiles', 'Profiles (' + profiles.length + ')', renderSimpleTable(['Name', 'Identifier', 'Type', 'Source'], profileRows), false));
    }

    var actionDefinitions = [
        { key: 'refresh', label: 'Refresh', method: 'POST', path: 'refresh' },
        { key: 'push_apps', label: 'Push Assigned Apps', method: 'POST', path: 'push_apps' },
        { key: 'restart', label: 'Restart', method: 'POST', path: 'restart' },
        { key: 'shutdown', label: 'Shutdown', method: 'POST', path: 'shutdown' },
        { key: 'lock', label: 'Lock', method: 'POST', path: 'lock' },
        { key: 'clear_passcode', label: 'Clear Passcode', method: 'POST', path: 'clear_passcode' },
        { key: 'clear_firmware_password', label: 'Clear Firmware Password', method: 'POST', path: 'clear_firmware_password' },
        { key: 'rotate_firmware_password', label: 'Rotate Firmware Password', method: 'POST', path: 'rotate_firmware_password' },
        { key: 'clear_recovery_lock_password', label: 'Clear Recovery Lock Password', method: 'POST', path: 'clear_recovery_lock_password' },
        { key: 'clear_restrictions_password', label: 'Clear Restrictions Password', method: 'POST', path: 'clear_restrictions_password' },
        { key: 'rotate_recovery_lock_password', label: 'Rotate Recovery Lock Password', method: 'POST', path: 'rotate_recovery_lock_password' },
        { key: 'rotate_filevault_key', label: 'Rotate FileVault Key', method: 'POST', path: 'rotate_filevault_key' },
        { key: 'set_admin_password', label: 'Set Admin Password', method: 'POST', path: 'set_admin_password' },
        { key: 'rotate_admin_password', label: 'Rotate Admin Password', method: 'POST', path: 'rotate_admin_password' },
        { key: 'wipe', label: 'Wipe', method: 'POST', path: 'wipe' },
        { key: 'update_os', label: 'Update OS', method: 'POST', path: 'update_os' },
        { key: 'remote_desktop_enable', label: 'Enable Remote Desktop', method: 'POST', path: 'remote_desktop' },
        { key: 'remote_desktop_disable', label: 'Disable Remote Desktop', method: 'DELETE', path: 'remote_desktop' },
        { key: 'bluetooth_enable', label: 'Enable Bluetooth', method: 'POST', path: 'bluetooth' },
        { key: 'bluetooth_disable', label: 'Disable Bluetooth', method: 'DELETE', path: 'bluetooth' },
        { key: 'set_time_zone', label: 'Set Time Zone', method: 'POST', path: 'set_time_zone' },
        { key: 'unenroll', label: 'Unenroll', method: 'POST', path: 'unenroll' },
        { key: 'delete_device', label: 'Delete Device', method: 'DELETE', path: '' }
    ];

    function initActionControls() {
        var $sel = $('#simplemdm-action-name').empty();
        actionDefinitions.forEach(function(a) {
            $sel.append('<option value="' + esc(a.key) + '">' + esc(a.label) + '</option>');
        });
        function syncMethod() {
            var key = String($sel.val() || '');
            var def = actionDefinitions.find(function(a) { return a.key === key; }) || actionDefinitions[0];
            $('#simplemdm-action-method').val(def ? def.method : '');
        }
        $sel.on('change', syncMethod);
        syncMethod();
    }

    function renderAttributeSections(attrs) {
        var sections = [
            { id: 'identity', title: 'Identity', expanded: true, keys: ['name', 'device_name', 'serial_number', 'unique_identifier', 'product_name', 'model', 'model_name', 'processor_architecture'] },
            { id: 'enrollment', title: 'Enrollment', expanded: true, keys: ['status', 'enrolled_at', 'enrollment_channels', 'is_supervised', 'is_dep_enrollment', 'dep_enrolled', 'dep_assigned', 'is_user_approved_enrollment', 'user_enrollment', 'ddm_enabled', 'auto_admin_name'] },
            { id: 'os', title: 'OS & Updates', expanded: true, keys: ['os_version', 'build_version', 'supplemental_build_version', 'supplemental_os_version_extra', 'os_update', 'time_zone'] },
            { id: 'security', title: 'Security', expanded: true, keys: ['filevault_enabled', 'filevault_recovery_key', 'firmware_password', 'firmware_password_enabled', 'recovery_lock_password', 'recovery_lock_password_enabled', 'system_integrity_protection_enabled', 'is_activation_lock_enabled', 'passcode_present', 'passcode_compliant', 'passcode_compliant_with_profiles', 'is_cloud_backup_enabled', 'is_device_locator_service_enabled', 'is_do_not_disturb_in_effect', 'lost_mode_enabled', 'remote_desktop_enabled', 'firewall'] },
            { id: 'hardware', title: 'Hardware & Capacity', expanded: false, keys: ['device_capacity', 'available_device_capacity', 'battery_level', 'hardware_encryption_caps', 'modem_firmware_version'] },
            { id: 'network', title: 'Network & Cellular', expanded: false, keys: ['wifi_mac', 'ethernet_macs', 'bluetooth_mac', 'phone_number', 'imei', 'meid', 'iccid', 'cellular_technology', 'carrier_settings_version', 'current_carrier_network', 'sim_carrier_network', 'subscriber_carrier_network', 'voice_roaming_enabled', 'data_roaming_enabled', 'is_roaming', 'subscriber_mcc', 'subscriber_mnc', 'simmnc', 'current_mcc', 'current_mnc', 'personal_hotspot_enabled', 'service_subscriptions'] },
            { id: 'location', title: 'Location', expanded: false, keys: ['location_latitude', 'location_longitude', 'location_accuracy', 'location_updated_at', 'last_seen_ip', 'last_seen_at', 'last_cloud_backup_date'] }
        ];

        var used = {};
        var html = '';

        sections.forEach(function(section) {
            var rows = '';
            section.keys.forEach(function(key) {
                if (Object.prototype.hasOwnProperty.call(attrs, key)) {
                    used[key] = true;
                    rows += '<tr><th style="width:300px;">' + esc(toTitle(key)) + '</th><td>' + renderValue(attrs[key]) + '</td></tr>';
                }
            });
            if (rows) {
                html += createSectionHtml(section.id, section.title, '<div class="simplemdm-table-wrap"><table class="simplemdm-table-modern"><tbody>' + rows + '</tbody></table></div>', section.expanded);
            }
        });

        var otherKeys = Object.keys(attrs).filter(function(k) { return !used[k]; }).sort();
        if (otherKeys.length) {
            var otherRows = '';
            otherKeys.forEach(function(key) {
                otherRows += '<tr><th style="width:300px;">' + esc(toTitle(key)) + '</th><td>' + renderValue(attrs[key]) + '</td></tr>';
            });
            html += createSectionHtml('other', 'Other', '<div class="simplemdm-table-wrap"><table class="simplemdm-table-modern"><tbody>' + otherRows + '</tbody></table></div>', false);
        }

        if (!html) {
            html = '<div class="text-muted" style="padding:6px 2px;">No attributes available.</div>';
        }
        $('#simplemdm-attributes-sections').html(html);
    }

    function renderRelationships(rels) {
        var $rels = $('#simplemdm-relationships').empty();
        Object.keys(rels).sort().forEach(function(key) {
            $rels.append('<tr><th>' + esc(toTitle(key)) + '</th><td>' + renderValue(rels[key]) + '</td></tr>');
        });
        if (!Object.keys(rels).length) {
            $rels.append('<tr><td colspan="2" class="text-muted">No relationships available.</td></tr>');
        }
    }

    function renderConnectedResources(data) {
        var $summary = $('#simplemdm-connected-summary').empty();
        var $groups = $('#simplemdm-connected-groups').empty();
        var connections = (data && data.connections) ? data.connections : [];
        var summary = (data && data.summary) ? data.summary : [];

        if (!connections.length) {
            $summary.html('<span class="text-muted">No linked resources found for this device.</span>');
            $groups.html('<div class="text-muted">No connected resources.</div>');
            return;
        }

        $summary.append('<span class="simplemdm-device-chip"><strong>Total:</strong>&nbsp;' + esc(connections.length) + '</span>');
        $summary.append('<span class="simplemdm-source-badge simplemdm-source-direct">Direct per-device</span>');
        $summary.append('<span class="simplemdm-source-badge simplemdm-source-derived">Derived relationship</span>');
        summary.forEach(function(item) {
            $summary.append(
                '<a class="simplemdm-device-chip" href="' + resourcesListingUrl('type=' + encodeURIComponent(item.type)) + '">' +
                esc(toTitle(item.type)) + ': ' + esc(item.count) + '</a>'
            );
        });

        var byType = {};
        connections.forEach(function(item) {
            var key = item.type || 'unknown';
            if (!byType[key]) {
                byType[key] = [];
            }
            byType[key].push(item);
        });

        Object.keys(byType).sort().forEach(function(typeKey) {
            var rows = '';
            byType[typeKey].forEach(function(item) {
                var type = item.type || '-';
                var id = item.id || '-';
                var name = item.name || '-';
                var reason = item.reason || '-';
                var endpoint = item.endpoint || '-';
                var sourceClass = 'simplemdm-source-derived';
                var sourceLabel = 'Derived relationship';
                if (/^devices\/\d+\/(profiles|installed_apps|users)(\/|$)/.test(String(endpoint))) {
                    sourceClass = 'simplemdm-source-direct';
                    sourceLabel = 'Direct per-device';
                }
                var itemUrl = resourcesListingUrl('type=' + encodeURIComponent(type) + '&resource_id=' + encodeURIComponent(id));
                rows += '' +
                    '<tr>' +
                        '<td><a href="' + itemUrl + '">' + esc(name) + '</a></td>' +
                        '<td style="width:140px;"><a href="' + itemUrl + '">' + esc(id) + '</a></td>' +
                        '<td style="width:170px;"><span class="simplemdm-source-badge ' + sourceClass + '">' + esc(sourceLabel) + '</span></td>' +
                        '<td style="width:220px;">' + esc(toTitle(reason)) + '</td>' +
                        '<td style="width:280px;"><a href="' + resourcesListingUrl('endpoint=' + encodeURIComponent(endpoint)) + '"><code>' + esc(endpoint) + '</code></a></td>' +
                    '</tr>';
            });

            var body = '' +
                '<div class="simplemdm-table-wrap">' +
                    '<table class="simplemdm-table-modern">' +
                        '<thead>' +
                            '<tr>' +
                                '<th>Name</th>' +
                                '<th style="width:140px;">ID</th>' +
                                '<th style="width:170px;">Source</th>' +
                                '<th style="width:220px;">Match</th>' +
                                '<th style="width:280px;">Endpoint</th>' +
                            '</tr>' +
                        '</thead>' +
                        '<tbody>' + rows + '</tbody>' +
                    '</table>' +
                '</div>';

            $groups.append(createSectionHtml('conn_' + typeKey.replace(/[^a-z0-9_]/gi, '_'), toTitle(typeKey) + ' (' + byType[typeKey].length + ')', body, false));
        });
    }

    function renderSupplementalData(data) {
        var $summary = $('#simplemdm-supplemental-summary').empty();
        var $sections = $('#simplemdm-supplemental-sections').empty();
        var detected = (data && data.detected_sources) ? data.detected_sources : [];
        var sources = (data && data.sources) ? data.sources : [];
        var summary = data && data.summary ? data.summary : null;

        if (!data || data.enabled === false) {
            $summary.html('<span class="text-muted">Supplemental data is disabled in SimpleMDM settings.</span>');
            $sections.html('<div class="text-muted">Enable supplemental data in the admin page to enrich this view.</div>');
            return;
        }

        $summary.append('<span class="simplemdm-device-chip"><strong>Detected Sources:</strong>&nbsp;' + esc(detected.filter(function(item) { return item.detected; }).length) + '</span>');
        if (summary && summary.last_refresh) {
            $summary.append('<span class="simplemdm-device-chip"><strong>Last Refresh:</strong>&nbsp;' + esc(summary.last_refresh) + '</span>');
        }
        if (summary && summary.last_refresh_status) {
            $summary.append('<span class="simplemdm-device-chip"><strong>Refresh Status:</strong>&nbsp;' + esc(summary.last_refresh_status) + '</span>');
        }

        if (!sources.length) {
            $sections.html('<div class="text-muted">No supplemental sources are configured for this device.</div>');
            return;
        }

        sources.forEach(function(source) {
            var title = source.label || source.source_id || 'Supplemental Source';
            var state = source.freshness && source.freshness.state ? source.freshness.state : 'missing';
            var detail = source.detail || {};
            var rows = '';

            rows += '<tr><th style="width:220px;">Source</th><td><span class="simplemdm-source-badge simplemdm-source-derived">' + esc(source.source_id || '-') + '</span></td></tr>';
            rows += '<tr><th>Detection</th><td>' + esc(source.detected ? 'Detected' : 'Not detected') + '</td></tr>';
            rows += '<tr><th>State</th><td>' + esc(state) + '</td></tr>';
            if (source.freshness && source.freshness.source_timestamp) {
                rows += '<tr><th>Source Timestamp</th><td>' + esc(source.freshness.source_timestamp) + '</td></tr>';
            }
            if (source.freshness && source.freshness.summary_refresh) {
                rows += '<tr><th>Summary Refresh</th><td>' + esc(source.freshness.summary_refresh) + '</td></tr>';
            }

            Object.keys(detail).forEach(function(key) {
                rows += '<tr><th>' + esc(key) + '</th><td>' + renderValue(detail[key]) + '</td></tr>';
            });

            $sections.append(
                createSectionHtml(
                    'supp_' + String(source.source_id || title).replace(/[^a-z0-9_]/gi, '_'),
                    title,
                    '<div class="simplemdm-table-wrap"><table class="simplemdm-table-modern"><thead><tr><th>Field</th><th>Value</th></tr></thead><tbody>' + rows + '</tbody></table></div>',
                    false
                )
            );
        });
    }

    function simplemdmRenderDeviceFindings(includeClosed) {
        var statuses = includeClosed
            ? 'open,acknowledged,in_progress,resolved,ignored,suppressed'
            : 'open,acknowledged,in_progress';
        $.getJSON(window.simplemdmModuleUrl('get_mcp_findings') + '/' + encodeURIComponent(serial) + '?limit=200&status=' + statuses, function(data) {
            var findings = (data && data.findings) ? data.findings : [];
            var $existing = $('[data-section-id="mcp-findings"]');
            if (!findings.length && !includeClosed) {
                $existing.remove();
                return; // PRD 14.2: section only appears when findings exist
            }
            var rows = findings.map(function(f) {
                var sev = String(f.severity || 'info').toLowerCase();
                if (sev !== 'danger' && sev !== 'warning') { sev = 'info'; }
                var meta = [
                    'first seen ' + esc(String(f.first_seen_at || '').slice(0, 10)),
                    'last seen ' + esc(String(f.last_seen_at || '').slice(0, 10)),
                    'seen ' + esc(f.occurrence_count || 1) + 'x'
                ];
                if (f.resolved_at) { meta.push('resolved ' + esc(String(f.resolved_at).slice(0, 10))); }
                var actions = '';
                if (window.simplemdmIsGlobalAdmin && f.status !== 'resolved') {
                    actions = '<span class="simplemdm-finding-actions">' +
                        ['acknowledge', 'resolve', 'ignore', 'suppress'].map(function(a) {
                            return '<button type="button" class="btn btn-xs btn-default" data-finding-action="' + a + '" data-finding-id="' + Number(f.id) + '">' + a + '</button>';
                        }).join(' ') + '</span>';
                }
                // f.data may come back as a nested JSON object rather than a
                // string; JSON.stringify keeps the disclosure readable instead
                // of rendering "[object Object]".
                var dataText = (f.data && typeof f.data === 'object') ? JSON.stringify(f.data, null, 2) : String(f.data || '');
                var dataBlock = f.data
                    ? '<details><summary class="text-muted">details</summary><pre class="simplemdm-finding-data">' + esc(dataText) + '</pre></details>'
                    : '';
                return '<div class="simplemdm-kv-row" data-finding-row="' + Number(f.id) + '">' +
                    '<span class="badge alert-' + sev + '">' + esc(sev) + '</span> ' +
                    '<span class="badge">' + esc(f.status || 'open') + '</span> ' +
                    '<strong>' + esc(f.finding_type || '-') + '</strong>' +
                    (f.category ? ' <span class="text-muted">[' + esc(f.category) + ']</span>' : '') +
                    '<div>' + esc(f.message || '') + '</div>' +
                    '<div class="text-muted" style="font-size:11px">' + esc(f.source || '') + ' &middot; ' + meta.join(' &middot; ') + '</div>' +
                    dataBlock + actions +
                '</div>';
            }).join('');
            var toggleLabel = includeClosed ? 'Hide resolved/ignored' : 'Show resolved/ignored';
            var body = '<div><button type="button" class="btn btn-xs btn-default" id="simplemdm-findings-closed-toggle" data-include-closed="' + (includeClosed ? '1' : '0') + '">' + toggleLabel + '</button></div>' + rows;
            var html = createSectionHtml('mcp-findings', 'MCP Findings (' + findings.length + ')', body, true);
            if ($existing.length) { $existing.replaceWith(html); } else { $('[data-section-id]').last().after(html); }
            if (window.simplemdmBindWheelScroll) {
                $('#simplemdm-section-mcp-findings .simplemdm-finding-data').each(function() {
                    window.simplemdmBindWheelScroll(this);
                });
            }
        });
    }
    simplemdmRenderDeviceFindings(false);

    $(document).on('click', '#simplemdm-findings-closed-toggle', function() {
        simplemdmRenderDeviceFindings($(this).attr('data-include-closed') !== '1');
    });

    $(document).on('click', '[data-finding-action]', function() {
        var action = String($(this).attr('data-finding-action'));
        var id = Number($(this).attr('data-finding-id'));
        if (['acknowledge', 'resolve', 'ignore', 'suppress'].indexOf(action) === -1 || !id) { return; }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: window.simplemdmModuleUrl(action + '_mcp_finding'),
            method: 'POST', contentType: 'application/json',
            data: JSON.stringify({ id: id })
        }).done(function() {
            var closed = $('#simplemdm-findings-closed-toggle').attr('data-include-closed') === '1';
            simplemdmRenderDeviceFindings(closed);
        }).fail(function() {
            $btn.prop('disabled', false).text(action + ' failed');
        });
    });

    $(document).on('click', '[data-section-toggle]', function() {
        var sectionId = String($(this).attr('data-section-toggle') || '');
        if (!sectionId) {
            return;
        }
        var $body = $('#simplemdm-section-' + sectionId);
        if (!$body.length) {
            return;
        }
        var expand = !$body.is(':visible');
        $body.stop(true, true).slideToggle(160);
        $(this).find('.simplemdm-section-toggle').text(expand ? '- Collapse' : '+ Expand');
    });

    $.getJSON(url, function(data) {
        if (!data || !data.length) {
            $('#simplemdm-device-msg')
                .removeClass('alert-info')
                .addClass('alert-warning')
                .text('No SimpleMDM data found for this serial.')
                .show();
            return;
        }

        var d = data[0];
        var attrs = {};
        var rels = {};

        try { attrs = d.attributes_json ? JSON.parse(d.attributes_json) : {}; } catch (e) { attrs = {}; }
        try { rels = d.relationships_json ? JSON.parse(d.relationships_json) : {}; } catch (e) { rels = {}; }
        currentAttrs = attrs;
        currentRels = rels;

        if (d.status) {
            $('#simplemdm-status-badge').text(String(d.status)).show();
        }
        if (d.assignment_group) {
            $('#simplemdm-group-badge').text(String(d.assignment_group)).show();
        }
        if (d.simplemdm_id) {
            simplemdmDeviceId = String(d.simplemdm_id);
        }

        setKpi('#simplemdm-kpi-enrollment', String(d.status || '').toLowerCase() === 'enrolled' ? '1' : '0');
        setKpi('#simplemdm-kpi-dep', d.is_dep_enrollment);
        setKpi('#simplemdm-kpi-supervised', d.is_supervised);
        setKpi('#simplemdm-kpi-filevault', d.filevault_enabled);

        addRows($('#simplemdm-overview'), [
            { label: 'Device Name', value: renderOverviewValue(d.device_name || '-') },
            { label: 'Model', value: renderOverviewValue(d.model_name || '-') },
            { label: 'OS Version', value: renderOverviewValue(d.os_version || '-') },
            { label: 'Build Version', value: renderOverviewValue(d.build_version || '-') },
            { label: 'Enrolled At', value: renderValue(d.enrolled_at) },
            { label: 'Last Seen At', value: renderValue(d.last_seen_at) },
            { label: 'Last Seen IP', value: renderOverviewValue(d.last_seen_ip || '-') },
            { label: 'SimpleMDM ID', value: renderOverviewValue(d.simplemdm_id || '-') }
        ]);

        addRows($('#simplemdm-security'), [
            { label: 'Supervised', value: boolBadge(d.is_supervised) },
            { label: 'DEP Enrollment', value: boolBadge(d.is_dep_enrollment) },
            { label: 'FileVault Enabled', value: boolBadge(d.filevault_enabled) },
            { label: 'Firewall Enabled', value: boolBadge(d.firewall_enabled) },
            { label: 'SIP Enabled', value: boolBadge(d.sip_enabled) },
            { label: 'Passcode Compliant', value: boolBadge(d.passcode_compliant) },
            { label: 'Activation Lock', value: boolBadge(d.activation_lock_enabled) },
            { label: 'Remote Desktop', value: boolBadge(d.remote_desktop_enabled) }
        ]);

        renderAttributeSections(attrs);
        renderRelationships(rels);
        $.getJSON(appUrl + '/module/simplemdm/get_device_resources/' + encodeURIComponent(serial), function(resourceData) {
            renderConnectedResources(resourceData || {});
        }).fail(function() {
            $('#simplemdm-connected-summary').html('<span class="text-danger">Failed to load connected resources.</span>');
            $('#simplemdm-connected-groups').html('<div class="text-danger">Connected resource lookup failed.</div>');
        });
        $.getJSON(appUrl + '/module/simplemdm/get_device_subresources/' + encodeURIComponent(serial), function(subresourceData) {
            renderSubresources(subresourceData || {});
        }).fail(function() {
            $('#simplemdm-subresource-summary').html('<span class="text-danger">Failed to load subresources.</span>');
            $('#simplemdm-subresource-sections').html('<div class="text-danger">Subresource lookup failed.</div>');
        });
        $.getJSON(appUrl + '/module/simplemdm/get_supplemental_data/' + encodeURIComponent(serial), function(supplementalData) {
            renderSupplementalData(supplementalData || {});
        }).fail(function() {
            $('#simplemdm-supplemental-summary').html('<span class="text-danger">Failed to load supplemental data.</span>');
            $('#simplemdm-supplemental-sections').html('<div class="text-danger">Supplemental lookup failed.</div>');
        });

        $('#simplemdm-layout').show();
    }).fail(function(xhr) {
        $('#simplemdm-device-msg')
            .removeClass('alert-info')
            .addClass('alert-danger')
            .text('Failed to load SimpleMDM data (' + xhr.status + ').')
            .show();
    });

    $('#simplemdm-show-technical').on('change', function() {
        showTechnical = $(this).is(':checked');
        renderAttributeSections(currentAttrs);
        renderRelationships(currentRels);
    });

    initActionControls();
    $('#simplemdm-run-action').on('click', function() {
        var secret = String($('#simplemdm-action-secret').val() || '').trim();
        var actionKey = String($('#simplemdm-action-name').val() || '');
        var def = actionDefinitions.find(function(a) { return a.key === actionKey; }) || null;
        if (!def) {
            $('#simplemdm-action-status').text('Unknown action').removeClass().addClass('text-danger');
            return;
        }
        if (!simplemdmDeviceId) {
            $('#simplemdm-action-status').text('Device ID not loaded').removeClass().addClass('text-danger');
            return;
        }
        if (!secret) {
            $('#simplemdm-action-status').text('Action secret required').removeClass().addClass('text-danger');
            return;
        }

        var payloadText = String($('#simplemdm-action-payload').val() || '').trim();
        var body = null;
        if (payloadText) {
            try {
                body = JSON.parse(payloadText);
            } catch (e) {
                $('#simplemdm-action-status').text('Invalid JSON payload').removeClass().addClass('text-danger');
                return;
            }
        }

        var endpoint = appUrl + '/module/simplemdm/api_devices/' + encodeURIComponent(simplemdmDeviceId);
        if (def.path) {
            endpoint += '/' + encodeURIComponent(def.path);
        }

        $('#simplemdm-action-status').text('Running...').removeClass().addClass('text-info');
        $('#simplemdm-action-result').hide().text('');

        $.ajax({
            url: endpoint,
            method: def.method,
            headers: { 'X-SIMPLEMDM-ACTION-SECRET': secret },
            contentType: 'application/json',
            dataType: 'json',
            data: body ? JSON.stringify(body) : null
        }).done(function(res) {
            $('#simplemdm-action-status').text('Success').removeClass().addClass('text-success');
            $('#simplemdm-action-result').text(JSON.stringify(res, null, 2)).show();
        }).fail(function(xhr) {
            var out = xhr && xhr.responseText ? xhr.responseText : ('HTTP ' + (xhr ? xhr.status : 'unknown'));
            $('#simplemdm-action-status').text('Failed').removeClass().addClass('text-danger');
            $('#simplemdm-action-result').text(out).show();
        });
    });
});
</script>

<?php $this->view('partials/foot'); ?>

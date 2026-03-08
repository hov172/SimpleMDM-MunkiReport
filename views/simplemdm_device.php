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
    </div>
</div>

<script>
$(document).on('appReady', function() {
    $('body').attr('data-simplemdm-disable-dashboard-grid', '1');
    var serial = $('#simplemdm-serial').text().trim();
    var url = appUrl + '/module/simplemdm/get_simplemdm_data/' + encodeURIComponent(serial);
    var showTechnical = false;
    var currentAttrs = {};
    var currentRels = {};

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
                var itemUrl = resourcesListingUrl('type=' + encodeURIComponent(type) + '&resource_id=' + encodeURIComponent(id));
                rows += '' +
                    '<tr>' +
                        '<td><a href="' + itemUrl + '">' + esc(name) + '</a></td>' +
                        '<td style="width:140px;"><a href="' + itemUrl + '">' + esc(id) + '</a></td>' +
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
});
</script>

<?php $this->view('partials/foot'); ?>

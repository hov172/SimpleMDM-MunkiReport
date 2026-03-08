<?php $this->view('partials/head', ['page' => 'clients']); ?>

<div class="col-lg-12">
    <div class="page-header" style="margin-top: 10px;">
        <h2 style="margin-bottom: 6px;">SimpleMDM Device Detail</h2>
        <div style="font-size: 15px;">
            <strong>Serial:</strong>
            <span id="simplemdm-serial"><?php echo htmlspecialchars($serial_number, ENT_QUOTES, 'UTF-8'); ?></span>
            <span id="simplemdm-status-badge" class="label label-default" style="margin-left: 8px; display:none;"></span>
            <span id="simplemdm-group-badge" class="label label-info" style="margin-left: 6px; display:none;"></span>
        </div>
    </div>

    <div id="simplemdm-device-msg" class="alert alert-info" style="display:none;"></div>

    <div id="simplemdm-layout" style="display:none;">
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>Overview</strong></div>
                    <table class="table table-condensed table-striped" style="margin-bottom:0;">
                        <tbody id="simplemdm-overview"></tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>Security & Compliance</strong></div>
                    <table class="table table-condensed table-striped" style="margin-bottom:0;">
                        <tbody id="simplemdm-security"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><strong>All Attributes</strong></div>
            <div id="simplemdm-attributes-sections"></div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <strong>Relationships</strong>
                <div class="pull-right" style="font-weight: normal;">
                    <label style="margin:0; font-size:12px;">
                        <input type="checkbox" id="simplemdm-show-technical"> Show technical details
                    </label>
                </div>
                <div class="clearfix"></div>
            </div>
            <table class="table table-condensed table-striped table-bordered" style="margin-bottom:0;">
                <thead>
                    <tr><th style="width: 280px;">Relationship</th><th>Value</th></tr>
                </thead>
                <tbody id="simplemdm-relationships"></tbody>
            </table>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Connected Resources</strong></div>
            <div class="panel-body" id="simplemdm-connected-summary">
                <span class="text-muted">Loading...</span>
            </div>
            <div id="simplemdm-connected-groups" style="padding:10px;">
                <div class="text-muted">Loading...</div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    var serial = $('#simplemdm-serial').text().trim();
    var url = appUrl + '/module/simplemdm/get_simplemdm_data/' + encodeURIComponent(serial);
    var showTechnical = false;
    var currentAttrs = {};
    var currentRels = {};

    function esc(val) {
        return $('<div>').text(String(val)).html();
    }

    function toTitle(key) {
        return key.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
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

    function boolBadge(v) {
        if (v === 1 || v === true || v === '1') {
            return '<span class="label label-success">Yes</span>';
        }
        if (v === 0 || v === false || v === '0') {
            return '<span class="label label-danger">No</span>';
        }
        return '<span class="text-muted">-</span>';
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
                    return '<span class="label label-default" style="display:inline-block;margin:0 4px 4px 0;">' + esc(item === null ? '-' : item) + '</span>';
                }).join('');
            }

            var rows = val.map(function(item, idx) {
                return '<tr><th style="width:70px;">#' + (idx + 1) + '</th><td>' + renderValue(item, depth + 1) + '</td></tr>';
            }).join('');
            return '<table class="table table-condensed table-bordered" style="margin:0;"><tbody>' + rows + '</tbody></table>';
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
            return '<tr><th style="width:220px;">' + esc(toTitle(key)) + '</th><td>' + renderValue(val[key], depth + 1) + '</td></tr>';
        }).join('');

        return '<table class="table table-condensed table-bordered" style="margin:0;"><tbody>' + objRows + '</tbody></table>';
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
            $target.append('<tr><th style="width: 220px;">' + esc(row.label) + '</th><td>' + row.value + '</td></tr>');
        });
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

    function renderAttributeSections(attrs) {
        var sections = [
            { title: 'Identity', keys: ['name', 'device_name', 'serial_number', 'unique_identifier', 'product_name', 'model', 'model_name', 'processor_architecture'] },
            { title: 'Enrollment', keys: ['status', 'enrolled_at', 'enrollment_channels', 'is_supervised', 'is_dep_enrollment', 'dep_enrolled', 'dep_assigned', 'is_user_approved_enrollment', 'user_enrollment', 'ddm_enabled', 'auto_admin_name'] },
            { title: 'OS & Updates', keys: ['os_version', 'build_version', 'supplemental_build_version', 'supplemental_os_version_extra', 'os_update', 'time_zone'] },
            { title: 'Security', keys: ['filevault_enabled', 'filevault_recovery_key', 'firmware_password', 'firmware_password_enabled', 'recovery_lock_password', 'recovery_lock_password_enabled', 'system_integrity_protection_enabled', 'is_activation_lock_enabled', 'passcode_present', 'passcode_compliant', 'passcode_compliant_with_profiles', 'is_cloud_backup_enabled', 'is_device_locator_service_enabled', 'is_do_not_disturb_in_effect', 'lost_mode_enabled', 'remote_desktop_enabled', 'firewall'] },
            { title: 'Hardware & Capacity', keys: ['device_capacity', 'available_device_capacity', 'battery_level', 'hardware_encryption_caps', 'modem_firmware_version'] },
            { title: 'Network & Cellular', keys: ['wifi_mac', 'ethernet_macs', 'bluetooth_mac', 'phone_number', 'imei', 'meid', 'iccid', 'cellular_technology', 'carrier_settings_version', 'current_carrier_network', 'sim_carrier_network', 'subscriber_carrier_network', 'voice_roaming_enabled', 'data_roaming_enabled', 'is_roaming', 'subscriber_mcc', 'subscriber_mnc', 'simmnc', 'current_mcc', 'current_mnc', 'personal_hotspot_enabled', 'service_subscriptions'] },
            { title: 'Location', keys: ['location_latitude', 'location_longitude', 'location_accuracy', 'location_updated_at', 'last_seen_ip', 'last_seen_at', 'last_cloud_backup_date'] },
        ];

        var used = {};
        var html = '';

        sections.forEach(function(section) {
            var rows = '';
            section.keys.forEach(function(key) {
                if (Object.prototype.hasOwnProperty.call(attrs, key)) {
                    used[key] = true;
                    rows += '<tr><th style="width:280px;">' + esc(toTitle(key)) + '</th><td>' + renderValue(attrs[key]) + '</td></tr>';
                }
            });
            if (rows) {
                html += '' +
                    '<div class="panel panel-default" style="margin:10px;">' +
                        '<div class="panel-heading"><strong>' + esc(section.title) + '</strong></div>' +
                        '<table class="table table-condensed table-striped table-bordered" style="margin-bottom:0;">' +
                            '<tbody>' + rows + '</tbody>' +
                        '</table>' +
                    '</div>';
            }
        });

        var otherKeys = Object.keys(attrs).filter(function(k) { return !used[k]; }).sort();
        if (otherKeys.length) {
            var otherRows = '';
            otherKeys.forEach(function(key) {
                otherRows += '<tr><th style="width:280px;">' + esc(toTitle(key)) + '</th><td>' + renderValue(attrs[key]) + '</td></tr>';
            });
            html += '' +
                '<div class="panel panel-default" style="margin:10px;">' +
                    '<div class="panel-heading"><strong>Other</strong></div>' +
                    '<table class="table table-condensed table-striped table-bordered" style="margin-bottom:0;">' +
                        '<tbody>' + otherRows + '</tbody>' +
                    '</table>' +
                '</div>';
        }

        if (!html) {
            html = '<div class="text-muted" style="padding:12px;">No attributes available.</div>';
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

        $summary.prepend(
            '<span class="label label-primary" style="display:inline-block;margin:0 8px 6px 0;padding:6px 8px;">Total: ' +
            esc(connections.length) +
            '</span>'
        );

        summary.forEach(function(item) {
            $summary.append(
                '<a class="label label-default" style="display:inline-block;margin:0 6px 6px 0;padding:6px 8px;" ' +
                'href="' + resourcesListingUrl('type=' + encodeURIComponent(item.type)) + '">' +
                esc(toTitle(item.type)) + ': ' + esc(item.count) +
                '</a>'
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
                var itemUrl = resourcesListingUrl(
                    'type=' + encodeURIComponent(type) + '&resource_id=' + encodeURIComponent(id)
                );
                rows +=
                    '<tr>' +
                        '<td><a href="' + itemUrl + '">' + esc(name) + '</a></td>' +
                        '<td style="width:120px;"><a href="' + itemUrl + '">' + esc(id) + '</a></td>' +
                        '<td style="width:220px;">' + esc(toTitle(reason)) + '</td>' +
                        '<td style="width:260px;"><a href="' + resourcesListingUrl('endpoint=' + encodeURIComponent(endpoint)) + '"><code>' + esc(endpoint) + '</code></a></td>' +
                    '</tr>';
            });

            $groups.append(
                '<div class="panel panel-default" style="margin-bottom:10px;">' +
                    '<div class="panel-heading">' +
                        '<strong>' + esc(toTitle(typeKey)) + '</strong>' +
                        '<span class="badge pull-right">' + esc(byType[typeKey].length) + '</span>' +
                    '</div>' +
                    '<table class="table table-condensed table-striped table-bordered" style="margin-bottom:0;">' +
                        '<thead>' +
                            '<tr>' +
                                '<th>Name</th>' +
                                '<th style="width:120px;">ID</th>' +
                                '<th style="width:220px;">Match</th>' +
                                '<th style="width:260px;">Endpoint</th>' +
                            '</tr>' +
                        '</thead>' +
                        '<tbody>' + rows + '</tbody>' +
                    '</table>' +
                '</div>'
            );
        });
    }

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
            $('#simplemdm-status-badge')
                .text(d.status)
                .removeClass('label-default')
                .addClass(d.status === 'enrolled' ? 'label-success' : 'label-warning')
                .show();
        }
        if (d.assignment_group) {
            $('#simplemdm-group-badge').text(d.assignment_group).show();
        }

        addRows($('#simplemdm-overview'), [
            {label: 'Device Name', value: renderOverviewValue(d.device_name || '-')},
            {label: 'Model', value: renderOverviewValue(d.model_name || '-')},
            {label: 'OS Version', value: renderOverviewValue(d.os_version || '-')},
            {label: 'Build Version', value: renderOverviewValue(d.build_version || '-')},
            {label: 'Enrolled At', value: renderValue(d.enrolled_at)},
            {label: 'Last Seen At', value: renderValue(d.last_seen_at)},
            {label: 'Last Seen IP', value: renderOverviewValue(d.last_seen_ip || '-')},
            {label: 'SimpleMDM ID', value: renderOverviewValue(d.simplemdm_id || '-')}
        ]);

        addRows($('#simplemdm-security'), [
            {label: 'Supervised', value: boolBadge(d.is_supervised)},
            {label: 'DEP Enrollment', value: boolBadge(d.is_dep_enrollment)},
            {label: 'FileVault Enabled', value: boolBadge(d.filevault_enabled)},
            {label: 'Firewall Enabled', value: boolBadge(d.firewall_enabled)},
            {label: 'SIP Enabled', value: boolBadge(d.sip_enabled)},
            {label: 'Passcode Compliant', value: boolBadge(d.passcode_compliant)},
            {label: 'Activation Lock', value: boolBadge(d.activation_lock_enabled)},
            {label: 'Remote Desktop', value: boolBadge(d.remote_desktop_enabled)}
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

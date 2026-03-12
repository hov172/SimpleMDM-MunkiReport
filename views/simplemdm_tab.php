<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>
<style>
#simplemdm-tab .simplemdm-tab-panel {
    border: 1px solid var(--simplemdm-border);
    border-radius: 12px;
    background: var(--simplemdm-card-bg);
    padding: 10px 12px;
    margin-bottom: 12px;
}
#simplemdm-tab .table > thead > tr > th {
    background: var(--simplemdm-heading-bg);
    color: var(--simplemdm-ink);
    border-bottom: 1px solid var(--simplemdm-border);
}
#simplemdm-tab .table > tbody > tr > th,
#simplemdm-tab .table > tbody > tr > td {
    border-color: var(--simplemdm-border);
    color: var(--simplemdm-ink);
}
#simplemdm-tab .table-striped > tbody > tr:nth-of-type(odd) {
    background: rgba(120, 160, 200, 0.08);
}
#simplemdm-tab .simplemdm-tab-chip {
    display: inline-block;
    border-radius: 999px;
    padding: 4px 9px;
    margin: 0 6px 6px 0;
    font-weight: 700;
    font-size: 12px;
    border: 1px solid var(--simplemdm-border);
    background: var(--simplemdm-surface-alt);
    color: var(--simplemdm-ink);
}
</style>
<div id="simplemdm-tab" class="tab-pane">
    <h3 data-i18n="simplemdm.title"></h3>
    <button id="simplemdm-sync-now" class="btn btn-default btn-xs pull-right" style="margin-top: -30px;">
        <i class="fa fa-refresh"></i> <span data-i18n="simplemdm.sync_now"></span>
    </button>

    <div id="simplemdm-tab-msg" class="alert alert-info" style="display:none;">
        <span data-i18n="simplemdm.no_data"></span>
    </div>

    <div id="simplemdm-tab-data" style="display:none;">
        <div class="simplemdm-tab-panel">
        <table class="table table-striped">
            <tbody>
                <tr>
                    <th data-i18n="simplemdm.simplemdm_id"></th>
                    <td class="simplemdm-simplemdm_id"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.device_name"></th>
                    <td class="simplemdm-device_name"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.status"></th>
                    <td class="simplemdm-status"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.enrolled_at"></th>
                    <td class="simplemdm-enrolled_at"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.last_seen_at"></th>
                    <td class="simplemdm-last_seen_at"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.last_seen_ip"></th>
                    <td class="simplemdm-last_seen_ip"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.model_name"></th>
                    <td class="simplemdm-model_name"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.os_version"></th>
                    <td class="simplemdm-os_version"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.build_version"></th>
                    <td class="simplemdm-build_version"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.is_supervised"></th>
                    <td class="simplemdm-is_supervised"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.is_dep_enrollment"></th>
                    <td class="simplemdm-is_dep_enrollment"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.dep_enrolled"></th>
                    <td class="simplemdm-dep_enrolled"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.dep_assigned"></th>
                    <td class="simplemdm-dep_assigned"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.filevault_enabled"></th>
                    <td class="simplemdm-filevault_enabled"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.firewall_enabled"></th>
                    <td class="simplemdm-firewall_enabled"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.sip_enabled"></th>
                    <td class="simplemdm-sip_enabled"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.remote_desktop_enabled"></th>
                    <td class="simplemdm-remote_desktop_enabled"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.activation_lock_enabled"></th>
                    <td class="simplemdm-activation_lock_enabled"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.passcode_compliant"></th>
                    <td class="simplemdm-passcode_compliant"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.device_capacity"></th>
                    <td class="simplemdm-device_capacity"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.available_device_capacity"></th>
                    <td class="simplemdm-available_device_capacity"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.battery_level"></th>
                    <td class="simplemdm-battery_level"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.assignment_group"></th>
                    <td class="simplemdm-assignment_group"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.unique_identifier"></th>
                    <td class="simplemdm-unique_identifier"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.imei"></th>
                    <td class="simplemdm-imei"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.meid"></th>
                    <td class="simplemdm-meid"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.iccid"></th>
                    <td class="simplemdm-iccid"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.phone_number"></th>
                    <td class="simplemdm-phone_number"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.bluetooth_mac"></th>
                    <td class="simplemdm-bluetooth_mac"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.wifi_mac"></th>
                    <td class="simplemdm-wifi_mac"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.current_carrier_network"></th>
                    <td class="simplemdm-current_carrier_network"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.personal_hotspot_enabled"></th>
                    <td class="simplemdm-personal_hotspot_enabled"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.cellular_technology"></th>
                    <td class="simplemdm-cellular_technology"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.modem_firmware_version"></th>
                    <td class="simplemdm-modem_firmware_version"></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.attributes_json"></th>
                    <td><pre class="simplemdm-attributes_json" style="white-space: pre-wrap; word-break: break-word; margin: 0;"></pre></td>
                </tr>
                <tr>
                    <th data-i18n="simplemdm.relationships_json"></th>
                    <td><pre class="simplemdm-relationships_json" style="white-space: pre-wrap; word-break: break-word; margin: 0;"></pre></td>
                </tr>
            </tbody>
        </table>
        </div>

        <h4 data-i18n="simplemdm.custom_attributes"></h4>
        <div class="simplemdm-tab-panel">
        <table class="table table-striped">
            <tbody id="simplemdm-custom-attributes">
            </tbody>
        </table>
        </div>

        <h4>Connected Resources</h4>
        <div id="simplemdm-tab-resources-summary" class="simplemdm-tab-panel" style="margin-bottom:8px;"></div>
        <div class="simplemdm-tab-panel">
        <table class="table table-striped table-condensed table-bordered">
            <thead>
                <tr>
                    <th style="width: 160px;">Type</th>
                    <th>Name</th>
                    <th style="width: 100px;">ID</th>
                    <th style="width: 180px;">Match</th>
                </tr>
            </thead>
            <tbody id="simplemdm-tab-resources">
                <tr><td colspan="4" class="text-muted">Loading...</td></tr>
            </tbody>
        </table>
        </div>

        <h4>Supplemental Data</h4>
        <div id="simplemdm-tab-supplemental-summary" class="simplemdm-tab-panel" style="margin-bottom:8px;"></div>
        <div class="simplemdm-tab-panel">
        <table class="table table-striped table-condensed table-bordered">
            <thead>
                <tr>
                    <th style="width: 180px;">Source</th>
                    <th style="width: 120px;">State</th>
                    <th>Highlights</th>
                </tr>
            </thead>
            <tbody id="simplemdm-tab-supplemental">
                <tr><td colspan="3" class="text-muted">Loading...</td></tr>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function(e, lang) {
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

    function toTitle(v) {
        return String(v || '').replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
    }

    function esc(val) {
        return $('<div>').text(String(val)).html();
    }

    function renderTabResources(payload) {
        var summary = (payload && payload.summary) ? payload.summary : [];
        var rows = (payload && payload.connections) ? payload.connections : [];
        var $summary = $('#simplemdm-tab-resources-summary').empty();
        var $tbody = $('#simplemdm-tab-resources').empty();

        if (!rows.length) {
            $summary.html('<span class="text-muted">No linked resources found.</span>');
            $tbody.append('<tr><td colspan="4" class="text-muted">No connected resources.</td></tr>');
            return;
        }

        summary.forEach(function(item) {
            $summary.append(
                '<a class="simplemdm-tab-chip" ' +
                'href="' + resourcesListingUrl('type=' + encodeURIComponent(item.type)) + '">' +
                esc(toTitle(item.type)) + ': ' + esc(item.count) +
                '</a>'
            );
        });

        rows.forEach(function(item) {
            var itemUrl = resourcesListingUrl(
                'type=' + encodeURIComponent(item.type || '') + '&resource_id=' + encodeURIComponent(item.id || '')
            );
            $tbody.append(
                '<tr>' +
                    '<td><a href="' + itemUrl + '">' + esc(toTitle(item.type || '-')) + '</a></td>' +
                    '<td><a href="' + itemUrl + '">' + esc(item.name || '-') + '</a></td>' +
                    '<td><a href="' + itemUrl + '">' + esc(item.id || '-') + '</a></td>' +
                    '<td>' + esc(toTitle(item.reason || '-')) + '</td>' +
                '</tr>'
            );
        });
    }

    function renderTabSupplemental(payload) {
        var $summary = $('#simplemdm-tab-supplemental-summary').empty();
        var $tbody = $('#simplemdm-tab-supplemental').empty();
        var sources = (payload && payload.sources) ? payload.sources : [];
        var detected = (payload && payload.detected_sources) ? payload.detected_sources : [];
        var summary = payload && payload.summary ? payload.summary : null;

        if (!payload || payload.enabled === false) {
            $summary.html('<span class="text-muted">Supplemental data is disabled in SimpleMDM settings.</span>');
            $tbody.append('<tr><td colspan="3" class="text-muted">Supplemental data is disabled.</td></tr>');
            return;
        }

        $summary
            .append('<span class="simplemdm-tab-chip"><strong>Detected:</strong>&nbsp;' + esc(detected.filter(function(item) { return item.detected; }).length) + '</span>')
            .append(summary && summary.last_refresh ? '<span class="simplemdm-tab-chip"><strong>Last Refresh:</strong>&nbsp;' + esc(summary.last_refresh) + '</span>' : '')
            .append(summary && summary.last_refresh_status ? '<span class="simplemdm-tab-chip"><strong>Status:</strong>&nbsp;' + esc(summary.last_refresh_status) + '</span>' : '');

        if (!sources.length) {
            $tbody.append('<tr><td colspan="3" class="text-muted">No supplemental sources available.</td></tr>');
            return;
        }

        sources.forEach(function(source) {
            var highlights = [];
            var detail = source && source.detail ? source.detail : {};
            Object.keys(detail).slice(0, 3).forEach(function(key) {
                if (detail[key] !== null && detail[key] !== '') {
                    highlights.push('<strong>' + esc(key) + ':</strong> ' + esc(typeof detail[key] === 'object' ? JSON.stringify(detail[key]) : detail[key]));
                }
            });
            if (!highlights.length) {
                highlights.push('<span class="text-muted">No summary available</span>');
            }

            $tbody.append(
                '<tr>' +
                    '<td><span class="simplemdm-tab-chip">' + esc(source.label || source.source_id || '-') + '</span></td>' +
                    '<td>' + esc((source.freshness && source.freshness.state) ? source.freshness.state : 'missing') + '</td>' +
                    '<td>' + highlights.join('<br>') + '</td>' +
                '</tr>'
            );
        });
    }

    // Get simplemdm data for this machine
    $.getJSON(appUrl + '/module/simplemdm/get_simplemdm_data/' + serialNumber, function(data) {
        if (data.length) {
            // Update badge count
            $('#simplemdm-cnt').text(data.length);

            var d = data[0];

            // Populate fields
            if (d.simplemdm_id) {
                $('.simplemdm-simplemdm_id').html('<a href="https://a.simplemdm.com/devices/' + d.simplemdm_id + '" target="_blank">' + d.simplemdm_id + ' <i class="fa fa-external-link"></i></a>');
            } else {
                $('.simplemdm-simplemdm_id').text('-');
            }
            $('.simplemdm-device_name').text(d.device_name || '-');

            // Status with badge
            var statusClass = d.status === 'enrolled' ? 'label-success' : 'label-danger';
            $('.simplemdm-status').html('<span class="label ' + statusClass + '">' + (d.status || '-') + '</span>');

            $('.simplemdm-enrolled_at').text(d.enrolled_at || '-');
            $('.simplemdm-last_seen_at').text(d.last_seen_at || '-');
            $('.simplemdm-last_seen_ip').text(d.last_seen_ip || '-');
            $('.simplemdm-model_name').text(d.model_name || '-');
            $('.simplemdm-os_version').text(d.os_version || '-');
            $('.simplemdm-build_version').text(d.build_version || '-');

            // Boolean fields with Yes/No badges
            var boolFields = [
                'is_supervised', 'is_dep_enrollment', 'dep_enrolled', 'dep_assigned',
                'filevault_enabled', 'firewall_enabled', 'sip_enabled',
                'remote_desktop_enabled', 'activation_lock_enabled', 'passcode_compliant',
                'personal_hotspot_enabled'
            ];

            boolFields.forEach(function(field) {
                var val = d[field];
                if (val === 1 || val === true) {
                    $('.simplemdm-' + field).html('<span class="label label-success">' + i18n.t('yes') + '</span>');
                } else if (val === 0 || val === false) {
                    $('.simplemdm-' + field).html('<span class="label label-danger">' + i18n.t('no') + '</span>');
                } else {
                    $('.simplemdm-' + field).text('-');
                }
            });

            // Text fields
            var textFields = [
                'unique_identifier', 'imei', 'meid', 'iccid', 'phone_number',
                'bluetooth_mac', 'wifi_mac', 'current_carrier_network',
                'cellular_technology', 'modem_firmware_version'
            ];
            textFields.forEach(function(field) {
                $('.simplemdm-' + field).text(d[field] || '-');
            });

            // Capacity fields
            if (d.device_capacity) {
                $('.simplemdm-device_capacity').text(d.device_capacity + ' GB');
            } else {
                $('.simplemdm-device_capacity').text('-');
            }
            if (d.available_device_capacity) {
                $('.simplemdm-available_device_capacity').text(d.available_device_capacity + ' GB');
            } else {
                $('.simplemdm-available_device_capacity').text('-');
            }

            $('.simplemdm-battery_level').text(d.battery_level || '-');
            $('.simplemdm-assignment_group').text(d.assignment_group || '-');

            function prettyJsonOrDash(raw) {
                if (!raw) {
                    return '-';
                }
                try {
                    var parsed = (typeof raw === 'string') ? JSON.parse(raw) : raw;
                    return JSON.stringify(parsed, null, 2);
                } catch (e) {
                    return String(raw);
                }
            }
            $('.simplemdm-attributes_json').text(prettyJsonOrDash(d.attributes_json));
            $('.simplemdm-relationships_json').text(prettyJsonOrDash(d.relationships_json));

            // Handle Custom Attributes
            var $attrsTable = $('#simplemdm-custom-attributes').empty();
            if (d.custom_attributes) {
                try {
                    var attrs = JSON.parse(d.custom_attributes);
                    if (Object.keys(attrs).length > 0) {
                        for (var key in attrs) {
                            $attrsTable.append('<tr><th>' + key + '</th><td>' + attrs[key] + '</td></tr>');
                        }
                    } else {
                        $attrsTable.append('<tr><td colspan="2" class="text-muted">No custom attributes</td></tr>');
                    }
                } catch (e) {
                    $attrsTable.append('<tr><td colspan="2" class="text-danger">Error parsing attributes</td></tr>');
                }
            } else {
                $attrsTable.append('<tr><td colspan="2" class="text-muted">No custom attributes</td></tr>');
            }

            $('#simplemdm-tab-data').show();

            $.getJSON(appUrl + '/module/simplemdm/get_device_resources/' + serialNumber, function(resourceData) {
                renderTabResources(resourceData || {});
            }).fail(function() {
                $('#simplemdm-tab-resources-summary').html('<span class="text-danger">Failed to load connected resources.</span>');
                $('#simplemdm-tab-resources').html('<tr><td colspan="4" class="text-danger">Lookup failed.</td></tr>');
            });
            $.getJSON(appUrl + '/module/simplemdm/get_supplemental_data/' + serialNumber, function(supplementalData) {
                renderTabSupplemental(supplementalData || {});
            }).fail(function() {
                $('#simplemdm-tab-supplemental-summary').html('<span class="text-danger">Failed to load supplemental data.</span>');
                $('#simplemdm-tab-supplemental').html('<tr><td colspan="3" class="text-danger">Lookup failed.</td></tr>');
            });
        } else {
            $('#simplemdm-tab-msg').show();
        }
    });

    // Handle Sync Now button
    $('#simplemdm-sync-now').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).find('i').addClass('fa-spin');
        
        $.getJSON(appUrl + '/module/simplemdm/sync_device/' + serialNumber, function(data) {
            if (data.status === 'success') {
                alert(i18n.t('simplemdm.sync_requested'));
            } else {
                alert('Error: ' + data.message);
            }
            $btn.prop('disabled', false).find('i').removeClass('fa-spin');
        });
    });
});
</script>

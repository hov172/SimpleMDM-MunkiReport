<?php
$this->view('partials/head');
include_once __DIR__ . '/simplemdm_widget_modern_assets.php';

$simplemdm_widgets = [];
$provides_path = APP_ROOT . 'local/modules/simplemdm/provides.yml';
if (is_readable($provides_path)) {
    try {
        $provides = \Symfony\Component\Yaml\Yaml::parseFile($provides_path);
        if (isset($provides['widgets']) && is_array($provides['widgets'])) {
            foreach ($provides['widgets'] as $widget_id => $info) {
                $id = trim((string)$widget_id);
                if ($id === '') {
                    continue;
                }
                $label = str_replace('simplemdm_rt_', '', $id);
                $label = str_replace('simplemdm_', '', $label);
                $label = ucwords(str_replace('_', ' ', $label));
                $simplemdm_widgets[] = [
                    'id' => $id,
                    'label' => $label,
                ];
            }
        }
    } catch (\Throwable $e) {
        $simplemdm_widgets = [];
    }
}
?>

<style>
.simplemdm-admin-wrap {
    margin-top: 10px;
}
.simplemdm-admin-wrap .simplemdm-modern-widget {
    margin-bottom: 14px;
}
.simplemdm-admin-wrap .panel-title {
    text-transform: none;
    letter-spacing: 0.1px;
}
.simplemdm-admin-wrap .form-control {
    border-radius: 10px;
    border: 1px solid var(--simplemdm-border);
    background: var(--simplemdm-surface);
    color: var(--simplemdm-ink);
}
.simplemdm-admin-wrap .table > tbody > tr > th,
.simplemdm-admin-wrap .table > tbody > tr > td {
    border-color: var(--simplemdm-border);
    color: var(--simplemdm-ink);
}
</style>

<div class="container simplemdm-admin-wrap">
    <div class="row">
        <div class="col-lg-12">
            <h1><i class="fa fa-cog"></i> SimpleMDM Settings</h1>
            <p class="lead">Configure your SimpleMDM API connection and monitor sync health.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default simplemdm-modern-widget">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-key"></i> API Configuration</h3>
                </div>
                <div class="panel-body">
                    <form id="simplemdm-config-form">
                        <div class="form-group">
                            <label for="api_key">SimpleMDM API Key</label>
                            <input type="password" class="form-control" id="api_key" name="api_key" placeholder="Enter your API key">
                            <p class="help-block">Retrieve your key from <strong>Settings &rarr; API</strong> in the SimpleMDM console.</p>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                        <span id="save-status" style="margin-left: 10px;"></span>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel panel-default simplemdm-modern-widget">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-refresh"></i> Sync Status</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <tr>
                            <th>Queue State</th>
                            <td id="sync-request-state">idle</td>
                        </tr>
                        <tr>
                            <th>Requested At</th>
                            <td id="sync-requested-at">-</td>
                        </tr>
                        <tr>
                            <th>Started At</th>
                            <td id="sync-started-at">-</td>
                        </tr>
                        <tr>
                            <th>Last Sync Status</th>
                            <td id="sync-status">-</td>
                        </tr>
                        <tr>
                            <th>Last Sync Time</th>
                            <td id="sync-time">-</td>
                        </tr>
                    </table>
                    <button type="button" class="btn btn-default" id="simplemdm-sync-now">Sync Now</button>
                    <span id="sync-request-message" style="margin-left: 10px;"></span>
                    <p class="text-muted small" style="margin-top:10px;">This queues a sync request. The host-side cron or manual runner still executes <code>simplemdm_sync.py</code>.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default simplemdm-modern-widget">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-th-large"></i> Widget Visibility</h3>
                </div>
                <div class="panel-body">
                    <form id="simplemdm-widget-form">
                        <?php foreach ($simplemdm_widgets as $widget): ?>
                            <?php $key = 'widget_' . $widget['id']; ?>
                            <input type="hidden" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="0">
                            <div class="checkbox">
                                <label>
                                    <input
                                        type="checkbox"
                                        class="simplemdm-widget-toggle"
                                        id="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                        data-widget-key="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                        value="1"
                                    >
                                    <?= htmlspecialchars($widget['label'], ENT_QUOTES, 'UTF-8') ?>
                                </label>
                            </div>
                        <?php endforeach; ?>

                        <button type="submit" class="btn btn-primary">Save Widget Settings</button>
                        <span id="widget-save-status" style="margin-left: 10px;"></span>
                        <p class="text-muted small" style="margin-top:10px;">Applies to the SimpleMDM report page widgets.</p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default simplemdm-modern-widget">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-sliders"></i> Advanced Sync & Compliance</h3>
                </div>
                <div class="panel-body">
                    <form id="simplemdm-advanced-form">
                        <div class="form-group">
                            <label for="webhook_secret">Webhook Secret</label>
                            <input type="password" class="form-control" id="webhook_secret" name="webhook_secret" placeholder="Optional shared secret">
                            <p class="help-block">Used by `module/simplemdm/index?op=webhook` via `X-SIMPLEMDM-WEBHOOK-SECRET`.</p>
                        </div>
                        <div class="form-group">
                            <label for="action_api_secret">Action API Secret</label>
                            <input type="password" class="form-control" id="action_api_secret" name="action_api_secret" placeholder="Required for mutating device passthrough actions">
                            <p class="help-block">Required header for `POST/PATCH/DELETE` under `module/simplemdm/api_devices/...` via `X-SIMPLEMDM-ACTION-SECRET`.</p>
                        </div>
                        <div class="form-group">
                            <label for="compliance_min_os">Compliance Minimum OS</label>
                            <input type="text" class="form-control" id="compliance_min_os" name="compliance_min_os" placeholder="e.g. 14.0">
                            <p class="help-block">Optional minimum OS target for compliance widget calculations.</p>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="sync_delta_enabled" name="sync_delta_enabled" value="1">
                                Enable delta sync mode (when supported by API)
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="sync_commands_enabled" name="sync_commands_enabled" value="1">
                                Enable command status sync
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="enable_scheduled_sync" name="enable_scheduled_sync" value="1">
                                Enable scheduled sync (cron must still call script)
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="sync_interval_minutes">Scheduled sync interval (minutes)</label>
                            <input type="number" min="1" step="1" class="form-control" id="sync_interval_minutes" name="sync_interval_minutes" placeholder="15">
                            <p class="help-block">Used when script runs with <code>--respect-schedule</code>. Scheduling is enabled/disabled by the checkbox above.</p>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="sync_device_subresources_enabled" name="sync_device_subresources_enabled" value="1">
                                Enable deep per-device subresource sync (profiles/apps/users)
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="device_subresource_limit">Per-device deep sync limit</label>
                            <input type="number" min="0" step="1" class="form-control" id="device_subresource_limit" name="device_subresource_limit" placeholder="0 = all devices">
                            <p class="help-block">Set `0` for all devices, or cap the number of devices to reduce API load.</p>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Advanced Settings</button>
                        <span id="advanced-save-status" style="margin-left: 10px;"></span>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    function setSyncMessage(text, cssClass) {
        $('#sync-request-message').text(text).removeClass().addClass(cssClass || 'text-muted');
    }

    function updateSyncMessageFromState(data) {
        var state = String(data.sync_request_state || 'idle');
        var requestedAt = String(data.sync_requested_at || '').trim();
        var lastStatus = String(data.last_sync_status || '').trim();
        var lastTime = String(data.last_sync_time || '').trim();

        if (state === 'queued') {
            setSyncMessage(
                requestedAt ? 'Sync is queued and waiting for cron/manual runner. Requested at ' + requestedAt + '.' : 'Sync is queued and waiting for cron/manual runner.',
                'text-info'
            );
            return;
        }

        if (state === 'running') {
            setSyncMessage('Sync is currently running.', 'text-info');
            return;
        }

        if (lastStatus !== '' && lastTime !== '') {
            setSyncMessage('Last completed status: ' + lastStatus + ' at ' + lastTime + '.', 'text-muted');
            return;
        }

        setSyncMessage('No sync is currently queued.', 'text-muted');
    }

    function renderSyncStatus(data) {
        $('#sync-status').text(data.last_sync_status || 'Never');
        $('#sync-time').text(data.last_sync_time || '-');
        $('#sync-request-state').text(data.sync_request_state || 'idle');
        $('#sync-requested-at').text(data.sync_requested_at || '-');
        $('#sync-started-at').text(data.sync_started_at || '-');
        $('#simplemdm-sync-now').prop('disabled', String(data.sync_request_state || 'idle') === 'running');
        updateSyncMessageFromState(data);
    }

    function renderConfig(data) {
        if (data.api_key) {
            $('#api_key').val(data.api_key);
        }
        renderSyncStatus(data);

        function isEnabled(v) {
            return v === undefined || v === null || String(v) !== '0';
        }
        function pickValue(v, fallback) {
            return (v === undefined || v === null || String(v) === '') ? String(fallback) : String(v);
        }
        $('.simplemdm-widget-toggle').each(function() {
            var key = $(this).data('widget-key');
            $(this).prop('checked', isEnabled(data[key]));
        });
        $('#webhook_secret').val(data.webhook_secret || '');
        $('#action_api_secret').val(data.action_api_secret || '');
        $('#compliance_min_os').val(data.compliance_min_os || '');
        $('#sync_delta_enabled').prop('checked', String(data.sync_delta_enabled || '0') === '1');
        $('#sync_commands_enabled').prop('checked', String(data.sync_commands_enabled || '0') === '1');
        $('#enable_scheduled_sync').prop('checked', String(data.enable_scheduled_sync || '0') === '1');
        $('#sync_interval_minutes').val(pickValue(data.sync_interval_minutes, '15'));
        $('#sync_device_subresources_enabled').prop('checked', String(data.sync_device_subresources_enabled || '0') === '1');
        $('#device_subresource_limit').val(pickValue(data.device_subresource_limit, '0'));
    }

    function loadConfig() {
        $.getJSON(appUrl + '/module/simplemdm/get_config', function(data) {
            renderConfig(data);
        });
    }

    function refreshSyncStatus() {
        $.getJSON(appUrl + '/module/simplemdm/get_config', function(data) {
            renderSyncStatus(data);
        });
    }

    // Load existing config
    loadConfig();
    window.setInterval(refreshSyncStatus, 15000);

    $('#simplemdm-sync-now').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        setSyncMessage('Queueing sync request...', 'text-info');

        $.post(appUrl + '/module/simplemdm/request_sync', {}, function(data) {
            if (data.status === 'success') {
                refreshSyncStatus();
            } else {
                setSyncMessage('Error: ' + (data.message || 'Unknown'), 'text-danger');
                $btn.prop('disabled', false);
            }
        }, 'json').fail(function(xhr) {
            var msg = 'Request failed';
            if (xhr && xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) {
                msg = xhr.responseJSON.message || xhr.responseJSON.error;
            }
            setSyncMessage('Error: ' + msg, 'text-danger');
            $btn.prop('disabled', false);
        });
    });

    // Handle form submission
    $('#simplemdm-config-form').on('submit', function(e) {
        e.preventDefault();
        $('#save-status').text('Saving...').removeClass().addClass('text-info');
        
        $.post(appUrl + '/module/simplemdm/save_config', $(this).serialize(), function(data) {
            if (data.status === 'success') {
                $('#save-status').text('Saved successfully!').removeClass().addClass('text-success');
                setTimeout(function() { $('#save-status').fadeOut(); }, 3000);
            } else {
                $('#save-status').text('Error: ' + (data.message || 'Unknown')).removeClass().addClass('text-danger');
            }
        }, 'json').fail(function(xhr) {
            var msg = 'Request failed';
            if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            }
            $('#save-status').text('Error: ' + msg).removeClass().addClass('text-danger');
        });
    });

    $('#simplemdm-widget-form').on('submit', function(e) {
        e.preventDefault();
        $('#widget-save-status').text('Saving...').removeClass().addClass('text-info');

        var payload = {};
        $('.simplemdm-widget-toggle').each(function() {
            var key = $(this).data('widget-key');
            payload[key] = $(this).is(':checked') ? '1' : '0';
        });

        $.post(appUrl + '/module/simplemdm/save_config', payload, function(data) {
            if (data.status === 'success') {
                $('#widget-save-status').text('Saved successfully!').removeClass().addClass('text-success');
                setTimeout(function() { $('#widget-save-status').fadeOut(); }, 3000);
            } else {
                $('#widget-save-status').text('Error: ' + (data.message || 'Unknown')).removeClass().addClass('text-danger');
            }
        }, 'json').fail(function(xhr) {
            var msg = 'Request failed';
            if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            }
            $('#widget-save-status').text('Error: ' + msg).removeClass().addClass('text-danger');
        });
    });

    $('#simplemdm-advanced-form').on('submit', function(e) {
        e.preventDefault();
        $('#advanced-save-status').text('Saving...').removeClass().addClass('text-info');

        var payload = {
            webhook_secret: $('#webhook_secret').val() || '',
            action_api_secret: $('#action_api_secret').val() || '',
            compliance_min_os: $('#compliance_min_os').val() || '',
            sync_delta_enabled: $('#sync_delta_enabled').is(':checked') ? '1' : '0',
            sync_commands_enabled: $('#sync_commands_enabled').is(':checked') ? '1' : '0',
            enable_scheduled_sync: $('#enable_scheduled_sync').is(':checked') ? '1' : '0',
            sync_interval_minutes: String($('#sync_interval_minutes').val() || '15'),
            sync_device_subresources_enabled: $('#sync_device_subresources_enabled').is(':checked') ? '1' : '0',
            device_subresource_limit: String($('#device_subresource_limit').val() || '0')
        };

        $.post(appUrl + '/module/simplemdm/save_config', payload, function(data) {
            if (data.status === 'success') {
                $('#advanced-save-status').text('Saved successfully!').removeClass().addClass('text-success');
                setTimeout(function() { $('#advanced-save-status').fadeOut(); }, 3000);
            } else {
                $('#advanced-save-status').text('Error: ' + (data.message || 'Unknown')).removeClass().addClass('text-danger');
            }
        }, 'json').fail(function(xhr) {
            var msg = 'Request failed';
            if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            }
            $('#advanced-save-status').text('Error: ' + msg).removeClass().addClass('text-danger');
        });
    });
});
</script>

<?php $this->view('partials/foot'); ?>

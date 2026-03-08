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
                            <th>Last Sync Status</th>
                            <td id="sync-status">-</td>
                        </tr>
                        <tr>
                            <th>Last Sync Time</th>
                            <td id="sync-time">-</td>
                        </tr>
                    </table>
                    <p class="text-muted small">The sync script should be configured via crontab on your server.</p>
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
    // Load existing config
    $.getJSON(appUrl + '/module/simplemdm/get_config', function(data) {
        if (data.api_key) {
            $('#api_key').val(data.api_key);
        }
        $('#sync-status').text(data.last_sync_status || 'Never');
        $('#sync-time').text(data.last_sync_time || '-');

        function isEnabled(v) {
            return v === undefined || v === null || String(v) !== '0';
        }
        $('.simplemdm-widget-toggle').each(function() {
            var key = $(this).data('widget-key');
            $(this).prop('checked', isEnabled(data[key]));
        });
        $('#webhook_secret').val(data.webhook_secret || '');
        $('#compliance_min_os').val(data.compliance_min_os || '');
        $('#sync_delta_enabled').prop('checked', String(data.sync_delta_enabled || '0') === '1');
        $('#sync_commands_enabled').prop('checked', String(data.sync_commands_enabled || '0') === '1');
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
            compliance_min_os: $('#compliance_min_os').val() || '',
            sync_delta_enabled: $('#sync_delta_enabled').is(':checked') ? '1' : '0',
            sync_commands_enabled: $('#sync_commands_enabled').is(':checked') ? '1' : '0'
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

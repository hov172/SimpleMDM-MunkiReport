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
    max-width: 1280px;
}
.simplemdm-admin-wrap .simplemdm-modern-widget {
    margin-bottom: 18px;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 12px 32px rgba(16, 24, 40, 0.08);
}
.simplemdm-admin-wrap .panel-title {
    text-transform: none;
    letter-spacing: 0.1px;
}
.simplemdm-admin-wrap .panel-heading {
    padding: 14px 18px;
}
.simplemdm-admin-wrap .panel-body {
    padding: 18px;
}
.simplemdm-admin-wrap .form-control {
    border-radius: 10px;
    border: 1px solid var(--simplemdm-border);
    background: var(--simplemdm-surface);
    color: var(--simplemdm-ink);
}
.simplemdm-admin-wrap .form-group {
    margin-bottom: 14px;
}
.simplemdm-admin-wrap .checkbox {
    margin-top: 0;
    margin-bottom: 12px;
}
.simplemdm-admin-wrap .table > tbody > tr > th,
.simplemdm-admin-wrap .table > tbody > tr > td {
    border-color: var(--simplemdm-border);
    color: var(--simplemdm-ink);
}
.simplemdm-admin-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
    gap: 18px;
    align-items: start;
}
.simplemdm-admin-column {
    min-width: 0;
}
.simplemdm-admin-stack {
    display: grid;
    gap: 18px;
}
.simplemdm-admin-hero {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 18px;
}
.simplemdm-admin-hero-copy {
    max-width: 760px;
}
.simplemdm-admin-hero h1 {
    margin: 0 0 6px;
}
.simplemdm-admin-hero .lead {
    margin: 0;
}
.simplemdm-kpi-strip {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}
.simplemdm-kpi {
    border: 1px solid var(--simplemdm-border);
    background: var(--simplemdm-surface);
    border-radius: 12px;
    padding: 12px 14px;
}
.simplemdm-kpi-label {
    display: block;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.4px;
    text-transform: uppercase;
    color: var(--simplemdm-muted);
}
.simplemdm-kpi-value {
    display: block;
    margin-top: 4px;
    font-size: 18px;
    font-weight: 800;
    color: var(--simplemdm-ink);
}
.simplemdm-actions-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}
.simplemdm-script-grid {
    display: grid;
    gap: 12px;
}
.simplemdm-script-row {
    border: 1px solid var(--simplemdm-border);
    border-radius: 12px;
    background: var(--simplemdm-surface);
    padding: 14px;
}
.simplemdm-script-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 8px;
}
.simplemdm-script-title {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
    color: var(--simplemdm-ink);
}
.simplemdm-script-type {
    display: inline-flex;
    align-items: center;
    border: 1px solid var(--simplemdm-border);
    border-radius: 999px;
    padding: 3px 9px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--simplemdm-muted);
    background: var(--simplemdm-card-bg);
}
.simplemdm-script-description {
    margin: 0 0 12px;
    color: var(--simplemdm-muted);
}
.simplemdm-script-actions .btn {
    margin-right: 8px;
    margin-bottom: 8px;
}
.simplemdm-schedule-actions .btn {
    margin-right: 8px;
    margin-bottom: 8px;
}
.simplemdm-prereq-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 12px 0 6px;
}
.simplemdm-prereq-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 5px 10px;
    font-size: 11px;
    font-weight: 800;
    border: 1px solid var(--simplemdm-border);
    background: var(--simplemdm-card-bg);
    color: var(--simplemdm-muted);
}
.simplemdm-prereq-badge.is-ready {
    background: rgba(47, 158, 68, 0.12);
    border-color: rgba(47, 158, 68, 0.28);
    color: #206a37;
}
.simplemdm-prereq-badge.is-missing {
    background: rgba(194, 59, 59, 0.10);
    border-color: rgba(194, 59, 59, 0.24);
    color: #9f2f2f;
}
.simplemdm-state-panel {
    border: 1px solid var(--simplemdm-border);
    border-radius: 12px;
    background: var(--simplemdm-surface);
    padding: 12px 14px;
    margin: 12px 0;
}
.simplemdm-state-line {
    margin: 0 0 6px;
    color: var(--simplemdm-ink);
    font-size: 13px;
}
.simplemdm-state-line:last-child {
    margin-bottom: 0;
}
.simplemdm-state-label {
    font-weight: 800;
}
.simplemdm-script-command-wrap {
    margin-top: 4px;
}
.simplemdm-script-command-label {
    display: block;
    margin-bottom: 6px;
    font-size: 12px;
    font-weight: 700;
    color: var(--simplemdm-muted);
}
.simplemdm-script-command {
    width: 100%;
    margin: 0;
    padding: 10px 12px;
    border: 1px solid var(--simplemdm-border);
    border-radius: 10px;
    background: #f7fafc;
    font-family: Menlo, Monaco, Consolas, monospace;
    font-size: 12px;
    line-height: 1.55;
    color: #1f2937;
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: anywhere;
}
#script-runner-output {
    width: 100%;
    min-height: 220px;
    resize: vertical;
    font-family: Menlo, Monaco, Consolas, monospace;
    font-size: 12px;
    white-space: pre-wrap;
}
@media (max-width: 1080px) {
    .simplemdm-admin-grid {
        grid-template-columns: 1fr;
    }
    .simplemdm-kpi-strip {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container simplemdm-admin-wrap">
    <div class="simplemdm-admin-hero">
        <div class="simplemdm-admin-hero-copy">
            <h1><i class="fa fa-cog"></i> SimpleMDM Settings</h1>
            <p class="lead">Configure your SimpleMDM API connection and monitor sync health.</p>
        </div>
        <div class="simplemdm-kpi-strip">
            <div class="simplemdm-kpi">
                <span class="simplemdm-kpi-label">Schedule Status</span>
                <span class="simplemdm-kpi-value" id="schedule-status-kpi">Disabled</span>
            </div>
            <div class="simplemdm-kpi">
                <span class="simplemdm-kpi-label">Last Run</span>
                <span class="simplemdm-kpi-value" id="schedule-last-run-kpi">-</span>
            </div>
            <div class="simplemdm-kpi">
                <span class="simplemdm-kpi-label">Next Expected Run</span>
                <span class="simplemdm-kpi-value" id="schedule-next-run-kpi">-</span>
            </div>
        </div>
    </div>

    <div class="simplemdm-admin-grid">
        <div class="simplemdm-admin-column">
            <div class="simplemdm-admin-stack">
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
                    <div class="simplemdm-actions-row">
                        <button type="button" class="btn btn-default" id="simplemdm-sync-now">Run Sync Now</button>
                        <span id="sync-request-message" class="text-muted"></span>
                    </div>
                    <p class="text-muted small" style="margin-top:10px;">This queues a sync request. The host-side cron or manual runner still executes <code>simplemdm_sync.py</code>.</p>
                </div>
            </div>

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

        <div class="simplemdm-admin-column">
            <div class="simplemdm-admin-stack">
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

            <div class="panel panel-default simplemdm-modern-widget">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-calendar"></i> In-Module Sync And Schedule</h3>
                </div>
                <div class="panel-body">
                    <p class="text-muted">Use this section for actions the module can perform for you directly: immediate sync runs, schedule settings, and cron management when in-module execution is enabled.</p>
                    <form id="simplemdm-script-runner-form">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <tr>
                                    <th>Schedule Status</th>
                                    <td id="schedule-status">Disabled</td>
                                </tr>
                                <tr>
                                    <th>Last Run</th>
                                    <td id="schedule-last-run">-</td>
                                </tr>
                                <tr>
                                    <th>Next Expected Run</th>
                                    <td id="schedule-next-run">-</td>
                                </tr>
                            </table>
                        </div>
                        <div class="form-group">
                            <label for="script_runner_schedule_preset">Preset</label>
                            <select class="form-control" id="script_runner_schedule_preset">
                                <option value="*/5 * * * *">Every 5 Minutes</option>
                                <option value="*/15 * * * *">Every 15 Minutes</option>
                                <option value="0 * * * *">Hourly</option>
                                <option value="0 0 * * *">Daily</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="script_runner_schedule">Schedule</label>
                            <input type="text" class="form-control" id="script_runner_schedule" name="script_runner_schedule" placeholder="*/15 * * * *">
                            <p class="help-block">Use a preset or enter a custom cron expression.</p>
                        </div>
                        <div class="form-group">
                            <label for="script_runner_munkireport_url">Runner MunkiReport URL</label>
                            <input type="text" class="form-control" id="script_runner_munkireport_url" name="script_runner_munkireport_url" placeholder="https://your-munkireport.example.com">
                            <p class="help-block">The Python runner posts data back into this MunkiReport instance, so it needs the base URL when running inside the module or from cron.</p>
                        </div>
                        <div class="form-group">
                            <label for="script_runner_python_bin">Configured Python Path</label>
                            <input type="text" class="form-control" id="script_runner_python_bin" name="script_runner_python_bin" placeholder="/usr/bin/python3">
                            <p class="help-block">This path is used for the host/manual runner. In-module availability is verified separately below under `Module Python`.</p>
                        </div>
                        <div class="form-group">
                            <label for="script_runner_log_path">Cron Log Path</label>
                            <input type="text" class="form-control" id="script_runner_log_path" name="script_runner_log_path" placeholder="/var/log/simplemdm_sync.log">
                        </div>
                        <div class="form-group">
                            <label for="script_runner_max_parent_resources">Max Parent Resources</label>
                            <input type="number" min="0" step="1" class="form-control" id="script_runner_max_parent_resources" name="script_runner_max_parent_resources" placeholder="25">
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="allow_module_script_execution" name="allow_module_script_execution" value="1">
                                Allow in-module script execution for global admins
                            </label>
                        </div>
                        <div class="simplemdm-prereq-row" id="schedule-prereq-row">
                            <span class="simplemdm-prereq-badge" id="prereq-api-key">API Key</span>
                            <span class="simplemdm-prereq-badge" id="prereq-runner-url">Runner URL</span>
                            <span class="simplemdm-prereq-badge" id="prereq-python">Python Path</span>
                            <span class="simplemdm-prereq-badge" id="prereq-module-python">Module Python</span>
                            <span class="simplemdm-prereq-badge" id="prereq-schedule">Schedule</span>
                            <span class="simplemdm-prereq-badge" id="prereq-log-path">Log Path</span>
                            <span class="simplemdm-prereq-badge" id="prereq-module-exec">Module Execution</span>
                        </div>
                        <p class="text-muted small" style="margin: 10px 0 12px;">`Python Path` is the configured path for host/manual sync. `Module Python` confirms whether Python is actually available inside the MunkiReport runtime for in-module sync.</p>
                        <div class="simplemdm-state-panel">
                            <p class="simplemdm-state-line"><span class="simplemdm-state-label">Immediate Run:</span> <span id="immediate-run-state">Checking...</span></p>
                            <p class="simplemdm-state-line"><span class="simplemdm-state-label">Scheduled Run:</span> <span id="scheduled-run-state">Checking...</span></p>
                            <p class="simplemdm-state-line"><span class="simplemdm-state-label">Module Runtime Python:</span> <span id="module-runtime-state">Checking...</span></p>
                            <p class="simplemdm-state-line"><span class="simplemdm-state-label">Host/Manual Runner:</span> <span id="host-runner-state">Checking...</span></p>
                            <p class="simplemdm-state-line"><span class="simplemdm-state-label">Cron Management:</span> <span id="cron-management-state">Checking...</span></p>
                            <p class="simplemdm-state-line text-muted" id="cron-management-detail">Waiting for status...</p>
                        </div>
                        <div class="alert alert-info" id="module-runtime-guidance" style="margin-top:12px; margin-bottom:0;">
                            Checking module guidance...
                        </div>
                        <div class="simplemdm-schedule-actions">
                            <button type="button" class="btn btn-primary" id="run-sync-now-btn">Run Sync Now</button>
                            <button type="button" class="btn btn-success" id="enable-schedule-btn">Enable Scheduled Sync</button>
                            <button type="button" class="btn btn-default" id="disable-schedule-btn">Disable Scheduled Sync</button>
                            <button type="submit" class="btn btn-default">Save Schedule Settings</button>
                        </div>
                        <div id="script-runner-save-status" class="text-muted" style="margin-top:10px;"></div>
                        <p class="text-muted small" style="margin-top:10px;">One-off runs execute <code>simplemdm_sync.py</code> immediately. Repeating runs still use cron, which this module can install or remove when script execution is enabled.</p>
                    </form>
                </div>
            </div>

            <div class="panel panel-default simplemdm-modern-widget">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-download"></i> Manual / Outside-Module Access</h3>
                </div>
                <div class="panel-body">
                    <p class="text-muted">Use this section if you want to manage sync outside the module: download the module, copy commands, install cron manually, or run the scripts directly on the host.</p>
                    <p class="text-muted small">Host/manual runner commands should include an explicit SimpleMDM API key via <code>--api-key</code> or <code>SIMPLEMDM_API_KEY</code>. They should not rely on an authenticated browser session to discover the key.</p>
                    <p>
                        <a class="btn btn-default" id="download-module-link" href="#">
                            <i class="fa fa-archive"></i> Download Module Bundle
                        </a>
                    </p>
                    <div id="script-catalog" class="simplemdm-script-grid"></div>
                    <div class="form-group" style="margin-top:14px;">
                        <label for="script-runner-output">Script Output</label>
                        <textarea id="script-runner-output" class="form-control" readonly>Script output will appear here.</textarea>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    var runnerStatusCache = null;
    var runnerStatusRequest = null;
    var runnerStatusRefreshTimer = null;
    var RUNNER_STATUS_TIMEOUT_MS = 5000;

    function parseIsoDate(value) {
        var raw = String(value || '').trim();
        if (!raw) {
            return null;
        }
        var parsed = Date.parse(raw);
        return isNaN(parsed) ? null : new Date(parsed);
    }

    function setSyncMessage(text, cssClass) {
        $('#sync-request-message').text(text).removeClass().addClass(cssClass || 'text-muted');
    }

    function updateSyncMessageFromState(data) {
        var state = String(data.sync_request_state || 'idle');
        var requestedAt = String(data.sync_requested_at || '').trim();
        var lastStatus = String(data.last_sync_status || '').trim();
        var lastTime = String(data.last_sync_time || '').trim();
        var intervalMinutes = parseInt(String(data.sync_interval_minutes || '15'), 10);
        if (isNaN(intervalMinutes) || intervalMinutes < 1) {
            intervalMinutes = 15;
        }

        if (state === 'queued') {
            var requestedDate = parseIsoDate(requestedAt);
            var queuedTooLong = false;
            if (requestedDate) {
                queuedTooLong = ((new Date()).getTime() - requestedDate.getTime()) > ((intervalMinutes + 1) * 60 * 1000);
            }
            setSyncMessage(
                requestedAt
                    ? 'Sync is queued and waiting for cron/manual runner. Requested at ' + requestedAt + '.' + (queuedTooLong ? ' This is longer than the current schedule interval; verify cron is installed and running.' : '')
                    : 'Sync is queued and waiting for cron/manual runner.',
                queuedTooLong ? 'text-warning' : 'text-info'
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

    function formatDateOrDash(value) {
        var dt = parseIsoDate(value);
        return dt ? dt.toLocaleString() : '-';
    }

    function addMinutes(date, minutes) {
        return new Date(date.getTime() + (minutes * 60 * 1000));
    }

    function computeNextExpectedRun(schedule, lastRunRaw) {
        var scheduleValue = String(schedule || '').trim();
        var lastRun = parseIsoDate(lastRunRaw);
        var now = new Date();
        var base = lastRun || now;

        if (scheduleValue === '*/5 * * * *') {
            return addMinutes(base, 5).toLocaleString();
        }
        if (scheduleValue === '*/15 * * * *') {
            return addMinutes(base, 15).toLocaleString();
        }
        if (scheduleValue === '0 * * * *') {
            var hourly = new Date(base.getTime());
            hourly.setMinutes(0, 0, 0);
            hourly.setHours(hourly.getHours() + 1);
            return hourly.toLocaleString();
        }
        if (scheduleValue === '0 0 * * *') {
            var daily = new Date(base.getTime());
            daily.setHours(0, 0, 0, 0);
            daily.setDate(daily.getDate() + 1);
            return daily.toLocaleString();
        }

        return scheduleValue ? 'Custom schedule configured' : '-';
    }

    function renderScheduleStatus(data) {
        var enabled = String(data.enable_scheduled_sync || '0') === '1';
        var schedule = String(data.script_runner_schedule || '');
        var lastRunRaw = String(data.last_sync_time || '');
        var statusText = enabled ? 'Enabled' : 'Disabled';
        var lastRunText = formatDateOrDash(lastRunRaw);
        var nextRunText = enabled ? computeNextExpectedRun(schedule, lastRunRaw) : '-';
        $('#schedule-status').text(statusText);
        $('#schedule-last-run').text(lastRunText);
        $('#schedule-next-run').text(nextRunText);
        $('#schedule-status-kpi').text(statusText);
        $('#schedule-last-run-kpi').text(lastRunText);
        $('#schedule-next-run-kpi').text(nextRunText);
        $('#enable-schedule-btn').prop('disabled', enabled);
        $('#disable-schedule-btn').prop('disabled', !enabled);
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
        $('#allow_module_script_execution').prop('checked', String(data.allow_module_script_execution || '0') === '1');
        $('#script_runner_munkireport_url').val(data.script_runner_munkireport_url || '');
        $('#script_runner_python_bin').val(pickValue(data.script_runner_python_bin, '/usr/bin/python3'));
        var currentSchedule = pickValue(data.script_runner_schedule, '* * * * *');
        $('#script_runner_schedule').val(currentSchedule);
        if ($('#script_runner_schedule_preset option[value="' + currentSchedule.replace(/"/g, '\\"') + '"]').length) {
            $('#script_runner_schedule_preset').val(currentSchedule);
        } else {
            $('#script_runner_schedule_preset').val('custom');
        }
        $('#script_runner_log_path').val(pickValue(data.script_runner_log_path, '/var/log/simplemdm_sync.log'));
        $('#script_runner_max_parent_resources').val(pickValue(data.script_runner_max_parent_resources, '25'));
        renderScheduleStatus(data);
        renderPrereqState();
    }

    function setScriptOutput(lines) {
        $('#script-runner-output').val(lines);
    }

    function setActionNotice(target, text, cssClass) {
        $(target).text(text).removeClass().addClass(cssClass || 'text-muted');
    }

    function getRunnerSettingsPayload(extraPayload) {
        var payload = {
            allow_module_script_execution: $('#allow_module_script_execution').is(':checked') ? '1' : '0',
            script_runner_munkireport_url: $('#script_runner_munkireport_url').val() || '',
            script_runner_python_bin: $('#script_runner_python_bin').val() || '/usr/bin/python3',
            script_runner_schedule: $('#script_runner_schedule').val() || '*/15 * * * *',
            script_runner_log_path: $('#script_runner_log_path').val() || '/var/log/simplemdm_sync.log',
            script_runner_max_parent_resources: String($('#script_runner_max_parent_resources').val() || '25')
        };

        if (extraPayload) {
            Object.keys(extraPayload).forEach(function(key) {
                payload[key] = extraPayload[key];
            });
        }

        return payload;
    }

    function collectPrereqState() {
        return {
            apiKeyPresent: String($('#api_key').val() || '').trim() !== '',
            moduleExecutionEnabled: $('#allow_module_script_execution').is(':checked'),
            runnerUrlPresent: String($('#script_runner_munkireport_url').val() || '').trim() !== '',
            pythonPresent: String($('#script_runner_python_bin').val() || '').trim() !== '',
            schedulePresent: String($('#script_runner_schedule').val() || '').trim() !== '',
            logPathPresent: String($('#script_runner_log_path').val() || '').trim() !== '',
            maxParentResourcesPresent: String($('#script_runner_max_parent_resources').val() || '').trim() !== ''
        };
    }

    function getMissingFields(requirements) {
        var state = collectPrereqState();
        var missing = [];
        if (requirements.apiKey && !state.apiKeyPresent) {
            missing.push('SimpleMDM API Key');
        }
        if (requirements.moduleExecution && !state.moduleExecutionEnabled) {
            missing.push('Allow in-module script execution');
        }
        if (requirements.runnerUrl && !state.runnerUrlPresent) {
            missing.push('Runner MunkiReport URL');
        }
        if (requirements.python && !state.pythonPresent) {
            missing.push('Python Binary');
        }
        if (requirements.schedule && !state.schedulePresent) {
            missing.push('Schedule');
        }
        if (requirements.logPath && !state.logPathPresent) {
            missing.push('Cron Log Path');
        }
        if (requirements.maxParentResources && !state.maxParentResourcesPresent) {
            missing.push('Max Parent Resources');
        }
        return missing;
    }

    function updatePrereqBadge(selector, ready, label) {
        var $el = $(selector);
        $el.removeClass('is-ready is-missing');
        $el.addClass(ready ? 'is-ready' : 'is-missing');
        $el.text(label + ': ' + (ready ? 'Ready' : 'Missing'));
    }

    function setGuidance(message, level) {
        var $el = $('#module-runtime-guidance');
        $el.removeClass('alert-info alert-warning alert-success');
        $el.addClass(level || 'alert-info');
        $el.text(message);
    }

    function renderPrereqState() {
        var state = collectPrereqState();
        updatePrereqBadge('#prereq-api-key', state.apiKeyPresent, 'API Key');
        updatePrereqBadge('#prereq-runner-url', state.runnerUrlPresent, 'Runner URL');
        updatePrereqBadge('#prereq-python', state.pythonPresent, 'Python Path');
        updatePrereqBadge('#prereq-module-python', false, 'Module Python');
        updatePrereqBadge('#prereq-schedule', state.schedulePresent, 'Schedule');
        updatePrereqBadge('#prereq-log-path', state.logPathPresent, 'Log Path');
        updatePrereqBadge('#prereq-module-exec', state.moduleExecutionEnabled, 'Module Execution');
        setGuidance('Reviewing runner settings, module runtime, and cron support...', 'alert-info');
    }

    function renderRunnerModeState(status) {
        var state = collectPrereqState();
        var runtime = status && status.runtime ? status.runtime : null;
        var cronStatus = status && status.cron ? status.cron : null;
        var modulePythonAvailable = runtime ? !!runtime.python_available : false;
        var configuredPythonPath = String((runtime && runtime.python_binary) || $('#script_runner_python_bin').val() || '/usr/bin/python3');
        var immediateReady = state.apiKeyPresent && state.moduleExecutionEnabled && state.runnerUrlPresent && state.pythonPresent && state.maxParentResourcesPresent && modulePythonAvailable;
        var scheduledReady = state.apiKeyPresent && state.runnerUrlPresent && state.pythonPresent && state.schedulePresent && state.logPathPresent && state.maxParentResourcesPresent;

        updatePrereqBadge('#prereq-module-python', modulePythonAvailable, 'Module Python');

        if (immediateReady) {
            $('#immediate-run-state').text('Ready to run inside the module.');
        } else if (!state.moduleExecutionEnabled) {
            $('#immediate-run-state').text('Not ready. Enable module-side script execution first.');
        } else if (!modulePythonAvailable) {
            $('#immediate-run-state').text('Not ready. Module Python is missing at ' + configuredPythonPath + '. In Docker, add Python to the munkireport container or use host/manual sync.');
        } else {
            $('#immediate-run-state').text('Not ready for immediate in-module execution.');
        }

        if (!scheduledReady) {
            $('#scheduled-run-state').text('Missing required settings for recurring scheduled sync.');
        } else if (state.moduleExecutionEnabled && !modulePythonAvailable) {
            $('#scheduled-run-state').text('Recurring sync can be configured, but Module Python is missing at ' + configuredPythonPath + '. In Docker, install Python in the munkireport container; otherwise use host/manual cron.');
        } else if (cronStatus && cronStatus.installed) {
            $('#scheduled-run-state').text('Ready. Cron entry is installed.');
        } else if (state.moduleExecutionEnabled) {
            $('#scheduled-run-state').text('Configuration is ready. Install cron from this module to start recurring runs.');
        } else {
            $('#scheduled-run-state').text('Configuration is ready, but recurring runs still require manual cron installation outside the module.');
        }

        if (runtime) {
            $('#module-runtime-state').text(runtime.python_available ? 'Verified: Module Python is available at ' + (runtime.python_path || configuredPythonPath) + '.' : ('Missing: Module Python at ' + configuredPythonPath + '. ' + (runtime.message || 'Add Python to the app container/server for in-module sync.')));
        } else {
            $('#module-runtime-state').text('Checking whether the module runtime can execute Python...');
        }

        if (!state.pythonPresent) {
            $('#host-runner-state').text('Python path is not configured for host/manual sync.');
        } else {
            $('#host-runner-state').text('Configured to use ' + String($('#script_runner_python_bin').val() || '/usr/bin/python3') + ' for host/manual sync.');
        }

        if (!cronStatus) {
            $('#cron-management-state').text('Checking...');
            $('#cron-management-detail').text('Waiting for cron inspection.');
        } else {
            var managementText = 'Manual cron required.';
            if (cronStatus.mode === 'module_managed_installed') {
                managementText = 'Managed in module. Cron installed.';
            } else if (cronStatus.mode === 'module_managed_not_installed') {
                managementText = 'Managed in module. Cron not installed.';
            } else if (cronStatus.mode === 'manual_installed') {
                managementText = 'Managed outside module. Cron installed.';
            }

            $('#cron-management-state').text(managementText);
            $('#cron-management-detail').text(cronStatus.message || '');
        }

        if (!state.moduleExecutionEnabled) {
            setGuidance('To use immediate in-module sync, enable `Allow in-module script execution for global admins`, save the settings, and re-check `Module Python`. If you use Docker, see the module README and `local/modules/simplemdm/Dockerfile.munkireport-simplemdm` for the recommended container update.', 'alert-info');
        } else if (!modulePythonAvailable) {
            setGuidance('Docker recommendation: add `python3` to the `munkireport` image, rebuild with `docker compose build`, then recreate with `docker compose up -d --force-recreate`. If you also want cron inspection or management inside the container, add the `cron` package too. Otherwise keep using host/manual sync. See the module README and `local/modules/simplemdm/Dockerfile.munkireport-simplemdm` for the recommended Dockerfile example.', 'alert-warning');
        } else if (cronStatus && cronStatus.available === false && cronStatus.message) {
            setGuidance('Module Python is ready, but cron inspection or management is not. If you want the module to inspect or manage cron inside Docker, add the `cron` package to the `munkireport` image; otherwise manage cron on the host. See the module README and `local/modules/simplemdm/Dockerfile.munkireport-simplemdm` for the recommended container changes.', 'alert-warning');
        } else {
            setGuidance('Module-side execution is available. You can run immediate sync here, and scheduled sync can be managed here when cron support is available.', 'alert-success');
        }

        $('#run-sync-now-btn').prop('disabled', !immediateReady);
        $('#enable-schedule-btn').prop('disabled', !scheduledReady || (state.moduleExecutionEnabled && !modulePythonAvailable));
    }

    function renderRunnerStatusPending(reason) {
        var state = collectPrereqState();
        renderPrereqState();
        $('#immediate-run-state').text(state.moduleExecutionEnabled ? 'Re-checking in-module readiness...' : 'Not ready. Enable module-side script execution first.');
        $('#scheduled-run-state').text('Re-checking scheduled sync capability...');
        $('#module-runtime-state').text(reason || 'Checking whether the module runtime can execute Python...');
        if (!state.pythonPresent) {
            $('#host-runner-state').text('Python path is not configured for host/manual sync.');
        } else {
            $('#host-runner-state').text('Configured to use ' + String($('#script_runner_python_bin').val() || '/usr/bin/python3') + ' for host/manual sync.');
        }
        updatePrereqBadge('#prereq-module-python', false, 'Module Python');
        $('#cron-management-state').text('Checking...');
        $('#cron-management-detail').text('Waiting for cron inspection.');
        setGuidance(reason || 'Checking module runtime and cron support...', 'alert-info');
    }

    function validateActionRequirements(requirements, options) {
        options = options || {};
        var missing = getMissingFields(requirements || {});
        if (requirements.modulePython && (!runnerStatusCache || !runnerStatusCache.runtime || !runnerStatusCache.runtime.python_available)) {
            missing.push('Python available in module runtime');
        }
        if (missing.length) {
            var message = (options.prefix || 'Cannot continue') + ': missing ' + missing.join(', ') + '.';
            if (options.noticeTarget) {
                setActionNotice(options.noticeTarget, message, 'text-danger');
            }
            if (options.syncMessage) {
                setSyncMessage(message, 'text-danger');
            }
            if (options.outputMessage) {
                setScriptOutput(message + '\n\nFill in the missing settings and save them before retrying.');
            }
            return false;
        }
        return true;
    }

    function confirmAction(message) {
        return window.confirm(message);
    }

    function renderScriptCatalog(data) {
        var scripts = Array.isArray(data.scripts) ? data.scripts : [];
        $('#download-module-link').attr('href', data.module_download_url || '#');

        if (!scripts.length) {
            $('#script-catalog').html('<p class="text-muted">No scripts are available.</p>');
            return;
        }

        var html = '';
        scripts.forEach(function(script) {
            html += '<div class="simplemdm-script-row">';
            html += '<div class="simplemdm-script-head">';
            html += '<div>';
            html += '<h4 class="simplemdm-script-title">' + $('<div>').text(script.name || '').html() + '</h4>';
            html += '</div>';
            html += '<span class="simplemdm-script-type">' + $('<div>').text(script.type || 'script').html() + '</span>';
            html += '</div>';
            html += '<p class="simplemdm-script-description">' + $('<div>').text(script.description || '').html() + '</p>';
            html += '<div class="simplemdm-script-actions">';
            html += '<a class="btn btn-default btn-sm" href="' + $('<div>').text(script.download_url || '#').html() + '"><i class="fa fa-download"></i> Download</a>';
            html += '<button type="button" class="btn btn-default btn-sm simplemdm-copy-command" data-command="' + $('<div>').text(script.external_command || '').html() + '"><i class="fa fa-copy"></i> Copy External Command</button>';
            html += '<button type="button" class="btn btn-primary btn-sm simplemdm-run-script" data-action="' + $('<div>').text(script.run_action || '').html() + '"><i class="fa fa-play"></i> Run In Module</button>';
            html += '</div>';
            html += '<div class="simplemdm-script-command-wrap">';
            html += '<span class="simplemdm-script-command-label">External Command</span>';
            html += '<pre class="simplemdm-script-command">' + $('<div>').text(script.external_command || '').html() + '</pre>';
            html += '</div>';
            html += '</div>';
        });

        $('#script-catalog').html(html);
        $('.simplemdm-run-script').prop('disabled', !data.execution_enabled);
        if (!data.execution_enabled) {
            setScriptOutput('In-module script execution is currently disabled. For host/manual runs, download the scripts and use commands that pass --api-key explicitly or set SIMPLEMDM_API_KEY.');
        }
    }

    function loadScriptCatalog() {
        $.getJSON(appUrl + '/module/simplemdm/get_script_catalog', function(data) {
            renderScriptCatalog(data);
        }).fail(function(xhr) {
            var msg = 'Unable to load script catalog';
            if (xhr && xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) {
                msg = xhr.responseJSON.message || xhr.responseJSON.error;
            }
            $('#script-catalog').html('<p class="text-danger">' + $('<div>').text(msg).html() + '</p>');
        });
    }

    function loadRunnerStatus() {
        if (runnerStatusRequest && runnerStatusRequest.readyState !== 4) {
            runnerStatusRequest.abort();
        }

        renderRunnerStatusPending();

        runnerStatusRequest = $.ajax({
            url: appUrl + '/module/simplemdm/get_runner_status',
            dataType: 'json',
            timeout: RUNNER_STATUS_TIMEOUT_MS
        }).done(function(data) {
            runnerStatusCache = data || null;
            renderRunnerModeState(data || null);
        }).fail(function(xhr, textStatus) {
            var timedOut = textStatus === 'timeout';
            var aborted = textStatus === 'abort';
            if (aborted) {
                return;
            }

            var message = timedOut
                ? 'Timed out after ' + (RUNNER_STATUS_TIMEOUT_MS / 1000) + ' seconds while checking cron state from the module.'
                : 'Unable to inspect cron state from the module.';
            if (xhr && xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) {
                message = xhr.responseJSON.message || xhr.responseJSON.error;
            }
            runnerStatusCache = {
                cron: {
                    mode: 'manual_required',
                    installed: false,
                    message: message
                },
                runtime: {
                    python_available: false,
                    python_binary: String($('#script_runner_python_bin').val() || '/usr/bin/python3'),
                    message: timedOut
                        ? 'Timed out after ' + (RUNNER_STATUS_TIMEOUT_MS / 1000) + ' seconds while checking the module runtime. If you are using Docker, verify the munkireport container is running and has Python installed.'
                        : 'Unable to inspect the module runtime. If you are using Docker, verify the munkireport container is running and that Python is installed there.'
                }
            };
            renderRunnerModeState(runnerStatusCache);
        });
    }

    function runScriptAction(action) {
        setScriptOutput('Running action: ' + action + ' ...');
        $.post(appUrl + '/module/simplemdm/run_script', { action: action }, function(data) {
            var parts = [];
            parts.push('Status: ' + (data.status || 'unknown'));
            parts.push('Action: ' + (data.action || action));
            parts.push('Exit Code: ' + String(data.exit_code === undefined ? '' : data.exit_code));
            parts.push('Command: ' + (data.command || ''));
            parts.push('');
            parts.push('STDOUT');
            parts.push(data.stdout || '');
            parts.push('');
            parts.push('STDERR');
            parts.push(data.stderr || '');
            setScriptOutput(parts.join('\n'));
        }, 'json').fail(function(xhr) {
            var msg = 'Script execution failed';
            var payload = (xhr && xhr.responseJSON) ? xhr.responseJSON : {};
            var parts = [
                msg,
                payload.message || payload.error || '',
                payload.stdout || '',
                payload.stderr || ''
            ];
            setScriptOutput(parts.join('\n').trim());
        });
    }

    function loadConfig() {
        $.getJSON(appUrl + '/module/simplemdm/get_config', function(data) {
            renderConfig(data);
        });
    }

    function refreshSyncStatus() {
        $.getJSON(appUrl + '/module/simplemdm/get_config', function(data) {
            renderSyncStatus(data);
            renderScheduleStatus(data);
        });
    }

    function saveScheduleSettings(extraPayload, successMessage) {
        $('#script-runner-save-status').text('Saving...').removeClass().addClass('text-info');
        var payload = getRunnerSettingsPayload(extraPayload);

        $.post(appUrl + '/module/simplemdm/save_config', payload, function(data) {
            if (data.status === 'success') {
                $('#script-runner-save-status').text(successMessage || 'Saved successfully!').removeClass().addClass('text-success');
                loadConfig();
                loadScriptCatalog();
                loadRunnerStatus();
            } else {
                $('#script-runner-save-status').text('Error: ' + (data.message || 'Unknown')).removeClass().addClass('text-danger');
            }
        }, 'json').fail(function(xhr) {
            var msg = 'Request failed';
            if (xhr && xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) {
                msg = xhr.responseJSON.message || xhr.responseJSON.error;
            }
            $('#script-runner-save-status').text('Error: ' + msg).removeClass().addClass('text-danger');
        });
    }

    function runImmediateSyncFromSchedule($button) {
        if (!validateActionRequirements(
            { apiKey: true, moduleExecution: true, runnerUrl: true, python: true, maxParentResources: true, modulePython: true },
            {
                prefix: 'Immediate sync cannot start',
                noticeTarget: '#script-runner-save-status',
                syncMessage: true,
                outputMessage: true
            }
        )) {
            $button.prop('disabled', false);
            return;
        }

        if (!confirmAction('Run SimpleMDM sync now using the current runner settings?')) {
            setActionNotice('#script-runner-save-status', 'Immediate sync cancelled.', 'text-muted');
            $button.prop('disabled', false);
            return;
        }

        $('#script-runner-save-status').text('Saving runner settings...').removeClass().addClass('text-info');
        $.post(appUrl + '/module/simplemdm/save_config', getRunnerSettingsPayload(), function(data) {
            if (data.status !== 'success') {
                $('#script-runner-save-status').text('Error: ' + (data.message || 'Unable to save runner settings')).removeClass().addClass('text-danger');
                $button.prop('disabled', false);
                return;
            }

            $('#script-runner-save-status').text('Starting immediate sync...').removeClass().addClass('text-info');
            setSyncMessage('Running sync now inside the module.', 'text-info');
            setScriptOutput('Immediate sync requested.\n\nPrerequisites validated:\n- API key present\n- In-module execution enabled\n- Runner URL set\n- Python binary set\n\nStarting simplemdm_sync.py ...');
            runScriptAction('sync_now');
            $button.prop('disabled', false);
        }, 'json').fail(function(xhr) {
            var msg = 'Unable to save runner settings';
            if (xhr && xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) {
                msg = xhr.responseJSON.message || xhr.responseJSON.error;
            }
            $('#script-runner-save-status').text('Error: ' + msg).removeClass().addClass('text-danger');
            $button.prop('disabled', false);
        });
    }

    // Load existing config
    loadConfig();
    loadScriptCatalog();
    loadRunnerStatus();
    window.setInterval(refreshSyncStatus, 15000);

    $('#simplemdm-sync-now').on('click', function() {
        var $btn = $(this);
        if (!validateActionRequirements(
            { apiKey: true },
            {
                prefix: 'Queued sync cannot start',
                syncMessage: true
            }
        )) {
            return;
        }
        if (!confirmAction('Queue a sync request for the next cron/manual worker pickup?')) {
            setSyncMessage('Queued sync cancelled.', 'text-muted');
            return;
        }
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

    $('#run-sync-now-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        runImmediateSyncFromSchedule($btn);
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

    $('#simplemdm-script-runner-form').on('submit', function(e) {
        e.preventDefault();
        saveScheduleSettings({}, 'Saved successfully!');
    });

    $(document).on('click', '.simplemdm-copy-command', function() {
        var command = String($(this).data('command') || '');
        if (!command) {
            return;
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(command);
            setScriptOutput('Copied external command:\n' + command);
            return;
        }
        setScriptOutput('Copy is not available in this browser. Command:\n' + command);
    });

    $(document).on('click', '.simplemdm-run-script', function() {
        runScriptAction(String($(this).data('action') || ''));
    });

    $('#script_runner_schedule_preset').on('change', function() {
        var value = String($(this).val() || '');
        if (value && value !== 'custom') {
            $('#script_runner_schedule').val(value);
        }
        renderPrereqState();
    });

    $('#api_key, #script_runner_munkireport_url, #script_runner_python_bin, #script_runner_schedule, #script_runner_log_path, #script_runner_max_parent_resources, #allow_module_script_execution').on('input change', function() {
        renderPrereqState();
        renderRunnerStatusPending('Re-checking module runtime after unsaved changes...');
        window.clearTimeout(runnerStatusRefreshTimer);
        runnerStatusRefreshTimer = window.setTimeout(function() {
            loadRunnerStatus();
        }, 400);
    });

    $('#enable-schedule-btn').on('click', function() {
        var canRunInModule = $('#allow_module_script_execution').is(':checked');
        if (!validateActionRequirements(
            {
                apiKey: true,
                runnerUrl: true,
                python: true,
                schedule: true,
                logPath: true,
                maxParentResources: true,
                modulePython: canRunInModule
            },
            {
                prefix: 'Scheduled sync cannot be enabled',
                noticeTarget: '#script-runner-save-status',
                outputMessage: true
            }
        )) {
            return;
        }
        if (!confirmAction('Enable scheduled sync using the current schedule settings?')) {
            setActionNotice('#script-runner-save-status', 'Enable scheduled sync cancelled.', 'text-muted');
            return;
        }
        saveScheduleSettings({ enable_scheduled_sync: '1' }, 'Schedule enabled.');
        if (canRunInModule) {
            setScriptOutput('Installing cron for scheduled sync using the saved runner settings...');
            runScriptAction('install_cron');
        } else {
            setScriptOutput('Schedule enabled in config. In-module execution is disabled, so cron was not installed automatically. Use the Manual Access section to run the install command outside the module.');
        }
    });

    $('#disable-schedule-btn').on('click', function() {
        var canRunInModule = $('#allow_module_script_execution').is(':checked');
        if (!confirmAction('Disable scheduled sync? This stops future cron-based sync runs managed by this module.')) {
            setActionNotice('#script-runner-save-status', 'Disable scheduled sync cancelled.', 'text-muted');
            return;
        }
        saveScheduleSettings({ enable_scheduled_sync: '0' }, 'Schedule disabled.');
        if (canRunInModule) {
            setScriptOutput('Removing cron for scheduled sync...');
            runScriptAction('remove_cron');
        } else {
            setScriptOutput('Schedule disabled in config. In-module execution is disabled, so cron was not removed automatically. Use the Manual Access section if you need to remove the host cron job manually.');
        }
    });
});
</script>

<?php $this->view('partials/foot'); ?>

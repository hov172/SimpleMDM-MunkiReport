<?php
$this->view('partials/head');
include_once __DIR__ . '/simplemdm_widget_modern_assets.php';

$simplemdm_widgets = [];
$required_simplemdm_widgets = [
    'simplemdm_mcp_findings' => 'MCP Findings',
];
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

$simplemdm_widget_ids = array_column($simplemdm_widgets, 'id');
foreach ($required_simplemdm_widgets as $id => $label) {
    if (! in_array($id, $simplemdm_widget_ids, true)) {
        $simplemdm_widgets[] = [
            'id' => $id,
            'label' => $label,
        ];
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
    height: auto;
    align-self: start;
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
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    align-items: start;
}
.simplemdm-admin-column {
    min-width: 0;
}
.simplemdm-admin-stack {
    display: grid;
    gap: 18px;
    align-content: start;
}
.simplemdm-admin-stack > .simplemdm-modern-widget {
    min-width: 0;
    margin-bottom: 0;
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
.simplemdm-runs-list {
    display: grid;
    gap: 10px;
}
.simplemdm-runs-empty {
    color: var(--simplemdm-muted);
}
.simplemdm-runs-card {
    border: 1px solid var(--simplemdm-border);
    border-radius: 12px;
    background: var(--simplemdm-surface);
    padding: 12px 14px;
}
.simplemdm-runs-summary {
    font-weight: 600;
    line-height: 1.45;
    word-break: break-word;
    overflow-wrap: anywhere;
}
.simplemdm-runs-meta {
    display: grid;
    gap: 4px;
    margin-top: 10px;
}
.simplemdm-runs-meta-line {
    line-height: 1.35;
}
.simplemdm-runs-meta-label {
    font-weight: 700;
    color: var(--simplemdm-muted);
    margin-right: 6px;
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
.simplemdm-script-inline-status {
    margin: 4px 0 0;
    font-size: 12px;
    color: var(--simplemdm-muted);
}
.simplemdm-schedule-actions .btn {
    margin-right: 8px;
    margin-bottom: 8px;
}
.simplemdm-schedule-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 12px 10px;
    margin-top: 18px;
}
.simplemdm-schedule-actions .btn {
    margin-right: 0;
    margin-bottom: 0;
}
.simplemdm-schedule-primary {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.simplemdm-schedule-secondary {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
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
.simplemdm-admin-collapsible .panel-heading {
    cursor: pointer;
}
.simplemdm-admin-heading-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.simplemdm-admin-heading-main {
    min-width: 0;
}
.simplemdm-admin-heading-summary {
    margin-top: 4px;
    font-size: 12px;
    color: var(--simplemdm-muted);
    font-weight: 600;
    line-height: 1.4;
}
.simplemdm-admin-heading-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--simplemdm-border);
    border-radius: 999px;
    background: var(--simplemdm-card-bg);
    color: var(--simplemdm-ink);
    font-size: 11px;
    font-weight: 800;
    padding: 4px 10px;
    white-space: nowrap;
}
.simplemdm-source-toggle-grid {
    display: grid;
    gap: 10px;
    margin: 12px 0 14px;
}
.simplemdm-source-toggle-row {
    border: 1px solid var(--simplemdm-border);
    border-radius: 12px;
    background: var(--simplemdm-surface);
    padding: 12px 14px;
}
.simplemdm-source-toggle-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
}
.simplemdm-source-toggle-title {
    font-weight: 800;
    color: var(--simplemdm-ink);
}
.simplemdm-source-toggle-meta {
    margin-top: 6px;
    font-size: 12px;
    color: var(--simplemdm-muted);
    line-height: 1.45;
}
.simplemdm-admin-subsection {
    margin: 16px 0 18px;
    padding: 14px;
    border: 1px solid var(--simplemdm-border);
    border-radius: 12px;
    background: var(--simplemdm-surface);
}
.simplemdm-admin-subsection:first-child {
    margin-top: 0;
}
.simplemdm-admin-subsection-title {
    margin: 0 0 6px;
    font-size: 15px;
    font-weight: 800;
    color: var(--simplemdm-ink);
}
.simplemdm-admin-subsection-copy {
    margin: 0 0 12px;
    color: var(--simplemdm-muted);
    line-height: 1.45;
}
.simplemdm-admin-subsection-copy:last-child {
    margin-bottom: 0;
}
.simplemdm-event-toggle-grid {
    display: grid;
    gap: 10px;
    margin-top: 12px;
}
.simplemdm-event-toggle-row,
.simplemdm-custom-event-row {
    border: 1px solid var(--simplemdm-border);
    border-radius: 12px;
    background: var(--simplemdm-surface);
    padding: 12px 14px;
}
.simplemdm-event-toggle-head,
.simplemdm-custom-event-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}
.simplemdm-event-toggle-title,
.simplemdm-custom-event-title {
    font-weight: 800;
    color: var(--simplemdm-ink);
}
.simplemdm-event-toggle-meta,
.simplemdm-custom-event-meta {
    margin-top: 6px;
    font-size: 12px;
    color: var(--simplemdm-muted);
    line-height: 1.45;
}
.simplemdm-custom-event-grid {
    display: grid;
    gap: 10px;
    margin-top: 12px;
}
.simplemdm-custom-event-fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px 12px;
    margin-top: 12px;
}
.simplemdm-custom-event-field-full {
    grid-column: 1 / -1;
}
.simplemdm-custom-event-row label {
    display: block;
    margin-bottom: 4px;
    font-size: 12px;
    font-weight: 700;
    color: var(--simplemdm-muted);
}
.simplemdm-custom-event-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-top: 12px;
}
.simplemdm-inline-checkbox {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--simplemdm-muted);
    margin: 0;
}
.simplemdm-inline-checkbox input {
    margin: 0;
}
.simplemdm-custom-event-guidance {
    margin-top: 10px;
    font-size: 12px;
    line-height: 1.5;
    color: var(--simplemdm-muted);
}
.simplemdm-event-inline-status {
    min-height: 18px;
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
    .simplemdm-custom-event-fields {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container simplemdm-admin-wrap">
    <div class="simplemdm-admin-hero">
        <div class="simplemdm-admin-hero-copy">
            <h1><i class="fa fa-cog"></i> SimpleMDM Settings</h1>
            <p class="lead">Configure core SimpleMDM sync, supplemental module enrichment, and client-reported data for this MunkiReport module.</p>
        </div>
        <div class="simplemdm-kpi-strip">
            <div class="simplemdm-kpi">
                <span class="simplemdm-kpi-label">Schedule Config</span>
                <span class="simplemdm-kpi-value" id="schedule-config-kpi">Disabled</span>
            </div>
            <div class="simplemdm-kpi">
                <span class="simplemdm-kpi-label">Recurring Sync Ready</span>
                <span class="simplemdm-kpi-value" id="schedule-ready-kpi">No</span>
            </div>
            <div class="simplemdm-kpi">
                <span class="simplemdm-kpi-label">Last Run</span>
                <span class="simplemdm-kpi-value" id="schedule-last-run-kpi">-</span>
            </div>
        </div>
    </div>

    <div class="simplemdm-admin-grid">
            <div class="simplemdm-admin-column">
                <div class="simplemdm-admin-stack">
            <div class="panel panel-default simplemdm-modern-widget simplemdm-admin-collapsible" data-collapsible="api" data-default-open="0">
                <div class="panel-heading" data-collapsible-toggle="api">
                    <div class="simplemdm-admin-heading-wrap">
                        <div class="simplemdm-admin-heading-main">
                            <h3 class="panel-title"><i class="fa fa-key"></i> API Configuration</h3>
                            <div class="simplemdm-admin-heading-summary" id="summary-api">API key configured state</div>
                        </div>
                        <span class="simplemdm-admin-heading-toggle" id="toggle-api">Expand</span>
                    </div>
                </div>
                <div class="panel-body" data-collapsible-body="api" style="display:none;">
                    <p class="text-muted">Use this card to store the SimpleMDM API key this module uses for sync, reporting, and any module-managed runner actions.</p>
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

            <div class="panel panel-default simplemdm-modern-widget simplemdm-admin-collapsible" data-collapsible="widgets" data-default-open="0">
                <div class="panel-heading" data-collapsible-toggle="widgets">
                    <div class="simplemdm-admin-heading-wrap">
                        <div class="simplemdm-admin-heading-main">
                            <h3 class="panel-title"><i class="fa fa-th-large"></i> Widget Visibility</h3>
                            <div class="simplemdm-admin-heading-summary" id="summary-widgets">Enabled widget count</div>
                        </div>
                        <span class="simplemdm-admin-heading-toggle" id="toggle-widgets">Expand</span>
                    </div>
                </div>
                <div class="panel-body" data-collapsible-body="widgets" style="display:none;">
                    <p class="text-muted">Choose which SimpleMDM report widgets appear on the report page. This does not affect data collection or device detail pages.</p>
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

            <div class="panel panel-default simplemdm-modern-widget simplemdm-admin-collapsible" data-collapsible="supplemental" data-default-open="1">
                <div class="panel-heading" data-collapsible-toggle="supplemental">
                    <div class="simplemdm-admin-heading-wrap">
                        <div class="simplemdm-admin-heading-main">
                            <h3 class="panel-title"><i class="fa fa-plus-square"></i> Supplemental Data</h3>
                            <div class="simplemdm-admin-heading-summary" id="summary-supplemental">Detection, freshness, and client fact health</div>
                        </div>
                        <span class="simplemdm-admin-heading-toggle" id="toggle-supplemental">Collapse</span>
                    </div>
                </div>
                <div class="panel-body" data-collapsible-body="supplemental">
                    <p class="text-muted">This card covers the two supplemental paths layered on top of core SimpleMDM sync: Option A reads data from other loaded MunkiReport modules, and Option B adds client-reported facts stored by this module.</p>
                    <div class="simplemdm-admin-subsection">
                        <h4 class="simplemdm-admin-subsection-title">Summary Health</h4>
                        <p class="simplemdm-admin-subsection-copy">These values show whether supplemental enrichment is enabled, how fresh the cached summary is, and whether refreshes are succeeding.</p>
                        <table class="table table-striped">
                            <tr>
                                <th>Supplemental Enabled</th>
                                <td id="supplemental-enabled-state">-</td>
                            </tr>
                            <tr>
                                <th>Stale Threshold</th>
                                <td id="supplemental-stale-threshold">-</td>
                            </tr>
                            <tr>
                                <th>Summary Rows</th>
                                <td id="supplemental-summary-count">-</td>
                            </tr>
                            <tr>
                                <th>Last Summary Refresh</th>
                                <td id="supplemental-last-refresh">-</td>
                            </tr>
                            <tr>
                                <th>Last Summary Status</th>
                                <td id="supplemental-last-status">-</td>
                            </tr>
                            <tr>
                                <th>Fresh / Stale / Failed</th>
                                <td id="supplemental-freshness-summary">-</td>
                            </tr>
                            <tr>
                                <th>Client Facts / History</th>
                                <td id="client-reporter-counts">-</td>
                            </tr>
                        </table>
                        <div class="simplemdm-actions-row" style="margin-bottom:0;">
                            <button type="button" class="btn btn-default" id="refresh-supplemental-btn">Refresh Supplemental Summary</button>
                            <span id="supplemental-refresh-status" class="text-muted"></span>
                        </div>
                    </div>
                    <div class="simplemdm-admin-subsection">
                        <h4 class="simplemdm-admin-subsection-title">Detected Sources</h4>
                        <p class="simplemdm-admin-subsection-copy">Each source below shows whether SimpleMDM can use that module’s data, whether it was built-in or auto-discovered, and whether it is currently enabled for enrichment.</p>
                        <div id="supplemental-detected-sources" class="simplemdm-runs-list">
                            <div class="simplemdm-runs-empty">Loading supplemental detection...</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel panel-default simplemdm-modern-widget simplemdm-admin-collapsible" data-collapsible="enrichment" data-default-open="0">
                <div class="panel-heading" data-collapsible-toggle="enrichment">
                    <div class="simplemdm-admin-heading-wrap">
                        <div class="simplemdm-admin-heading-main">
                            <h3 class="panel-title"><i class="fa fa-puzzle-piece"></i> Supplemental And Client Reporter Settings</h3>
                            <div class="simplemdm-admin-heading-summary" id="summary-enrichment">Enrichment toggles, source selection, registry overrides, and client reporter controls</div>
                        </div>
                        <span class="simplemdm-admin-heading-toggle" id="toggle-enrichment">Expand</span>
                    </div>
                </div>
                <div class="panel-body" data-collapsible-body="enrichment" style="display:none;">
                    <p class="text-muted">Core SimpleMDM sync brings in primary device data from the external SimpleMDM service. Use this card only for the two optional supplemental paths: Option A for other MunkiReport modules, and Option B for client-reported facts sent directly into this module.</p>
                    <form id="simplemdm-enrichment-form">
                        <div class="simplemdm-admin-subsection">
                            <h4 class="simplemdm-admin-subsection-title">Supplemental Module Enrichment</h4>
                            <p class="simplemdm-admin-subsection-copy"><strong>Option A</strong>: use detected tables from other loaded MunkiReport modules to enrich this module's SimpleMDM device data, listings, and summary views.</p>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" id="supplemental_enabled" name="supplemental_enabled" value="1">
                                    Enable supplemental module data enrichment
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="supplemental_default_stale_after_minutes">Supplemental stale threshold (minutes)</label>
                                <input type="number" min="1" step="1" class="form-control" id="supplemental_default_stale_after_minutes" name="supplemental_default_stale_after_minutes" placeholder="1440">
                                <p class="help-block">Used for supplemental freshness badges when source modules do not expose their own last-updated timestamp.</p>
                            </div>
                            <div class="form-group">
                                <label>Detected Supplemental Sources</label>
                                <p class="help-block">Supported sources are auto-detected by schema presence and, for generic modules, inferred table usage. Uncheck any detected source to exclude it from supplemental enrichment and cached summary generation.</p>
                                <div id="supplemental-source-toggle-list" class="simplemdm-source-toggle-grid">
                                    <div class="simplemdm-runs-empty">Loading detected sources...</div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="supplemental_registry_json">Supplemental source registry overrides (JSON)</label>
                                <textarea class="form-control" id="supplemental_registry_json" name="supplemental_registry_json" rows="8" placeholder='{"warranty":{"label":"AppleCare"},"speedtest":{"table":"speedtest","join_key":"serial_number","required_columns":["serial_number"]}}'></textarea>
                                <p class="help-block">Optional JSON object keyed by source id. Use this to add or override supported source definitions without editing PHP.</p>
                            </div>
                        </div>
                        <div class="simplemdm-admin-subsection">
                            <h4 class="simplemdm-admin-subsection-title">Client Reporter Ingestion</h4>
                            <p class="simplemdm-admin-subsection-copy"><strong>Option B</strong>: accept client-reported facts posted directly into this SimpleMDM module and include them in supplemental device data.</p>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" id="client_reporter_enabled" name="client_reporter_enabled" value="1">
                                    Enable client reporter ingestion
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" id="client_reporter_history_enabled" name="client_reporter_history_enabled" value="1">
                                    Store client reporter history records
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="client_reporter_secret">Client Reporter Secret</label>
                                <input type="password" class="form-control" id="client_reporter_secret" name="client_reporter_secret" placeholder="Required for ingest_client_facts">
                                <p class="help-block">Accepted via `X-SIMPLEMDM-CLIENT-SECRET` when posting client facts into this MunkiReport module at `index?op=ingest_client_facts`.</p>
                            </div>
                            <div class="form-group">
                                <label for="client_reporter_max_payload_bytes">Client Reporter Max Payload Bytes</label>
                                <input type="number" min="1024" step="1" class="form-control" id="client_reporter_max_payload_bytes" name="client_reporter_max_payload_bytes" placeholder="16384">
                            </div>
                            <div class="form-group">
                                <label for="client_reporter_allowed_fact_keys_json">Client Reporter Allowlist (JSON array)</label>
                                <textarea class="form-control" id="client_reporter_allowed_fact_keys_json" name="client_reporter_allowed_fact_keys_json" rows="5" placeholder='["mdm_profile_present","console_user","uptime_seconds","munki_last_run_result","local_filevault_enabled"]'></textarea>
                                <p class="help-block">Keep this narrow. Unknown keys are rejected by the ingest endpoint.</p>
                            </div>
                            <div class="simplemdm-admin-subsection" style="margin-top:18px;">
                                <h5 class="simplemdm-admin-subsection-title" style="font-size:15px;">Optional Client Security Hardening</h5>
                                <p class="simplemdm-admin-subsection-copy">These options are additive. Leave them off to keep the original shared-secret flow. Enable them only after updating the client-side reporter to send the extra headers or route through a trusted proxy.</p>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="client_reporter_hmac_enabled" name="client_reporter_hmac_enabled" value="1">
                                        Require HMAC-signed requests
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="client_reporter_replay_protection_enabled" name="client_reporter_replay_protection_enabled" value="1">
                                        Require timestamp + nonce replay protection
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="client_reporter_per_device_tokens_enabled" name="client_reporter_per_device_tokens_enabled" value="1">
                                        Require per-device client tokens
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="client_reporter_proxy_only_enabled" name="client_reporter_proxy_only_enabled" value="1">
                                        Require a trusted proxy for ingest
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label for="client_reporter_max_time_skew_seconds">Max Timestamp Skew (seconds)</label>
                                    <input type="number" min="30" step="1" class="form-control" id="client_reporter_max_time_skew_seconds" name="client_reporter_max_time_skew_seconds" placeholder="300">
                                    <p class="help-block">Used by HMAC validation and replay protection. Clients outside this clock skew window are rejected.</p>
                                </div>
                                <div class="form-group">
                                    <label for="client_reporter_ip_allowlist">Client Reporter IP Allowlist</label>
                                    <textarea class="form-control" id="client_reporter_ip_allowlist" name="client_reporter_ip_allowlist" rows="3" placeholder="10.0.0.0/8&#10;192.168.1.10"></textarea>
                                    <p class="help-block">Optional newline- or comma-separated exact IPs or CIDR ranges. When set, client reporter ingest is accepted only from matching client IPs.</p>
                                </div>
                                <div class="form-group">
                                    <label for="client_reporter_trusted_proxy_ips">Trusted Proxy IPs</label>
                                    <textarea class="form-control" id="client_reporter_trusted_proxy_ips" name="client_reporter_trusted_proxy_ips" rows="3" placeholder="127.0.0.1&#10;10.10.0.0/16"></textarea>
                                    <p class="help-block">Optional newline- or comma-separated exact IPs or CIDR ranges. Required when <em>Require a trusted proxy for ingest</em> is enabled. The module will trust <code>X-Forwarded-For</code> and <code>X-Real-IP</code> only from these proxies.</p>
                                </div>
                                <div class="form-group">
                                    <label for="client_reporter_device_tokens_json">Device Token Provisioning (JSON, write-only)</label>
                                    <textarea class="form-control" id="client_reporter_device_tokens_json" name="client_reporter_device_tokens_json" rows="6" placeholder='{"C02ABC123":"token-for-this-device"}'></textarea>
                                    <p class="help-block">Optional. Leave blank to keep existing device tokens. Submit <code>[]</code> to clear all tokens. Tokens are stored hashed and are not returned to the browser after save.</p>
                                </div>
                                <div class="form-group">
                                    <label for="client_reporter_device_token_metadata_json">Configured Device Tokens</label>
                                    <textarea class="form-control" id="client_reporter_device_token_metadata_json" rows="6" readonly>Loading token metadata...</textarea>
                                    <p class="help-block">Metadata only. Raw device tokens are never returned once saved.</p>
                                </div>
                                <div class="form-group">
                                    <label>Client Reporter Requirements</label>
                                    <p class="help-block">Live summary of what the client must send with the current Option B settings. This is intended to reduce guesswork before deploying a reporter.</p>
                                    <div id="client-reporter-requirements-panel" class="simplemdm-state-panel">
                                        <div class="simplemdm-runs-empty">Loading client reporter requirements...</div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="client_reporter_required_headers_text">Required Headers</label>
                                    <textarea class="form-control" id="client_reporter_required_headers_text" rows="5" readonly>Loading...</textarea>
                                </div>
                                <div class="form-group">
                                    <label for="client_reporter_sample_request_text">Sample Request Notes</label>
                                    <textarea class="form-control" id="client_reporter_sample_request_text" rows="9" readonly>Loading...</textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Enrichment Settings</button>
                        <span id="enrichment-save-status" style="margin-left: 10px;"></span>
                    </form>
                </div>
            </div>

            <div class="panel panel-default simplemdm-modern-widget simplemdm-admin-collapsible" data-collapsible="events" data-default-open="0">
                <div class="panel-heading" data-collapsible-toggle="events">
                    <div class="simplemdm-admin-heading-wrap">
                        <div class="simplemdm-admin-heading-main">
                            <h3 class="panel-title"><i class="fa fa-bullhorn"></i> Event Settings</h3>
                            <div class="simplemdm-admin-heading-summary" id="summary-events">Built-in event toggles, stale threshold, and custom event rules</div>
                        </div>
                        <span class="simplemdm-admin-heading-toggle" id="toggle-events">Expand</span>
                    </div>
                </div>
                <div class="panel-body" data-collapsible-body="events" style="display:none;">
                    <p class="text-muted">Use this card to control which SimpleMDM events are emitted into the MunkiReport Events feed. Built-in events can be enabled or disabled individually, and custom rules can create additional current-state events from supported device fields.</p>
                    <form id="simplemdm-event-form">
                        <div class="simplemdm-admin-subsection">
                            <h4 class="simplemdm-admin-subsection-title">Built-In Events</h4>
                            <p class="simplemdm-admin-subsection-copy">These toggles affect future event writes only. Existing rows in the host <code>event</code> table are not retroactively removed when a toggle is disabled.</p>
                            <div class="form-group">
                                <label for="event_stale_threshold_hours">Default stale threshold (hours)</label>
                                <input type="number" min="1" step="1" class="form-control" id="event_stale_threshold_hours" name="event_stale_threshold_hours" placeholder="168">
                                <p class="help-block">Used by the built-in <code>simplemdm_stale</code> event. Custom stale rules can define their own threshold.</p>
                            </div>
                            <div id="event-builtin-toggle-list" class="simplemdm-event-toggle-grid">
                                <div class="simplemdm-runs-empty">Loading built-in event settings...</div>
                            </div>
                        </div>
                        <div class="simplemdm-admin-subsection">
                            <h4 class="simplemdm-admin-subsection-title">Custom Events</h4>
                            <p class="simplemdm-admin-subsection-copy">Custom events are intentionally constrained to supported fields and trigger types. Each rule creates its own <code>simplemdm_*</code> module key and can be enabled or disabled independently.</p>
                            <div class="simplemdm-state-panel">
                                <p class="simplemdm-state-line"><span class="simplemdm-state-label">What you are defining:</span> a rule that watches one synced SimpleMDM device field and emits a separate MunkiReport event when the selected trigger becomes true.</p>
                                <p class="simplemdm-state-line"><span class="simplemdm-state-label">Source Field:</span> choose the SimpleMDM-backed field to watch. These values come from the module's synced device record and webhook device updates.</p>
                                <p class="simplemdm-state-line"><span class="simplemdm-state-label">Trigger:</span> choose how that field should fire. Use <code>Changed To</code> for status values like enrollment, <code>Became Disabled</code> for protections like FileVault or Firewall, and <code>Older Than Hours</code> for <code>Last Seen</code>.</p>
                                <p class="simplemdm-state-line"><span class="simplemdm-state-label">Target Value:</span> only used with <code>Changed To</code>. For example, use <code>unenrolled</code> with <code>Enrollment Status</code>.</p>
                                <p class="simplemdm-state-line"><span class="simplemdm-state-label">Suffix:</span> becomes the module key <code>simplemdm_&lt;suffix&gt;</code>. Keep it unique, lowercase, and underscore-separated.</p>
                                <p class="simplemdm-state-line"><span class="simplemdm-state-label">Examples:</span> <code>Enrollment Status</code> + <code>Changed To</code> + <code>unenrolled</code>; <code>FileVault</code> + <code>Became Disabled</code>; <code>Last Seen</code> + <code>Older Than Hours</code> + <code>48</code>.</p>
                            </div>
                            <div class="simplemdm-actions-row" style="margin-bottom:12px;">
                                <button type="button" class="btn btn-default btn-sm" id="add-custom-event-rule">
                                    <i class="fa fa-plus"></i> Add Custom Event
                                </button>
                                <span id="event-builder-status" class="simplemdm-event-inline-status text-muted"></span>
                            </div>
                            <div id="custom-event-rule-list" class="simplemdm-custom-event-grid">
                                <div class="simplemdm-runs-empty">No custom event rules configured.</div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Event Settings</button>
                        <span id="event-save-status" style="margin-left: 10px;"></span>
                    </form>
                </div>
            </div>

            <div class="panel panel-default simplemdm-modern-widget simplemdm-admin-collapsible" data-collapsible="mcpfindings" data-default-open="0">
                <div class="panel-heading" data-collapsible-toggle="mcpfindings">
                    <div class="simplemdm-admin-heading-wrap">
                        <div class="simplemdm-admin-heading-main">
                            <h3 class="panel-title"><i class="fa fa-flag"></i> MCP Findings Settings</h3>
                            <div class="simplemdm-admin-heading-summary" id="summary-mcpfindings">Enable/disable, metadata size cap, and auto-resolve behavior</div>
                        </div>
                        <span class="simplemdm-admin-heading-toggle" id="toggle-mcpfindings">Expand</span>
                    </div>
                </div>
                <div class="panel-body" data-collapsible-body="mcpfindings" style="display:none;">
                    <p class="text-muted">Controls for the MCP findings ingest/read/admin-action routes (<code>ingest_mcp_findings</code>, <code>get_mcp_findings</code>, and the acknowledge/resolve/ignore/suppress admin actions).</p>
                    <form id="simplemdm-mcpfindings-form">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="mcp_findings_enabled" name="mcp_findings_enabled" value="1">
                                Enable MCP findings ingest, read, and admin-action routes
                            </label>
                            <p class="help-block">When off, <code>ingest_mcp_findings</code>, <code>get_mcp_findings</code>, and the acknowledge/resolve/ignore/suppress routes all return a 403 disabled error. The dashboard widget stays visible and shows its normal "failed to load" message.</p>
                        </div>
                        <div class="form-group">
                            <label for="mcp_findings_metadata_max_bytes">Metadata Max Bytes</label>
                            <input type="number" min="1024" step="1" class="form-control" id="mcp_findings_metadata_max_bytes" name="mcp_findings_metadata_max_bytes" placeholder="65536">
                            <p class="help-block">Maximum size (in characters) of each finding's <code>data</code> field. Larger payloads are truncated at ingest time.</p>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="mcp_findings_auto_resolve" name="mcp_findings_auto_resolve" value="1">
                                Enable complete-scan auto-resolve
                            </label>
                            <p class="help-block">When off, a complete scan (<code>replace: true</code>) never auto-resolves findings absent from the scan, regardless of what the push request sends.</p>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="mcp_findings_event_enabled" name="mcp_findings_event_enabled" value="1">
                                Publish a fleet findings summary event
                            </label>
                            <p class="help-block">Off by default. When on, ingest and admin-action routes write/clear a single deduplicated event (module <code>simplemdm_mcp_findings_summary</code>) anchored to the worst-affected device. Existing installs' Events UI is unchanged unless this is explicitly enabled.</p>
                        </div>
                        <div class="form-group">
                            <label for="mcp_findings_event_warning_threshold">Summary Event Warning Threshold</label>
                            <input type="number" min="1" step="1" class="form-control" id="mcp_findings_event_warning_threshold" name="mcp_findings_event_warning_threshold" placeholder="1">
                            <p class="help-block">Minimum active warning-severity finding count (fleet-wide) before the summary event escalates to "warning". Values below 1 are clamped up to 1.</p>
                        </div>
                        <button type="submit" class="btn btn-primary">Save MCP Findings Settings</button>
                        <span id="mcpfindings-save-status" style="margin-left: 10px;"></span>
                    </form>
                </div>
            </div>

            <div class="panel panel-default simplemdm-modern-widget simplemdm-admin-collapsible" data-collapsible="manual" data-default-open="0">
                <div class="panel-heading" data-collapsible-toggle="manual">
                    <div class="simplemdm-admin-heading-wrap">
                        <div class="simplemdm-admin-heading-main">
                            <h3 class="panel-title"><i class="fa fa-download"></i> Manual / Outside-Module Access</h3>
                            <div class="simplemdm-admin-heading-summary" id="summary-manual">Download scripts and copy external commands</div>
                        </div>
                        <span class="simplemdm-admin-heading-toggle" id="toggle-manual">Expand</span>
                    </div>
                </div>
                <div class="panel-body" data-collapsible-body="manual" style="display:none;">
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

            <div class="simplemdm-admin-column">
                <div class="simplemdm-admin-stack">
            <div class="panel panel-default simplemdm-modern-widget simplemdm-admin-collapsible" data-collapsible="sync" data-default-open="1">
                <div class="panel-heading" data-collapsible-toggle="sync">
                    <div class="simplemdm-admin-heading-wrap">
                        <div class="simplemdm-admin-heading-main">
                            <h3 class="panel-title"><i class="fa fa-refresh"></i> Sync Status</h3>
                            <div class="simplemdm-admin-heading-summary" id="summary-sync">Current queue and latest completed run</div>
                        </div>
                        <span class="simplemdm-admin-heading-toggle" id="toggle-sync">Collapse</span>
                    </div>
                </div>
                <div class="panel-body" data-collapsible-body="sync">
                    <p class="text-muted">Use this card to see queue state, last completed runs, and whether work is waiting for cron or manual pickup. It is status and queue visibility, not the place where immediate in-module sync runs are configured.</p>
                    <table class="table table-striped">
                        <tr>
                            <th>Queue State</th>
                            <td id="sync-request-state">idle</td>
                        </tr>
                        <tr>
                            <th>Last Queue Request</th>
                            <td id="sync-requested-at">-</td>
                        </tr>
                        <tr>
                            <th>Queue Pickup Time</th>
                            <td id="sync-started-at">-</td>
                        </tr>
                        <tr>
                            <th>Last Completed Source</th>
                            <td id="sync-source">-</td>
                        </tr>
                        <tr>
                            <th>Last Completed Status</th>
                            <td id="sync-status">-</td>
                        </tr>
                        <tr>
                            <th>Last Completed Time</th>
                            <td id="sync-time">-</td>
                        </tr>
                    </table>
                    <div class="simplemdm-actions-row">
                        <button type="button" class="btn btn-default" id="simplemdm-sync-now">Queue Next Worker Run</button>
                        <span id="sync-request-message" class="text-muted"></span>
                    </div>
                    <p class="text-muted small" style="margin-top:10px;">This does not run immediately. It queues a sync request for the next host-side cron or manual worker pickup, which still executes <code>simplemdm_sync.py</code>. Use <strong>Run Sync Now</strong> below for immediate in-module execution.</p>
                    <div style="margin-top:16px;">
                        <div class="simplemdm-actions-row" style="justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                            <h4 style="margin:0;">Recent Runs</h4>
                            <button type="button" class="btn btn-default btn-sm" id="clear-sync-runs-btn">Clear Run History</button>
                        </div>
                        <div id="sync-recent-runs" class="simplemdm-runs-list">
                            <div class="simplemdm-runs-empty">Loading recent sync history...</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel panel-default simplemdm-modern-widget simplemdm-admin-collapsible" data-collapsible="advanced" data-default-open="0">
                <div class="panel-heading" data-collapsible-toggle="advanced">
                        <div class="simplemdm-admin-heading-wrap">
                            <div class="simplemdm-admin-heading-main">
                            <h3 class="panel-title"><i class="fa fa-sliders"></i> Security And Sync Controls</h3>
                            <div class="simplemdm-admin-heading-summary" id="summary-advanced">Secrets, compliance target, delta sync, command sync, and deep sync controls</div>
                            </div>
                            <span class="simplemdm-admin-heading-toggle" id="toggle-advanced">Expand</span>
                        </div>
                </div>
                <div class="panel-body" data-collapsible-body="advanced" style="display:none;">
                    <p class="text-muted">Use this card for security-sensitive settings and sync behavior that changes how much data SimpleMDM collects from the API.</p>
                    <form id="simplemdm-advanced-form">
                        <div class="simplemdm-admin-subsection">
                            <h4 class="simplemdm-admin-subsection-title">Endpoint Security</h4>
                            <p class="simplemdm-admin-subsection-copy">These secrets protect inbound webhook traffic and mutating passthrough actions. Leave them blank only if you are not using those endpoints.</p>
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
                        </div>
                        <div class="simplemdm-admin-subsection">
                            <h4 class="simplemdm-admin-subsection-title">Sync Scope And API Load</h4>
                            <p class="simplemdm-admin-subsection-copy">These settings control how much SimpleMDM data is collected and how expensive sync becomes. Deep sync improves detail views, but it can increase runtime and API load.</p>
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
                                    <input type="checkbox" id="sync_device_subresources_enabled" name="sync_device_subresources_enabled" value="1">
                                    Enable deep per-device subresource sync (profiles/apps/users)
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="device_subresource_limit">Per-device deep sync limit</label>
                                <input type="number" min="0" step="1" class="form-control" id="device_subresource_limit" name="device_subresource_limit" placeholder="0 = all devices">
                                <p class="help-block">Set `0` for all devices, or cap the number of devices to reduce API load.</p>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Advanced Settings</button>
                        <span id="advanced-save-status" style="margin-left: 10px;"></span>
                    </form>
                </div>
            </div>

            <div class="panel panel-default simplemdm-modern-widget simplemdm-admin-collapsible" data-collapsible="schedule" data-default-open="1">
                <div class="panel-heading" data-collapsible-toggle="schedule">
                    <div class="simplemdm-admin-heading-wrap">
                        <div class="simplemdm-admin-heading-main">
                            <h3 class="panel-title"><i class="fa fa-calendar"></i> In-Module Sync And Schedule</h3>
                            <div class="simplemdm-admin-heading-summary" id="summary-schedule">Immediate run readiness and recurring schedule state</div>
                        </div>
                        <span class="simplemdm-admin-heading-toggle" id="toggle-schedule">Collapse</span>
                    </div>
                </div>
                <div class="panel-body" data-collapsible-body="schedule">
                    <p class="text-muted">Use this section for the recurring sync lifecycle: schedule enable/disable, cron cadence, worker interval, immediate runs, and cron management when in-module execution is enabled.</p>
                    <form id="simplemdm-script-runner-form">
                        <div class="simplemdm-admin-subsection">
                            <h4 class="simplemdm-admin-subsection-title">Schedule Configuration</h4>
                            <p class="simplemdm-admin-subsection-copy">Use this section to define when recurring sync should run and what runtime settings the worker should use.</p>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <tr>
                                        <th>Schedule Config</th>
                                        <td id="schedule-config">Disabled</td>
                                    </tr>
                                    <tr>
                                        <th>Recurring Sync Ready</th>
                                        <td id="schedule-ready">No</td>
                                    </tr>
                                    <tr>
                                        <th>Last Run</th>
                                        <td id="schedule-last-run">-</td>
                                    </tr>
                                    <tr>
                                        <th>Last Run Source</th>
                                        <td id="schedule-last-run-source">-</td>
                                    </tr>
                                    <tr>
                                        <th>Next Expected Run</th>
                                        <td id="schedule-next-run">-</td>
                                    </tr>
                                </table>
                            </div>
                            <p class="text-muted small" style="margin-bottom:10px;"><strong>Cron cadence</strong> controls how often cron launches the worker. <strong>Worker minimum interval</strong> controls how often the worker is actually allowed to sync when `--respect-schedule` is used.</p>
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
                                <p class="help-block">Use a preset or enter a custom cron expression for how often cron launches the worker.</p>
                            </div>
                            <div class="form-group">
                                <label for="sync_interval_minutes">Worker Minimum Interval (minutes)</label>
                                <input type="number" min="1" step="1" class="form-control" id="sync_interval_minutes" name="sync_interval_minutes" placeholder="15">
                                <p class="help-block">Used by <code>simplemdm_sync.py --respect-schedule</code>. Cron can run more often, but the worker will only sync when this interval says it is due.</p>
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
                        </div>
                        <div class="simplemdm-admin-subsection">
                            <h4 class="simplemdm-admin-subsection-title">Runtime Readiness And Actions</h4>
                            <p class="simplemdm-admin-subsection-copy">Use this section to verify prerequisites, see whether module-side execution is available, and run or schedule sync actions.</p>
                            <p class="text-muted small" style="margin-bottom:10px;"><strong>Prerequisites</strong> below show whether the current settings are sufficient for immediate runs, scheduled runs, and module-managed cron actions.</p>
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
                            <div class="alert alert-info" id="module-runtime-guidance" style="margin-top:16px; margin-bottom:0;">
                                Checking module guidance...
                            </div>
                            <div class="simplemdm-schedule-actions">
                                <div class="simplemdm-schedule-primary">
                                    <button type="button" class="btn btn-primary" id="run-sync-now-btn">Run Sync Now</button>
                                    <button type="button" class="btn btn-success" id="enable-schedule-btn">Enable Scheduled Sync</button>
                                    <button type="button" class="btn btn-default" id="disable-schedule-btn">Disable Scheduled Sync</button>
                                </div>
                                <div class="simplemdm-schedule-secondary">
                                    <button type="submit" class="btn btn-default">Save Schedule Settings</button>
                                </div>
                            </div>
                            <div id="script-runner-save-status" class="text-muted" style="margin-top:10px;"></div>
                            <p class="text-muted small" style="margin-top:10px;">One-off runs execute <code>simplemdm_sync.py</code> immediately. Repeating runs still use cron, which this module can install or remove when script execution is enabled.</p>
                        </div>
                    </form>
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
    var supplementalDisabledSourceIds = [];
    var RUNNER_STATUS_TIMEOUT_MS = 5000;
    var syncPollTimer = null;
    var SYNC_POLL_INTERVAL_MS = 3000;
    var SYNC_POLL_TIMEOUT_MS = 180000;
    var backgroundRefreshTimer = null;
    var BACKGROUND_IDLE_REFRESH_MS = 5000;
    var BACKGROUND_ACTIVE_REFRESH_MS = 2500;
    var builtInEventCatalog = {};
    var customEventFieldCatalog = {};
    var eventFormDirty = false;

    function parseIsoDate(value) {
        var raw = String(value || '').trim();
        if (!raw) {
            return null;
        }
        if (raw.indexOf(' - ') !== -1) {
            raw = raw.split(' - ', 1)[0].trim();
        }
        if (raw.slice(-1) === 'Z') {
            raw = raw.slice(0, -1) + '+00:00';
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
        var lastStatus = String(data.last_completed_sync_status || data.last_sync_status || '').trim();
        var lastTime = String(data.last_completed_sync_time || data.last_sync_time || '').trim();
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

    function formatDurationMs(value) {
        var ms = parseInt(String(value || ''), 10);
        if (isNaN(ms) || ms < 1) {
            return '-';
        }
        if (ms < 1000) {
            return ms + ' ms';
        }
        var seconds = Math.round(ms / 1000);
        if (seconds < 60) {
            return seconds + 's';
        }
        var minutes = Math.floor(seconds / 60);
        var remaining = seconds % 60;
        return minutes + 'm ' + remaining + 's';
    }

    function escapeHtml(value) {
        return $('<div>').text(String(value || '')).html();
    }

    function parseJsonValue(value, fallback) {
        if (value === undefined || value === null || value === '') {
            return fallback;
        }
        if (typeof value === 'object') {
            return value;
        }
        try {
            var parsed = JSON.parse(String(value));
            return parsed;
        } catch (err) {
            return fallback;
        }
    }

    function eventFieldLabel(fieldName) {
        if (customEventFieldCatalog && customEventFieldCatalog[fieldName] && customEventFieldCatalog[fieldName].label) {
            return String(customEventFieldCatalog[fieldName].label);
        }
        return String(fieldName || '').replace(/_/g, ' ');
    }

    function eventTriggerLabel(triggerName) {
        var labels = {
            became_disabled: 'Became Disabled',
            changed_to: 'Changed To',
            older_than_hours: 'Older Than Hours'
        };
        return labels[String(triggerName || '')] || String(triggerName || '').replace(/_/g, ' ');
    }

    function slugifyCustomEventPart(value) {
        return String(value || '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '')
            .replace(/_+/g, '_');
    }

    function suggestedCustomEventSuffix(fieldName, triggerName) {
        var fieldSlugMap = {
            status: 'enrollment_status',
            filevault_enabled: 'filevault',
            firewall_enabled: 'firewall',
            is_dep_enrollment: 'ade_dep',
            is_supervised: 'supervision',
            last_seen_at: 'last_seen',
            passcode_compliant: 'passcode_compliance',
            sip_enabled: 'sip',
            activation_lock_enabled: 'activation_lock'
        };
        var triggerSlugMap = {
            changed_to: 'changed',
            became_disabled: 'disabled',
            older_than_hours: 'stale'
        };
        var fieldSlug = fieldSlugMap[String(fieldName || '')] || slugifyCustomEventPart(eventFieldLabel(fieldName || ''));
        var triggerSlug = triggerSlugMap[String(triggerName || '')] || slugifyCustomEventPart(triggerName || '');
        return slugifyCustomEventPart(fieldSlug + '_' + triggerSlug);
    }

    function syncCustomEventRuleSuffix($row, fieldName, triggerName) {
        var $suffix = $row.find('.simplemdm-custom-event-suffix');
        var autoFill = String($suffix.attr('data-autofill') || '0') === '1';
        if (!autoFill) {
            return;
        }
        $suffix.val(suggestedCustomEventSuffix(fieldName, triggerName));
        updateCustomEventRuleHeader($row);
    }

    function syncCustomEventRuleInputs($row) {
        var fieldName = String($row.find('.simplemdm-custom-event-source-field').val() || '');
        var triggerName = String($row.find('.simplemdm-custom-event-trigger-type').val() || '');
        var fieldMeta = customEventFieldCatalog[fieldName] || { triggers: [] };
        var allowedTriggers = $.isArray(fieldMeta.triggers) ? fieldMeta.triggers : [];
        var $trigger = $row.find('.simplemdm-custom-event-trigger-type');
        var currentTrigger = triggerName;

        $trigger.empty();
        if (!allowedTriggers.length) {
            $trigger.append('<option value="">No triggers available</option>');
        } else {
            allowedTriggers.forEach(function(trigger) {
                var option = $('<option>').val(trigger).text(eventTriggerLabel(trigger));
                $trigger.append(option);
            });
        }
        if (allowedTriggers.indexOf(currentTrigger) === -1) {
            currentTrigger = allowedTriggers.length ? allowedTriggers[0] : '';
        }
        $trigger.val(currentTrigger);

        var showTarget = currentTrigger === 'changed_to';
        var showThreshold = currentTrigger === 'older_than_hours';
        $row.find('.simplemdm-custom-event-target-wrap').toggle(showTarget);
        $row.find('.simplemdm-custom-event-threshold-wrap').toggle(showThreshold);
        syncCustomEventRuleSuffix($row, fieldName, currentTrigger);
        updateCustomEventRuleGuidance($row, fieldName, currentTrigger);
    }

    function updateCustomEventRuleGuidance($row, fieldName, triggerName) {
        var fieldLabel = eventFieldLabel(fieldName);
        var $target = $row.find('.simplemdm-custom-event-target-value');
        var $threshold = $row.find('.simplemdm-custom-event-threshold-hours');
        var $message = $row.find('.simplemdm-custom-event-message');
        var $guidance = $row.find('.simplemdm-custom-event-guidance');
        var exampleTarget = '';
        var messageText = '';
        var messagePlaceholder = 'SimpleMDM: custom event triggered';

        if (fieldName === 'status') {
            exampleTarget = 'unenrolled';
        } else if (fieldName === 'last_seen_at') {
            exampleTarget = '';
        }

        if (triggerName === 'changed_to') {
            messageText = 'Use the exact stored value for ' + fieldLabel + '. Example: ' + (exampleTarget || 'enter the exact status value returned by the module') + '.';
            $target.attr('placeholder', exampleTarget ? ('Example: ' + exampleTarget) : 'Enter exact stored value');
            messagePlaceholder = 'SimpleMDM: ' + fieldLabel.toLowerCase() + ' changed to ' + (exampleTarget || '<value>');
        } else if (triggerName === 'became_disabled') {
            messageText = 'This rule fires when ' + fieldLabel + ' changes from enabled/on to disabled/off.';
            $target.attr('placeholder', 'Not used for this trigger');
            messagePlaceholder = 'SimpleMDM: ' + fieldLabel + ' became disabled';
        } else if (triggerName === 'older_than_hours') {
            messageText = 'This rule fires when ' + fieldLabel + ' is older than the selected hour threshold.';
            $threshold.attr('placeholder', fieldName === 'last_seen_at' ? 'Example: 48' : '24');
            messagePlaceholder = 'SimpleMDM: ' + fieldLabel + ' is older than threshold';
        } else {
            messageText = 'Choose a supported field and trigger to define the rule.';
            $target.attr('placeholder', 'Enter value if required');
        }

        if (!$message.val()) {
            $message.attr('placeholder', messagePlaceholder);
        }

        $guidance.text(messageText);
    }

    function createCustomEventRuleRow(rule) {
        var data = $.extend({
            enabled: '1',
            suffix: '',
            label: '',
            source_field: 'status',
            trigger_type: 'changed_to',
            severity: 'warning',
            message: '',
            target_value: '',
            threshold_hours: ''
        }, rule || {});

        var $row = $('<div class="simplemdm-custom-event-row">');
        var $head = $('<div class="simplemdm-custom-event-head">');
        var titleText = data.label ? data.label : (data.suffix ? data.suffix : 'New Custom Event');
        $head.append(
            $('<div>').append(
                $('<div class="simplemdm-custom-event-title">').text(titleText)
            ).append(
                $('<div class="simplemdm-custom-event-meta">').text('Custom module key: simplemdm_' + (data.suffix || '<suffix>'))
            )
        );
        $head.append(
            $('<button type="button" class="btn btn-default btn-xs simplemdm-remove-custom-event">')
                .html('<i class="fa fa-trash"></i> Remove')
        );
        $row.append($head);

        var $fields = $('<div class="simplemdm-custom-event-fields">');
        var fieldOptions = Object.keys(customEventFieldCatalog).sort();
        var $sourceSelect = $('<select class="form-control simplemdm-custom-event-source-field"></select>');
        fieldOptions.forEach(function(fieldName) {
            $sourceSelect.append($('<option>').val(fieldName).text(eventFieldLabel(fieldName)));
        });

        $fields.append(
            $('<div>').append('<label>Suffix</label>').append(
                $('<input type="text" class="form-control simplemdm-custom-event-suffix" placeholder="custom_suffix">')
                    .attr('data-autofill', data.suffix ? '0' : '1')
                    .val(data.suffix || '')
            ).append(
                $('<div class="simplemdm-custom-event-guidance">').text('Admin-defined rule key. This does not come from the SimpleMDM API or widget data; it becomes simplemdm_<suffix> in MunkiReport.')
            )
        );
        $fields.append(
            $('<div>').append('<label>Label</label>').append(
                $('<input type="text" class="form-control simplemdm-custom-event-label" placeholder="Optional label">').val(data.label || '')
            )
        );
        $fields.append(
            $('<div>').append('<label>Source Field</label>').append($sourceSelect.val(data.source_field || 'status'))
        );
        $fields.append(
            $('<div>').append('<label>Trigger</label>').append(
                $('<select class="form-control simplemdm-custom-event-trigger-type"></select>')
            )
        );
        $fields.append(
            $('<div class="simplemdm-custom-event-target-wrap">').append('<label>Target Value</label>').append(
                $('<input type="text" class="form-control simplemdm-custom-event-target-value" placeholder="Example: retired">').val(data.target_value || '')
            )
        );
        $fields.append(
            $('<div class="simplemdm-custom-event-threshold-wrap">').append('<label>Threshold Hours</label>').append(
                $('<input type="number" min="1" step="1" class="form-control simplemdm-custom-event-threshold-hours" placeholder="24">').val(data.threshold_hours || '')
            )
        );
        $fields.append(
            $('<div>').append('<label>Severity</label>').append(
                $('<select class="form-control simplemdm-custom-event-severity">' +
                    '<option value="info">Info</option>' +
                    '<option value="warning">Warning</option>' +
                    '<option value="danger">Danger</option>' +
                    '<option value="success">Success</option>' +
                '</select>').val(data.severity || 'warning')
            )
        );
        $fields.append(
            $('<div class="simplemdm-custom-event-field-full">').append('<label>Message</label>').append(
                $('<input type="text" class="form-control simplemdm-custom-event-message" placeholder="SimpleMDM: custom event message">').val(data.message || '')
            )
        );
        $row.append($fields);
        $row.append($('<div class="simplemdm-custom-event-guidance">'));
        $row.append(
            $('<div class="simplemdm-custom-event-actions">')
                .append(
                    $('<label class="simplemdm-inline-checkbox">')
                        .append($('<input type="checkbox" class="simplemdm-custom-event-enabled" value="1">').prop('checked', String(data.enabled || '1') === '1'))
                        .append('<span>Enabled</span>')
                )
                .append(
                    $('<div class="simplemdm-custom-event-meta">').text('Use a unique suffix to create a separate current event slot in MunkiReport.')
                )
        );

        syncCustomEventRuleInputs($row);
        return $row;
    }

    function updateCustomEventRuleHeader($row) {
        var label = String($row.find('.simplemdm-custom-event-label').val() || '').trim();
        var suffix = String($row.find('.simplemdm-custom-event-suffix').val() || '').trim();
        $row.find('.simplemdm-custom-event-title').text(label || suffix || 'New Custom Event');
        $row.find('.simplemdm-custom-event-meta').first().text('Custom module key: simplemdm_' + (suffix || '<suffix>'));
    }

    function renderBuiltInEventToggles(catalog, settings) {
        builtInEventCatalog = parseJsonValue(catalog, {});
        var settingMap = parseJsonValue(settings, {});
        var $list = $('#event-builtin-toggle-list').empty();
        var keys = Object.keys(builtInEventCatalog).sort();

        if (!keys.length) {
            $list.append('<div class="simplemdm-runs-empty">No built-in events available.</div>');
            return;
        }

        keys.forEach(function(suffix) {
            var meta = builtInEventCatalog[suffix] || {};
            var checked = !Object.prototype.hasOwnProperty.call(settingMap, suffix) || String(settingMap[suffix]) !== '0';
            var $row = $('<div class="simplemdm-event-toggle-row">');
            var $head = $('<div class="simplemdm-event-toggle-head">');
            $head.append(
                $('<div>').append(
                    $('<div class="simplemdm-event-toggle-title">').text(meta.label || suffix)
                ).append(
                    $('<div class="simplemdm-event-toggle-meta">').text(meta.description || '')
                )
            );
            $head.append(
                $('<label class="simplemdm-inline-checkbox">')
                    .append($('<input type="checkbox" class="simplemdm-event-toggle-checkbox" value="1">').attr('data-event-suffix', suffix).prop('checked', checked))
                    .append('<span>Enabled</span>')
            );
            $row.append($head);
            $list.append($row);
        });
    }

    function renderCustomEventRules(rules, fieldCatalog) {
        customEventFieldCatalog = parseJsonValue(fieldCatalog, {});
        var ruleList = parseJsonValue(rules, []);
        if (!$.isArray(ruleList)) {
            ruleList = [];
        }

        var $list = $('#custom-event-rule-list').empty();
        if (!ruleList.length) {
            $list.append('<div class="simplemdm-runs-empty">No custom event rules configured.</div>');
            return;
        }

        ruleList.forEach(function(rule) {
            $list.append(createCustomEventRuleRow(rule));
        });
    }

    function collectBuiltInEventSettings() {
        var settings = {};
        $('.simplemdm-event-toggle-checkbox').each(function() {
            var suffix = String($(this).attr('data-event-suffix') || '');
            if (suffix) {
                settings[suffix] = $(this).is(':checked') ? '1' : '0';
            }
        });
        return settings;
    }

    function collectCustomEventRules() {
        var rules = [];
        $('#custom-event-rule-list .simplemdm-custom-event-row').each(function() {
            var $row = $(this);
            rules.push({
                enabled: $row.find('.simplemdm-custom-event-enabled').is(':checked') ? '1' : '0',
                suffix: $row.find('.simplemdm-custom-event-suffix').val() || '',
                label: $row.find('.simplemdm-custom-event-label').val() || '',
                source_field: $row.find('.simplemdm-custom-event-source-field').val() || '',
                trigger_type: $row.find('.simplemdm-custom-event-trigger-type').val() || '',
                severity: $row.find('.simplemdm-custom-event-severity').val() || 'warning',
                message: $row.find('.simplemdm-custom-event-message').val() || '',
                target_value: $row.find('.simplemdm-custom-event-target-value').val() || '',
                threshold_hours: $row.find('.simplemdm-custom-event-threshold-hours').val() || ''
            });
        });
        return rules;
    }

    function refreshEventSummary() {
        var enabledBuiltIns = 0;
        $('.simplemdm-event-toggle-checkbox').each(function() {
            if ($(this).is(':checked')) {
                enabledBuiltIns++;
            }
        });
        var customCount = $('#custom-event-rule-list .simplemdm-custom-event-row').length;
        updateCollapsibleSummary('events', enabledBuiltIns + ' built-in enabled, ' + customCount + ' custom rule(s)');
    }

    function keepEventPanelOpen() {
        var $body = $('[data-collapsible-body="events"]');
        if (!$body.is(':visible')) {
            $body.stop(true, true).slideDown(0);
            $('#toggle-events').text('Collapse');
        }
    }

    function markEventFormDirty(isDirty) {
        eventFormDirty = !!isDirty;
    }

    function formatSupplementalReason(reason) {
        var value = String(reason || '').trim();
        if (!value) {
            return '-';
        }
        var map = {
            ok: 'Detected and ready',
            no_rows: 'Detected, but no rows are present yet',
            table_missing: 'Module is loaded, but its table is not present',
            required_columns_missing: 'Table exists, but required columns are missing',
            no_supported_join_key: 'Table exists, but no supported serial join column was found',
            schema_error: 'Database schema check failed',
            query_failed: 'The source query failed',
            disabled_in_settings: 'Disabled in SimpleMDM settings',
            client_reporter_disabled: 'Client Reporter is disabled'
        };
        return map[value] || value.replace(/_/g, ' ');
    }

    function renderRecentRuns(runs) {
        var list = $.isArray(runs) ? runs : [];
        var $list = $('#sync-recent-runs');
        $list.empty();

        if (!list.length) {
            $list.append('<div class="simplemdm-runs-empty">No sync runs recorded yet.</div>');
            return;
        }

        list.forEach(function(run) {
            var summary = String(run.summary || '').trim();
            var label = summary ? summary : (run.run_uuid || '-');
            var finishedAt = formatDateOrDash(run.finished_at || run.started_at || run.requested_at);
            var card = '<div class="simplemdm-runs-card">' +
                '<div class="simplemdm-runs-summary" title="' + escapeHtml(label) + '">' + escapeHtml(label) + '</div>' +
                '<div class="simplemdm-runs-meta">' +
                    '<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Source:</span>' + escapeHtml(formatRunSource(run.source || '')) + '</div>' +
                    '<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Status:</span>' + escapeHtml(run.status_label || formatRunSource(run.status || '')) + '</div>' +
                    '<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Finished:</span>' + escapeHtml(finishedAt) + '</div>' +
                    '<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Duration:</span>' + escapeHtml(formatDurationMs(run.duration_ms)) + '</div>' +
                '</div>' +
                '</div>';
            $list.append(card);
        });
    }

    function renderSyncStatus(data) {
        var queueState = String(data.sync_request_state || 'idle');
        var requestedAt = String(data.sync_requested_at || '').trim();
        var startedAt = String(data.sync_started_at || '').trim();
        var source = formatRunSource(data.last_completed_sync_source || data.sync_request_source || '');
        $('#sync-status').text(data.last_completed_sync_status || data.last_sync_status || 'Never');
        $('#sync-time').text(data.last_completed_sync_time || data.last_sync_time || '-');
        $('#sync-request-state').text(queueState);
        $('#sync-requested-at').text(requestedAt || '-');
        $('#sync-started-at').text(
            queueState === 'running' || queueState === 'queued'
                ? (startedAt || '-')
                : '-'
        );
        $('#sync-source').text(source);
        $('#simplemdm-sync-now').prop('disabled', queueState === 'running');
        renderRecentRuns(data.sync_recent_runs || []);
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

    function formatRunSource(value) {
        var source = String(value || '').trim().toLowerCase();
        if (!source) {
            return '-';
        }
        if (source === 'in_module_immediate') {
            return 'Immediate (In-Module)';
        }
        if (source === 'queued_admin') {
            return 'Queued Admin Request';
        }
        if (source === 'scheduled') {
            return 'Scheduled';
        }
        if (source === 'admin') {
            return 'Admin';
        }
        return source.replace(/_/g, ' ').replace(/\b\w/g, function(chr) {
            return chr.toUpperCase();
        });
    }

    function updateCollapsibleSummary(id, text) {
        $('#summary-' + id).text(text || '');
    }

    function syncCollapsibleState(id, open) {
        var $body = $('[data-collapsible-body="' + id + '"]');
        var $toggle = $('#toggle-' + id);
        if (open) {
            $body.show();
            $toggle.text('Collapse');
        } else {
            $body.hide();
            $toggle.text('Expand');
        }
    }

    function initCollapsibles() {
        $('[data-collapsible]').each(function() {
            var id = String($(this).attr('data-collapsible') || '');
            var defaultOpen = String($(this).attr('data-default-open') || '0') === '1';
            syncCollapsibleState(id, defaultOpen);
        });

        $(document).off('click.simplemdmAdminCollapse').on('click.simplemdmAdminCollapse', '[data-collapsible-toggle]', function(e) {
            if ($(e.target).closest('button, a, input, label, textarea, select').length) {
                return;
            }
            var id = String($(this).attr('data-collapsible-toggle') || '');
            var $body = $('[data-collapsible-body="' + id + '"]');
            var open = !$body.is(':visible');
            $body.stop(true, true).slideToggle(160);
            $('#toggle-' + id).text(open ? 'Collapse' : 'Expand');
        });
    }

    function computeRecurringReady(data) {
        var enabled = String(data.enable_scheduled_sync || '0') === '1';
        var schedule = String(data.script_runner_schedule || '').trim() !== '';
        var logPath = String(data.script_runner_log_path || '').trim() !== '';
        var runnerUrl = String(data.script_runner_munkireport_url || '').trim() !== '';
        var apiKeyReady = String(data.api_key_set || (data.api_key ? '1' : '0')) === '1';
        var cronInstalled = !!(runnerStatusCache && runnerStatusCache.cron && runnerStatusCache.cron.installed);

        return enabled && schedule && logPath && runnerUrl && apiKeyReady && cronInstalled;
    }

    function renderScheduleStatus(data) {
        var enabled = String(data.enable_scheduled_sync || '0') === '1';
        var schedule = String(data.script_runner_schedule || '');
        var lastRunRaw = String(data.last_completed_sync_time || data.last_sync_time || '');
        var lastRunSource = formatRunSource(data.last_completed_sync_source || data.sync_request_source || '');
        var statusText = enabled ? 'Enabled' : 'Disabled';
        var recurringReady = computeRecurringReady(data);
        var recurringReadyText = recurringReady ? 'Yes' : 'No';
        var lastRunText = formatDateOrDash(lastRunRaw);
        var nextRunText = enabled ? computeNextExpectedRun(schedule, lastRunRaw) : '-';
        $('#schedule-config').text(statusText);
        $('#schedule-ready').text(recurringReadyText);
        $('#schedule-last-run').text(lastRunText);
        $('#schedule-last-run-source').text(lastRunSource);
        $('#schedule-next-run').text(nextRunText);
        $('#schedule-config-kpi').text(statusText);
        $('#schedule-ready-kpi').text(recurringReadyText);
        $('#schedule-last-run-kpi').text(lastRunText);
        $('#enable-schedule-btn').prop('disabled', enabled);
        $('#disable-schedule-btn').prop('disabled', !enabled);
        updateCollapsibleSummary('schedule', 'Config: ' + statusText + ', Ready: ' + recurringReadyText + ', Last Run: ' + lastRunText);
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
        $('#sync_interval_minutes').val(pickValue(data.sync_interval_minutes, '15'));
        $('#sync_device_subresources_enabled').prop('checked', String(data.sync_device_subresources_enabled || '0') === '1');
        $('#device_subresource_limit').val(pickValue(data.device_subresource_limit, '0'));
        if (!eventFormDirty) {
            $('#event_stale_threshold_hours').val(pickValue(data.event_stale_threshold_hours, '168'));
            renderBuiltInEventToggles(data.event_builtin_catalog_json || '{}', data.event_builtin_settings_json || '{}');
            renderCustomEventRules(data.custom_event_rules_json || '[]', data.custom_event_field_catalog_json || '{}');
        }
        $('#supplemental_enabled').prop('checked', String(data.supplemental_enabled || '1') === '1');
        $('#supplemental_default_stale_after_minutes').val(pickValue(data.supplemental_default_stale_after_minutes, '1440'));
        $('#supplemental_registry_json').val(data.supplemental_registry_json || '');
        $('#client_reporter_enabled').prop('checked', String(data.client_reporter_enabled || '0') === '1');
        $('#client_reporter_history_enabled').prop('checked', String(data.client_reporter_history_enabled || '1') === '1');
        $('#client_reporter_secret').val(data.client_reporter_secret || '');
        $('#client_reporter_max_payload_bytes').val(pickValue(data.client_reporter_max_payload_bytes, '16384'));
        $('#client_reporter_allowed_fact_keys_json').val(data.client_reporter_allowed_fact_keys_json || '');
        $('#mcp_findings_enabled').prop('checked', String(data.mcp_findings_enabled || '1') === '1');
        $('#mcp_findings_metadata_max_bytes').val(pickValue(data.mcp_findings_metadata_max_bytes, '65536'));
        $('#mcp_findings_auto_resolve').prop('checked', String(data.mcp_findings_auto_resolve || '1') === '1');
        $('#mcp_findings_event_enabled').prop('checked', String(data.mcp_findings_event_enabled || '0') === '1');
        $('#mcp_findings_event_warning_threshold').val(pickValue(data.mcp_findings_event_warning_threshold, '1'));
        $('#client_reporter_hmac_enabled').prop('checked', String(data.client_reporter_hmac_enabled || '0') === '1');
        $('#client_reporter_replay_protection_enabled').prop('checked', String(data.client_reporter_replay_protection_enabled || '0') === '1');
        $('#client_reporter_per_device_tokens_enabled').prop('checked', String(data.client_reporter_per_device_tokens_enabled || '0') === '1');
        $('#client_reporter_proxy_only_enabled').prop('checked', String(data.client_reporter_proxy_only_enabled || '0') === '1');
        $('#client_reporter_max_time_skew_seconds').val(pickValue(data.client_reporter_max_time_skew_seconds, '300'));
        $('#client_reporter_ip_allowlist').val(data.client_reporter_ip_allowlist || '');
        $('#client_reporter_trusted_proxy_ips').val(data.client_reporter_trusted_proxy_ips || '');
        $('#client_reporter_device_tokens_json').val('');
        $('#client_reporter_device_token_metadata_json').val(data.client_reporter_device_token_metadata_json || '[]');
        renderClientReporterRequirements(data || {});
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
        updateCollapsibleSummary('api', String(data.api_key || '').trim() !== '' ? 'API key saved' : 'API key not saved');
        updateCollapsibleSummary('advanced',
            'Delta ' + (String(data.sync_delta_enabled || '0') === '1' ? 'on' : 'off') +
            ', Commands ' + (String(data.sync_commands_enabled || '0') === '1' ? 'on' : 'off') +
            ', Deep Sync ' + (String(data.sync_device_subresources_enabled || '0') === '1' ? 'on' : 'off') +
            ', Compliance OS ' + (String(data.compliance_min_os || '').trim() !== '' ? String(data.compliance_min_os) : 'not set')
        );
        updateCollapsibleSummary('enrichment',
            'Supplemental ' + (String(data.supplemental_enabled || '1') === '1' ? 'on' : 'off') +
            ', Client Reporter ' + (String(data.client_reporter_enabled || '0') === '1' ? 'on' : 'off') +
            ', HMAC ' + (String(data.client_reporter_hmac_enabled || '0') === '1' ? 'on' : 'off') +
            ', Source Opt-Outs ' + String(supplementalDisabledSourceIds.length)
        );
        refreshEventSummary();
        var enabledWidgets = 0;
        $('.simplemdm-widget-toggle').each(function() {
            if ($(this).is(':checked')) {
                enabledWidgets++;
            }
        });
        updateCollapsibleSummary('widgets', enabledWidgets + ' widget(s) enabled');
    }

    function renderClientReporterRequirements(data) {
        var enabled = String(data.client_reporter_enabled || '0') === '1';
        var hmacEnabled = String(data.client_reporter_hmac_enabled || '0') === '1';
        var replayEnabled = String(data.client_reporter_replay_protection_enabled || '0') === '1';
        var deviceTokensEnabled = String(data.client_reporter_per_device_tokens_enabled || '0') === '1';
        var proxyOnlyEnabled = String(data.client_reporter_proxy_only_enabled || '0') === '1';
        var allowlistRules = String(data.client_reporter_ip_allowlist || '').trim();
        var trustedProxyRules = String(data.client_reporter_trusted_proxy_ips || '').trim();
        var skewSeconds = String(data.client_reporter_max_time_skew_seconds || '300');
        var tokenMetadataRaw = String(data.client_reporter_device_token_metadata_json || '[]');
        var tokenMetadata = [];

        try {
            tokenMetadata = JSON.parse(tokenMetadataRaw);
            if (!$.isArray(tokenMetadata)) {
                tokenMetadata = [];
            }
        } catch (err) {
            tokenMetadata = [];
        }

        var headers = ['X-SIMPLEMDM-CLIENT-SECRET'];
        if (hmacEnabled || replayEnabled) {
            headers.push('X-SIMPLEMDM-CLIENT-TIMESTAMP');
        }
        if (replayEnabled) {
            headers.push('X-SIMPLEMDM-CLIENT-NONCE');
        }
        if (hmacEnabled) {
            headers.push('X-SIMPLEMDM-CLIENT-SIGNATURE');
        }
        if (deviceTokensEnabled) {
            headers.push('X-SIMPLEMDM-CLIENT-TOKEN');
        }

        var factsAllowlist = String(data.client_reporter_allowed_fact_keys_json || '[]');
        var panelLines = [];
        panelLines.push('<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Mode:</span>' + escapeHtml(enabled ? 'Enabled' : 'Disabled') + '</div>');
        panelLines.push('<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Shared Secret Flow:</span>' + escapeHtml(enabled ? 'Available' : 'Disabled') + '</div>');
        panelLines.push('<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">HMAC Signing:</span>' + escapeHtml(hmacEnabled ? 'Required' : 'Not required') + '</div>');
        panelLines.push('<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Replay Protection:</span>' + escapeHtml(replayEnabled ? ('Required, max skew ' + skewSeconds + ' seconds') : 'Not required') + '</div>');
        panelLines.push('<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Per-Device Token:</span>' + escapeHtml(deviceTokensEnabled ? 'Required' : 'Not required') + '</div>');
        panelLines.push('<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Trusted Proxy:</span>' + escapeHtml(proxyOnlyEnabled ? 'Required' : 'Optional') + '</div>');
        panelLines.push('<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Client IP Allowlist:</span>' + escapeHtml(allowlistRules || 'Not configured') + '</div>');
        panelLines.push('<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Trusted Proxy IPs:</span>' + escapeHtml(trustedProxyRules || 'Not configured') + '</div>');
        panelLines.push('<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Provisioned Device Tokens:</span>' + escapeHtml(String(tokenMetadata.length)) + '</div>');

        $('#client-reporter-requirements-panel').html(
            '<div class="simplemdm-runs-meta">' + panelLines.join('') + '</div>'
        );

        $('#client_reporter_required_headers_text').val(headers.join('\n'));

        var sampleLines = [];
        sampleLines.push('Endpoint: /index.php?/module/simplemdm/index?op=ingest_client_facts');
        sampleLines.push('Required headers: ' + headers.join(', '));
        sampleLines.push('Require HTTPS: yes');
        sampleLines.push('Allowed fact keys: ' + factsAllowlist);
        if (proxyOnlyEnabled) {
            sampleLines.push('Network path: request must arrive through a trusted proxy that sets X-Forwarded-For or X-Real-IP.');
        } else if (allowlistRules) {
            sampleLines.push('Network path: request IP must match the configured client IP allowlist.');
        } else {
            sampleLines.push('Network path: no proxy/IP restriction configured.');
        }
        if (deviceTokensEnabled) {
            sampleLines.push('Device token: client must send the token that matches its serial number.');
        }
        if (hmacEnabled) {
            sampleLines.push('HMAC input: timestamp + "\\n" + nonce + "\\n" + raw_body');
        }
        if (replayEnabled) {
            sampleLines.push('Replay rule: each nonce can be used only once and timestamp skew must be within ' + skewSeconds + ' seconds.');
        }
        sampleLines.push('Recommended client examples:');
        sampleLines.push('- basic: scripts/simplemdm_client_reporter_example.sh');
        sampleLines.push('- hardened: scripts/simplemdm_client_reporter_hardened.py');
        sampleLines.push('- deployment guide: docs/CLIENT_REPORTER_DEPLOYMENT.md');

        $('#client_reporter_sample_request_text').val(sampleLines.join('\n'));
    }

    function renderSupplementalStatus(data) {
        var sources = $.isArray(data && data.detected_sources) ? data.detected_sources : [];
        var $list = $('#supplemental-detected-sources').empty();
        var $toggleList = $('#supplemental-source-toggle-list').empty();
        supplementalDisabledSourceIds = $.isArray(data && data.disabled_source_ids) ? data.disabled_source_ids.slice() : [];

        $('#supplemental-enabled-state').text(data && data.enabled ? 'Enabled' : 'Disabled');
        $('#supplemental-stale-threshold').text(String((data && data.stale_after_minutes) || '-') + ' minutes');
        $('#supplemental-summary-count').text(String((data && data.summary_row_count) || '0'));
        $('#supplemental-last-refresh').text(formatDateOrDash((data && data.last_summary_refresh) || ''));
        $('#supplemental-last-status').text((data && data.last_summary_status) || '-');
        $('#client-reporter-counts').text(
            String((data && data.client_fact_count) || 0) + ' / ' + String((data && data.client_fact_history_count) || 0)
        );
        var counts = (data && data.freshness_counts) ? data.freshness_counts : {};
        $('#supplemental-freshness-summary').text(
            'Fresh: ' + String(counts.fresh || 0) +
            ' / Stale: ' + String(counts.stale || 0) +
            ' / Failed: ' + String(counts.refresh_failed || 0)
        );

        if (!sources.length) {
            $list.append('<div class="simplemdm-runs-empty">No supplemental sources detected.</div>');
            $toggleList.append('<div class="simplemdm-runs-empty">No supported supplemental sources detected.</div>');
            updateCollapsibleSummary('supplemental', 'No sources detected');
            updateCollapsibleSummary('enrichment',
                'Supplemental ' + (data && data.enabled ? 'on' : 'off') +
                ', Client Reporter ' + (data && data.client_reporter_enabled ? 'on' : 'off') +
                ', HMAC ' + ($('#client_reporter_hmac_enabled').is(':checked') ? 'on' : 'off') +
                ', Source Opt-Outs 0'
            );
            return;
        }

        sources.forEach(function(source) {
            var status = source.detected ? 'Detected' : 'Unavailable';
            var reason = formatSupplementalReason(source.reason || '-');
            var sourceEnabled = source.enabled !== false;
            var health = (data && data.source_health && data.source_health[source.source_id]) ? data.source_health[source.source_id] : {};
            $list.append(
                '<div class="simplemdm-runs-card">' +
                    '<div class="simplemdm-runs-summary">' + escapeHtml(source.label || source.source_id || '-') + '</div>' +
                    '<div class="simplemdm-runs-meta">' +
                        '<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Type:</span>' + escapeHtml(source.auto_discovered ? 'Loaded module (auto-discovered)' : 'Built-in mapping') + '</div>' +
                        '<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Table:</span>' + escapeHtml(source.table || '-') + '</div>' +
                        (source.auto_discovered && $.isArray(source.table_candidates) && source.table_candidates.length
                            ? '<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Table Candidates:</span>' + escapeHtml(source.table_candidates.join(', ')) + '</div>'
                            : '') +
                        '<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Using:</span>' + escapeHtml(sourceEnabled ? 'Enabled' : 'Disabled') + '</div>' +
                        '<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Status:</span>' + escapeHtml(status) + '</div>' +
                        '<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Reason:</span>' + escapeHtml(reason) + '</div>' +
                        '<div class="simplemdm-runs-meta-line"><span class="simplemdm-runs-meta-label">Fresh/Stale/Failed:</span>' + escapeHtml(String(health.fresh || 0) + '/' + String(health.stale || 0) + '/' + String(health.refresh_failed || 0)) + '</div>' +
                    '</div>' +
                '</div>'
            );

            if (String(source.source_id || '') === 'client_reporter') {
                return;
            }

            $toggleList.append(
                '<div class="simplemdm-source-toggle-row">' +
                    '<div class="simplemdm-source-toggle-head">' +
                        '<label style="margin:0;">' +
                            '<input type="checkbox" class="simplemdm-source-toggle" data-source-id="' + escapeHtml(source.source_id || '') + '"' + (sourceEnabled ? ' checked' : '') + (source.detected ? '' : ' disabled') + '> ' +
                            '<span class="simplemdm-source-toggle-title">' + escapeHtml(source.label || source.source_id || '-') + '</span>' +
                        '</label>' +
                        '<span class="label label-' + (source.detected ? 'success' : 'default') + '">' + escapeHtml(status) + '</span>' +
                    '</div>' +
                    '<div class="simplemdm-source-toggle-meta">Source ID: ' + escapeHtml(source.source_id || '-') + ' | Table: ' + escapeHtml(source.table || '-') + (source.auto_discovered && $.isArray(source.table_candidates) && source.table_candidates.length ? ' | Candidates: ' + escapeHtml(source.table_candidates.join(', ')) : '') + ' | ' + escapeHtml(source.auto_discovered ? 'Loaded module discovered automatically.' : 'Built-in supplemental mapping.') + ' ' + escapeHtml(source.detected ? 'Available for enrichment.' : 'Not currently available. Enable remains read-only until the source is detected.') + '</div>' +
                '</div>'
            );
        });
        updateCollapsibleSummary('supplemental',
            'Rows: ' + String((data && data.summary_row_count) || 0) +
            ', Client Facts: ' + String((data && data.client_fact_count) || 0) +
            ', Fresh/Stale/Failed: ' + String(counts.fresh || 0) + '/' + String(counts.stale || 0) + '/' + String(counts.refresh_failed || 0)
        );
        updateCollapsibleSummary('enrichment',
            'Supplemental ' + (data && data.enabled ? 'on' : 'off') +
            ', Client Reporter ' + (data && data.client_reporter_enabled ? 'on' : 'off') +
            ', HMAC ' + ($('#client_reporter_hmac_enabled').is(':checked') ? 'on' : 'off') +
            ', Source Opt-Outs ' + String(supplementalDisabledSourceIds.length)
        );
    }

    function loadSupplementalStatus() {
        $.getJSON(appUrl + '/module/simplemdm/get_supplemental_status', function(data) {
            renderSupplementalStatus(data || {});
        }).fail(function(xhr) {
            var message = 'Unable to load supplemental status.';
            if (xhr && xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) {
                message = xhr.responseJSON.message || xhr.responseJSON.error;
            }
            $('#supplemental-detected-sources').html('<div class="simplemdm-runs-empty">' + escapeHtml(message) + '</div>');
        });
    }

    function setScriptOutput(lines) {
        $('#script-runner-output').val(lines);
        var count = $('#script-catalog .simplemdm-script-row').length;
        updateCollapsibleSummary('manual', count + ' script action(s) available');
    }

    function setActionNotice(target, text, cssClass) {
        $(target).text(text).removeClass().addClass(cssClass || 'text-muted');
    }

    function getDisabledSupplementalSourceIdsFromForm() {
        var disabled = [];
        $('.simplemdm-source-toggle').each(function() {
            var sourceId = String($(this).data('source-id') || '').trim();
            if (!sourceId) {
                return;
            }
            if (!$(this).is(':checked')) {
                disabled.push(sourceId);
            }
        });
        return disabled;
    }

    function setFormStatus(target, text, cssClass, autoHideMs) {
        var $el = $(target);
        $el.stop(true, true).show().text(text).removeClass().addClass(cssClass || 'text-muted');
        if (autoHideMs) {
            window.setTimeout(function() {
                $el.fadeOut();
            }, autoHideMs);
        }
    }

    function getRunnerSettingsPayload(extraPayload) {
        var payload = {
            allow_module_script_execution: $('#allow_module_script_execution').is(':checked') ? '1' : '0',
            script_runner_munkireport_url: $('#script_runner_munkireport_url').val() || '',
            script_runner_python_bin: $('#script_runner_python_bin').val() || '/usr/bin/python3',
            script_runner_schedule: $('#script_runner_schedule').val() || '*/15 * * * *',
            sync_interval_minutes: String($('#sync_interval_minutes').val() || '15'),
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
            missing.push('API Key');
        }
        if (requirements.moduleExecution && !state.moduleExecutionEnabled) {
            missing.push('Module Execution');
        }
        if (requirements.runnerUrl && !state.runnerUrlPresent) {
            missing.push('Runner URL');
        }
        if (requirements.python && !state.pythonPresent) {
            missing.push('Configured Python Path');
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
        refreshScriptActionAvailability();
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
        refreshScriptActionAvailability();
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

    function getScriptActionRequirements(action) {
        if (action === 'sync_now') {
            return { apiKey: true, moduleExecution: true, runnerUrl: true, python: true, maxParentResources: true, modulePython: true };
        }
        if (action === 'print_cron') {
            return { apiKey: true, moduleExecution: true, runnerUrl: true, python: true, schedule: true, logPath: true, maxParentResources: true };
        }
        if (action === 'install_cron') {
            return { apiKey: true, moduleExecution: true, runnerUrl: true, python: true, schedule: true, logPath: true, maxParentResources: true, modulePython: true };
        }
        if (action === 'remove_cron') {
            return { moduleExecution: true };
        }
        return { moduleExecution: true };
    }

    function getScriptActionMissing(action) {
        var requirements = getScriptActionRequirements(action);
        var missing = getMissingFields(requirements);
        if (requirements.modulePython && (!runnerStatusCache || !runnerStatusCache.runtime || !runnerStatusCache.runtime.python_available)) {
            missing.push('Module Python');
        }
        return missing;
    }

    function formatMissingList(items) {
        if (!items.length) {
            return '';
        }
        if (items.length === 1) {
            return items[0];
        }
        if (items.length === 2) {
            return items[0] + ' and ' + items[1];
        }
        return items.slice(0, -1).join(', ') + ', and ' + items[items.length - 1];
    }

    function refreshScriptActionAvailability() {
        $('.simplemdm-run-script').each(function() {
            var action = String($(this).data('action') || '');
            var missing = getScriptActionMissing(action);
            var disabledReason = missing.length ? 'Run In Module unavailable: missing ' + formatMissingList(missing) + '.' : 'Run this action inside the MunkiReport module runtime.';
            $(this)
                .prop('disabled', missing.length > 0)
                .attr('title', disabledReason)
                .attr('aria-label', disabledReason);
            $(this).closest('.simplemdm-run-script-wrap').attr('title', disabledReason);
            $(this).closest('.simplemdm-script-actions').siblings('.simplemdm-script-inline-status').text(
                missing.length ? disabledReason : 'In-module execution is available for this action.'
            );
        });
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
            html += '<span class="simplemdm-run-script-wrap" title="Checking in-module requirements...">';
            html += '<button type="button" class="btn btn-primary btn-sm simplemdm-run-script" data-action="' + $('<div>').text(script.run_action || '').html() + '" title="Checking in-module requirements..."><i class="fa fa-play"></i> Run In Module</button>';
            html += '</span>';
            html += '</div>';
            html += '<p class="simplemdm-script-inline-status">Checking in-module requirements...</p>';
            html += '<div class="simplemdm-script-command-wrap">';
            html += '<span class="simplemdm-script-command-label">External Command</span>';
            html += '<pre class="simplemdm-script-command">' + $('<div>').text(script.external_command || '').html() + '</pre>';
            html += '</div>';
            html += '</div>';
        });

        $('#script-catalog').html(html);
        refreshScriptActionAvailability();
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

    function runScriptAction(action, options) {
        options = options || {};
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
            if (typeof options.onSuccess === 'function') {
                options.onSuccess(data);
            }
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
            if (typeof options.onError === 'function') {
                options.onError(xhr, payload);
            }
        });
    }

    function loadConfig() {
        $.getJSON(appUrl + '/module/simplemdm/get_config', function(data) {
            renderConfig(data);
            loadSupplementalStatus();
        });
    }

    function refreshAllState() {
        $.getJSON(appUrl + '/module/simplemdm/get_config', function(data) {
            renderConfig(data);
            loadSupplementalStatus();
            if (String(data.sync_request_state || 'idle') === 'running' || String(data.sync_request_state || 'idle') === 'queued') {
                scheduleBackgroundRefresh(BACKGROUND_ACTIVE_REFRESH_MS);
            } else {
                scheduleBackgroundRefresh(BACKGROUND_IDLE_REFRESH_MS);
            }
        });
    }

    function scheduleBackgroundRefresh(delayMs) {
        if (backgroundRefreshTimer) {
            window.clearTimeout(backgroundRefreshTimer);
            backgroundRefreshTimer = null;
        }
        backgroundRefreshTimer = window.setTimeout(function() {
            refreshAllState();
        }, delayMs || BACKGROUND_IDLE_REFRESH_MS);
    }

    function stopSyncPolling() {
        if (syncPollTimer) {
            window.clearTimeout(syncPollTimer);
            syncPollTimer = null;
        }
    }

    function pollSyncUntilSettled(options) {
        options = options || {};
        stopSyncPolling();

        var startedAt = Date.now();
        var initialLastSyncTime = String(options.initialLastSyncTime || '').trim();
        var mode = String(options.mode || 'completion');

        function tick() {
            $.getJSON(appUrl + '/module/simplemdm/get_config', function(data) {
                renderConfig(data);

                var state = String(data.sync_request_state || 'idle');
                var currentLastSyncTime = String(data.last_completed_sync_time || data.last_sync_time || '').trim();
                var currentLastSyncStatus = String(data.last_completed_sync_status || data.last_sync_status || '').trim();
                var elapsed = Date.now() - startedAt;

                if (mode === 'queue') {
                    if (state === 'queued') {
                        setSyncMessage('Sync request queued. Waiting for cron/manual worker pickup...', 'text-info');
                    } else if (state === 'running') {
                        setSyncMessage('Queued sync was picked up and is now running.', 'text-info');
                    } else if (currentLastSyncTime && currentLastSyncTime !== initialLastSyncTime) {
                        setSyncMessage('Queued sync completed with status: ' + (currentLastSyncStatus || 'Unknown') + '.', currentLastSyncStatus.toLowerCase() === 'success' ? 'text-success' : 'text-danger');
                        $('#simplemdm-sync-now').prop('disabled', false);
                        scheduleBackgroundRefresh(BACKGROUND_IDLE_REFRESH_MS);
                        stopSyncPolling();
                        return;
                    } else if (elapsed >= SYNC_POLL_TIMEOUT_MS) {
                        setSyncMessage('Queued sync did not complete within 3 minutes. Check cron/manual runner status.', 'text-warning');
                        $('#simplemdm-sync-now').prop('disabled', false);
                        scheduleBackgroundRefresh(BACKGROUND_IDLE_REFRESH_MS);
                        stopSyncPolling();
                        return;
                    }
                } else {
                    if (state === 'running') {
                        setSyncMessage('Immediate sync is still running...', 'text-info');
                    } else if (currentLastSyncTime && currentLastSyncTime !== initialLastSyncTime) {
                        if (typeof options.onComplete === 'function') {
                            options.onComplete(data);
                        }
                        scheduleBackgroundRefresh(BACKGROUND_IDLE_REFRESH_MS);
                        stopSyncPolling();
                        return;
                    } else if (elapsed >= SYNC_POLL_TIMEOUT_MS) {
                        if (typeof options.onTimeout === 'function') {
                            options.onTimeout(data);
                        }
                        scheduleBackgroundRefresh(BACKGROUND_IDLE_REFRESH_MS);
                        stopSyncPolling();
                        return;
                    }
                }

                syncPollTimer = window.setTimeout(tick, SYNC_POLL_INTERVAL_MS);
            }).fail(function() {
                if (Date.now() - startedAt >= SYNC_POLL_TIMEOUT_MS) {
                    if (mode === 'queue') {
                        setSyncMessage('Unable to confirm queued sync completion. Refresh the page or check sync status again.', 'text-warning');
                        $('#simplemdm-sync-now').prop('disabled', false);
                    } else if (typeof options.onTimeout === 'function') {
                        options.onTimeout({});
                    }
                    scheduleBackgroundRefresh(BACKGROUND_IDLE_REFRESH_MS);
                    stopSyncPolling();
                    return;
                }
                syncPollTimer = window.setTimeout(tick, SYNC_POLL_INTERVAL_MS);
            });
        }

        tick();
    }

    function saveScheduleSettings(extraPayload, successMessage, onSuccess) {
        $('#script-runner-save-status').text('Saving...').removeClass().addClass('text-info');
        var payload = getRunnerSettingsPayload(extraPayload);

        $.post(appUrl + '/module/simplemdm/save_config', payload, function(data) {
            if (data.status === 'success') {
                $('#script-runner-save-status').text(successMessage || 'Saved successfully!').removeClass().addClass('text-success');
                if (typeof onSuccess === 'function') {
                    onSuccess(data);
                }
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
        var previousLastSyncTime = String($('#sync-time').text() || '').trim();
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

            $('#script-runner-save-status').text('Running immediate sync inside the module...').removeClass().addClass('text-info');
            setSyncMessage('Running sync now inside the module.', 'text-info');
            setScriptOutput('Immediate sync requested.\n\nPrerequisites validated:\n- API key present\n- In-module execution enabled\n- Runner URL set\n- Python binary set\n\nStarting simplemdm_sync.py ...');
            runScriptAction('sync_now', {
                onSuccess: function(result) {
                    refreshAllState();
                    loadRunnerStatus();

                    var exitCode = parseInt(result && result.exit_code, 10);
                    var completed = result && result.status === 'success' && !isNaN(exitCode) && exitCode === 0;

                    if (completed) {
                        $('#script-runner-save-status').text('Immediate sync finished. Confirming completion status...').removeClass().addClass('text-info');
                        setSyncMessage('Immediate sync finished. Confirming completion status...', 'text-info');
                        pollSyncUntilSettled({
                            initialLastSyncTime: previousLastSyncTime,
                            mode: 'completion',
                            onComplete: function(data) {
                                var latestLastSyncTime = String(data.last_completed_sync_time || data.last_sync_time || '').trim();
                                var latestLastSyncStatus = String(data.last_completed_sync_status || data.last_sync_status || '').trim();
                                var success = latestLastSyncStatus.toLowerCase() === 'success';
                                $('#script-runner-save-status').text(success ? 'Immediate sync completed successfully.' : 'Immediate sync finished with status: ' + (latestLastSyncStatus || 'Unknown') + '.').removeClass().addClass(success ? 'text-success' : 'text-danger');
                                if (latestLastSyncTime && latestLastSyncTime !== previousLastSyncTime) {
                                    setSyncMessage((success ? 'Immediate sync completed successfully.' : 'Immediate sync completed with status: ' + (latestLastSyncStatus || 'Unknown') + '.') + ' Last completed time updated.', success ? 'text-success' : 'text-danger');
                                } else {
                                    setSyncMessage(success ? 'Immediate sync completed successfully.' : 'Immediate sync completed with status: ' + (latestLastSyncStatus || 'Unknown') + '.', success ? 'text-success' : 'text-danger');
                                }
                                $button.prop('disabled', false);
                            },
                            onTimeout: function() {
                                $('#script-runner-save-status').text('Immediate sync finished, but the UI could not confirm completion within 3 minutes. Refresh to confirm status.').removeClass().addClass('text-warning');
                                setSyncMessage('Immediate sync was triggered, but the UI could not confirm completion within 3 minutes. Refresh to confirm status.', 'text-warning');
                                $button.prop('disabled', false);
                            }
                        });
                    } else {
                        $('#script-runner-save-status').text('Immediate sync finished with errors. Review script output below.').removeClass().addClass('text-danger');
                        setSyncMessage('Immediate sync finished with errors. Review script output below.', 'text-danger');
                        refreshAllState();
                        $button.prop('disabled', false);
                    }
                },
                onError: function(xhr, payload) {
                    var message = (payload && (payload.message || payload.error)) ? (payload.message || payload.error) : 'Immediate sync failed.';
                    $('#script-runner-save-status').text('Error: ' + message).removeClass().addClass('text-danger');
                    setSyncMessage('Immediate sync failed. Review script output below.', 'text-danger');
                    refreshAllState();
                    loadRunnerStatus();
                    $button.prop('disabled', false);
                }
            });
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
    initCollapsibles();
    refreshAllState();
    loadScriptCatalog();
    loadRunnerStatus();
    loadSupplementalStatus();
    scheduleBackgroundRefresh(BACKGROUND_IDLE_REFRESH_MS);

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
                refreshAllState();
                setSyncMessage('Sync request queued. Waiting for cron/manual worker pickup...', 'text-info');
                pollSyncUntilSettled({
                    initialLastSyncTime: String($('#sync-time').text() || '').trim(),
                    mode: 'queue'
                });
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

    $('#clear-sync-runs-btn').on('click', function() {
        var $btn = $(this);
        if (!confirmAction('Clear the recorded sync run history and reset the last completed sync cards?')) {
            return;
        }
        $btn.prop('disabled', true);
        $.post(appUrl + '/module/simplemdm/clear_sync_runs', {}, function(data) {
            if (data.status === 'success') {
                setSyncMessage('Run history cleared.', 'text-success');
                refreshAllState();
            } else {
                setSyncMessage('Error: ' + (data.message || 'Unable to clear run history.'), 'text-danger');
            }
            $btn.prop('disabled', false);
        }, 'json').fail(function(xhr) {
            var msg = 'Unable to clear run history.';
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
                setFormStatus('#save-status', 'Saved successfully!', 'text-success', 3000);
                loadConfig();
                loadScriptCatalog();
                loadRunnerStatus();
            } else {
                setFormStatus('#save-status', 'Error: ' + (data.message || 'Unknown'), 'text-danger');
            }
        }, 'json').fail(function(xhr) {
            var msg = 'Request failed';
            if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            }
            setFormStatus('#save-status', 'Error: ' + msg, 'text-danger');
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
                setFormStatus('#widget-save-status', 'Saved successfully!', 'text-success', 3000);
            } else {
                setFormStatus('#widget-save-status', 'Error: ' + (data.message || 'Unknown'), 'text-danger');
            }
        }, 'json').fail(function(xhr) {
            var msg = 'Request failed';
            if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            }
            setFormStatus('#widget-save-status', 'Error: ' + msg, 'text-danger');
        });
    });

    $('#simplemdm-mcpfindings-form').on('submit', function(e) {
        e.preventDefault();
        $('#mcpfindings-save-status').text('Saving...').removeClass().addClass('text-info');

        var payload = {
            mcp_findings_enabled: $('#mcp_findings_enabled').is(':checked') ? '1' : '0',
            mcp_findings_metadata_max_bytes: String($('#mcp_findings_metadata_max_bytes').val() || '65536'),
            mcp_findings_auto_resolve: $('#mcp_findings_auto_resolve').is(':checked') ? '1' : '0',
            mcp_findings_event_enabled: $('#mcp_findings_event_enabled').is(':checked') ? '1' : '0',
            mcp_findings_event_warning_threshold: String($('#mcp_findings_event_warning_threshold').val() || '1')
        };

        $.post(appUrl + '/module/simplemdm/save_config', payload, function(data) {
            if (data.status === 'success') {
                setFormStatus('#mcpfindings-save-status', 'Saved successfully!', 'text-success', 3000);
            } else {
                setFormStatus('#mcpfindings-save-status', 'Error: ' + (data.message || 'Unknown'), 'text-danger');
            }
        }, 'json').fail(function(xhr) {
            var msg = 'Request failed';
            if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            }
            setFormStatus('#mcpfindings-save-status', 'Error: ' + msg, 'text-danger');
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
            sync_device_subresources_enabled: $('#sync_device_subresources_enabled').is(':checked') ? '1' : '0',
            device_subresource_limit: String($('#device_subresource_limit').val() || '0')
        };

        $.post(appUrl + '/module/simplemdm/save_config', payload, function(data) {
            if (data.status === 'success') {
                setFormStatus('#advanced-save-status', 'Saved successfully!', 'text-success', 3000);
                loadConfig();
                loadScriptCatalog();
                loadRunnerStatus();
            } else {
                setFormStatus('#advanced-save-status', 'Error: ' + (data.message || 'Unknown'), 'text-danger');
            }
        }, 'json').fail(function(xhr) {
            var msg = 'Request failed';
            if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            }
            setFormStatus('#advanced-save-status', 'Error: ' + msg, 'text-danger');
        });
    });

    $('#simplemdm-enrichment-form').on('submit', function(e) {
        e.preventDefault();
        $('#enrichment-save-status').text('Saving...').removeClass().addClass('text-info');

        var payload = {
            supplemental_enabled: $('#supplemental_enabled').is(':checked') ? '1' : '0',
            supplemental_disabled_sources_json: JSON.stringify(getDisabledSupplementalSourceIdsFromForm()),
            supplemental_default_stale_after_minutes: String($('#supplemental_default_stale_after_minutes').val() || '1440'),
            supplemental_registry_json: $('#supplemental_registry_json').val() || '',
            client_reporter_enabled: $('#client_reporter_enabled').is(':checked') ? '1' : '0',
            client_reporter_history_enabled: $('#client_reporter_history_enabled').is(':checked') ? '1' : '0',
            client_reporter_secret: $('#client_reporter_secret').val() || '',
            client_reporter_max_payload_bytes: String($('#client_reporter_max_payload_bytes').val() || '16384'),
            client_reporter_allowed_fact_keys_json: $('#client_reporter_allowed_fact_keys_json').val() || '',
            client_reporter_hmac_enabled: $('#client_reporter_hmac_enabled').is(':checked') ? '1' : '0',
            client_reporter_replay_protection_enabled: $('#client_reporter_replay_protection_enabled').is(':checked') ? '1' : '0',
            client_reporter_per_device_tokens_enabled: $('#client_reporter_per_device_tokens_enabled').is(':checked') ? '1' : '0',
            client_reporter_proxy_only_enabled: $('#client_reporter_proxy_only_enabled').is(':checked') ? '1' : '0',
            client_reporter_max_time_skew_seconds: String($('#client_reporter_max_time_skew_seconds').val() || '300'),
            client_reporter_ip_allowlist: $('#client_reporter_ip_allowlist').val() || '',
            client_reporter_trusted_proxy_ips: $('#client_reporter_trusted_proxy_ips').val() || '',
            client_reporter_device_tokens_json: $('#client_reporter_device_tokens_json').val() || ''
        };

        $.post(appUrl + '/module/simplemdm/save_config', payload, function(data) {
            if (data.status === 'success') {
                $('#enrichment-save-status').text('Saved. Refreshing supplemental summary cache...').removeClass().addClass('text-info');
                $.post(appUrl + '/module/simplemdm/refresh_supplemental_summary', {}, function(refreshData) {
                    if (refreshData.status === 'success') {
                        setFormStatus('#enrichment-save-status', 'Saved and refreshed supplemental summary cache.', 'text-success', 3500);
                    } else {
                        setFormStatus('#enrichment-save-status', 'Saved settings, but summary refresh failed: ' + (refreshData.message || 'Unknown'), 'text-warning', 5000);
                    }
                    loadConfig();
                    loadSupplementalStatus();
                }, 'json').fail(function(xhr) {
                    var refreshMsg = 'Unable to refresh supplemental summary.';
                    if (xhr && xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) {
                        refreshMsg = xhr.responseJSON.message || xhr.responseJSON.error;
                    }
                    setFormStatus('#enrichment-save-status', 'Saved settings, but summary refresh failed: ' + refreshMsg, 'text-warning', 5000);
                    loadConfig();
                    loadSupplementalStatus();
                });
            } else {
                setFormStatus('#enrichment-save-status', 'Error: ' + (data.message || 'Unknown'), 'text-danger');
            }
        }, 'json').fail(function(xhr) {
            var msg = 'Request failed';
            if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            }
            setFormStatus('#enrichment-save-status', 'Error: ' + msg, 'text-danger');
        });
    });

    $('#simplemdm-event-form').on('submit', function(e) {
        e.preventDefault();
        keepEventPanelOpen();
        $('#event-save-status').text('Saving...').removeClass().addClass('text-info');

        var payload = {
            event_stale_threshold_hours: String($('#event_stale_threshold_hours').val() || '168'),
            event_builtin_settings_json: JSON.stringify(collectBuiltInEventSettings()),
            custom_event_rules_json: JSON.stringify(collectCustomEventRules())
        };

        $.post(appUrl + '/module/simplemdm/save_config', payload, function(data) {
            if (data.status === 'success') {
                markEventFormDirty(false);
                setFormStatus('#event-save-status', 'Saved successfully!', 'text-success', 3000);
                setFormStatus('#event-builder-status', 'Custom event settings saved.', 'text-success', 2500);
                loadConfig();
            } else {
                setFormStatus('#event-save-status', 'Error: ' + (data.message || 'Unknown'), 'text-danger');
            }
        }, 'json').fail(function(xhr) {
            var msg = 'Request failed';
            if (xhr && xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) {
                msg = xhr.responseJSON.message || xhr.responseJSON.error;
            }
            setFormStatus('#event-save-status', 'Error: ' + msg, 'text-danger');
        });
    });

    $(document).on('click mousedown', '[data-collapsible-body="events"] button, [data-collapsible-body="events"] a, [data-collapsible-body="events"] input, [data-collapsible-body="events"] label, [data-collapsible-body="events"] textarea, [data-collapsible-body="events"] select', function(e) {
        e.stopPropagation();
        keepEventPanelOpen();
    });

    $('#add-custom-event-rule').on('click', function() {
        var $list = $('#custom-event-rule-list');
        var $row;
        keepEventPanelOpen();
        markEventFormDirty(true);
        $list.find('.simplemdm-runs-empty').remove();
        $row = createCustomEventRuleRow({
            source_field: 'status',
            trigger_type: 'changed_to',
            severity: 'warning',
            message: 'SimpleMDM: custom event triggered'
        });
        $list.append($row);
        setFormStatus('#event-builder-status', 'Custom event row added. Save Event Settings to persist it.', 'text-success', 3500);
        $row.find('.simplemdm-custom-event-suffix').trigger('focus');
        refreshEventSummary();
    });

    $(document).on('change', '.simplemdm-custom-event-source-field', function() {
        var $row = $(this).closest('.simplemdm-custom-event-row');
        keepEventPanelOpen();
        markEventFormDirty(true);
        syncCustomEventRuleInputs($row);
    });

    $(document).on('change', '.simplemdm-custom-event-trigger-type', function() {
        var $row = $(this).closest('.simplemdm-custom-event-row');
        keepEventPanelOpen();
        markEventFormDirty(true);
        syncCustomEventRuleInputs($row);
    });

    $(document).on('input', '.simplemdm-custom-event-suffix, .simplemdm-custom-event-label', function() {
        keepEventPanelOpen();
        markEventFormDirty(true);
        var $row = $(this).closest('.simplemdm-custom-event-row');
        if ($(this).hasClass('simplemdm-custom-event-suffix')) {
            $(this).attr('data-autofill', String($(this).val() || '').trim() === '' ? '1' : '0');
            if (String($(this).val() || '').trim() === '') {
                syncCustomEventRuleInputs($row);
                return;
            }
        }
        updateCustomEventRuleHeader($row);
    });

    $(document).on('click', '.simplemdm-remove-custom-event', function() {
        var $list = $('#custom-event-rule-list');
        keepEventPanelOpen();
        markEventFormDirty(true);
        $(this).closest('.simplemdm-custom-event-row').remove();
        if (!$list.find('.simplemdm-custom-event-row').length) {
            $list.html('<div class="simplemdm-runs-empty">No custom event rules configured.</div>');
        }
        refreshEventSummary();
    });

    $(document).on('input change', '.simplemdm-event-toggle-checkbox, .simplemdm-custom-event-enabled, .simplemdm-custom-event-severity, .simplemdm-custom-event-message, .simplemdm-custom-event-target-value, .simplemdm-custom-event-threshold-hours, #event_stale_threshold_hours', function() {
        markEventFormDirty(true);
        refreshEventSummary();
    });

    $('#simplemdm-script-runner-form').on('submit', function(e) {
        e.preventDefault();
        saveScheduleSettings({}, 'Saved successfully!');
    });

    $('#refresh-supplemental-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#supplemental-refresh-status').text('Refreshing supplemental summary...').removeClass().addClass('text-info');
        $.post(appUrl + '/module/simplemdm/refresh_supplemental_summary', {}, function(data) {
            if (data.status === 'success') {
                $('#supplemental-refresh-status').text('Refreshed ' + String(data.refreshed || 0) + ' device summary rows.').removeClass().addClass('text-success');
                loadSupplementalStatus();
            } else {
                $('#supplemental-refresh-status').text('Error: ' + (data.message || 'Unknown')).removeClass().addClass('text-danger');
            }
            $btn.prop('disabled', false);
        }, 'json').fail(function(xhr) {
            var message = 'Unable to refresh supplemental summary.';
            if (xhr && xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) {
                message = xhr.responseJSON.message || xhr.responseJSON.error;
            }
            $('#supplemental-refresh-status').text(message).removeClass().addClass('text-danger');
            $btn.prop('disabled', false);
        });
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
        var action = String($(this).data('action') || '');
        if (!validateActionRequirements(
            getScriptActionRequirements(action),
            {
                prefix: 'In-module action cannot start',
                noticeTarget: '#script-runner-save-status',
                outputMessage: true
            }
        )) {
            return;
        }
        runScriptAction(action, {
            onSuccess: function(result) {
                loadRunnerStatus();
                refreshSyncStatus();
                var exitCode = parseInt(result && result.exit_code, 10);
                var ok = result && result.status === 'success' && !isNaN(exitCode) && exitCode === 0;
                setActionNotice('#script-runner-save-status', ok ? 'Action `' + action + '` completed successfully.' : 'Action `' + action + '` finished with errors. Review script output below.', ok ? 'text-success' : 'text-danger');
            },
            onError: function(xhr, payload) {
                var message = (payload && (payload.message || payload.error)) ? (payload.message || payload.error) : 'Action failed.';
                setActionNotice('#script-runner-save-status', 'Error running `' + action + '`: ' + message, 'text-danger');
                loadRunnerStatus();
                refreshSyncStatus();
            }
        });
    });

    $('#script_runner_schedule_preset').on('change', function() {
        var value = String($(this).val() || '');
        if (value && value !== 'custom') {
            $('#script_runner_schedule').val(value);
        }
        renderPrereqState();
    });

    $('#api_key, #script_runner_munkireport_url, #script_runner_python_bin, #script_runner_schedule, #sync_interval_minutes, #script_runner_log_path, #script_runner_max_parent_resources, #allow_module_script_execution').on('input change', function() {
        renderPrereqState();
        renderRunnerStatusPending('Re-checking module runtime after unsaved changes...');
        window.clearTimeout(runnerStatusRefreshTimer);
        runnerStatusRefreshTimer = window.setTimeout(function() {
            loadRunnerStatus();
        }, 400);
    });

    $(document).on('change', '.simplemdm-source-toggle', function() {
        updateCollapsibleSummary('enrichment',
            'Supplemental ' + ($('#supplemental_enabled').is(':checked') ? 'on' : 'off') +
            ', Client Reporter ' + ($('#client_reporter_enabled').is(':checked') ? 'on' : 'off') +
            ', HMAC ' + ($('#client_reporter_hmac_enabled').is(':checked') ? 'on' : 'off') +
            ', Source Opt-Outs ' + String(getDisabledSupplementalSourceIdsFromForm().length)
        );
    });

    $('#supplemental_enabled, #client_reporter_enabled, #client_reporter_hmac_enabled').on('change', function() {
        updateCollapsibleSummary('enrichment',
            'Supplemental ' + ($('#supplemental_enabled').is(':checked') ? 'on' : 'off') +
            ', Client Reporter ' + ($('#client_reporter_enabled').is(':checked') ? 'on' : 'off') +
            ', HMAC ' + ($('#client_reporter_hmac_enabled').is(':checked') ? 'on' : 'off') +
            ', Source Opt-Outs ' + String(getDisabledSupplementalSourceIdsFromForm().length)
        );
    });

    $('#client_reporter_enabled, #client_reporter_hmac_enabled, #client_reporter_replay_protection_enabled, #client_reporter_per_device_tokens_enabled, #client_reporter_proxy_only_enabled, #client_reporter_max_time_skew_seconds, #client_reporter_ip_allowlist, #client_reporter_trusted_proxy_ips, #client_reporter_allowed_fact_keys_json').on('input change', function() {
        renderClientReporterRequirements({
            client_reporter_enabled: $('#client_reporter_enabled').is(':checked') ? '1' : '0',
            client_reporter_hmac_enabled: $('#client_reporter_hmac_enabled').is(':checked') ? '1' : '0',
            client_reporter_replay_protection_enabled: $('#client_reporter_replay_protection_enabled').is(':checked') ? '1' : '0',
            client_reporter_per_device_tokens_enabled: $('#client_reporter_per_device_tokens_enabled').is(':checked') ? '1' : '0',
            client_reporter_proxy_only_enabled: $('#client_reporter_proxy_only_enabled').is(':checked') ? '1' : '0',
            client_reporter_max_time_skew_seconds: $('#client_reporter_max_time_skew_seconds').val() || '300',
            client_reporter_ip_allowlist: $('#client_reporter_ip_allowlist').val() || '',
            client_reporter_trusted_proxy_ips: $('#client_reporter_trusted_proxy_ips').val() || '',
            client_reporter_allowed_fact_keys_json: $('#client_reporter_allowed_fact_keys_json').val() || '[]',
            client_reporter_device_token_metadata_json: $('#client_reporter_device_token_metadata_json').val() || '[]'
        });
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
        saveScheduleSettings({ enable_scheduled_sync: '1' }, 'Schedule enabled.', function() {
            if (canRunInModule) {
                setScriptOutput('Installing cron for scheduled sync using the saved runner settings...');
                runScriptAction('install_cron', {
                    onSuccess: function(result) {
                        loadRunnerStatus();
                        refreshSyncStatus();
                        var exitCode = parseInt(result && result.exit_code, 10);
                        var ok = result && result.status === 'success' && !isNaN(exitCode) && exitCode === 0;
                        setActionNotice('#script-runner-save-status', ok ? 'Scheduled sync enabled and cron installed successfully.' : 'Scheduled sync was enabled, but cron installation reported errors. Review script output below.', ok ? 'text-success' : 'text-danger');
                    },
                    onError: function(xhr, payload) {
                        var message = (payload && (payload.message || payload.error)) ? (payload.message || payload.error) : 'Cron install failed.';
                        setActionNotice('#script-runner-save-status', 'Scheduled sync was enabled, but cron installation failed: ' + message, 'text-danger');
                        loadRunnerStatus();
                        refreshSyncStatus();
                    }
                });
            } else {
                loadRunnerStatus();
                refreshSyncStatus();
                setScriptOutput('Schedule enabled in config. In-module execution is disabled, so cron was not installed automatically. Use the Manual Access section to run the install command outside the module.');
                setActionNotice('#script-runner-save-status', 'Scheduled sync enabled in config. Manual cron install is still required outside the module.', 'text-warning');
            }
        });
    });

    $('#disable-schedule-btn').on('click', function() {
        var canRunInModule = $('#allow_module_script_execution').is(':checked');
        if (!confirmAction('Disable scheduled sync? This stops future cron-based sync runs managed by this module.')) {
            setActionNotice('#script-runner-save-status', 'Disable scheduled sync cancelled.', 'text-muted');
            return;
        }
        saveScheduleSettings({ enable_scheduled_sync: '0' }, 'Schedule disabled.', function() {
            if (canRunInModule) {
                setScriptOutput('Removing cron for scheduled sync...');
                runScriptAction('remove_cron', {
                    onSuccess: function(result) {
                        loadRunnerStatus();
                        refreshSyncStatus();
                        var exitCode = parseInt(result && result.exit_code, 10);
                        var ok = result && result.status === 'success' && !isNaN(exitCode) && exitCode === 0;
                        setActionNotice('#script-runner-save-status', ok ? 'Scheduled sync disabled and cron removed successfully.' : 'Scheduled sync was disabled, but cron removal reported errors. Review script output below.', ok ? 'text-success' : 'text-danger');
                    },
                    onError: function(xhr, payload) {
                        var message = (payload && (payload.message || payload.error)) ? (payload.message || payload.error) : 'Cron removal failed.';
                        setActionNotice('#script-runner-save-status', 'Scheduled sync was disabled, but cron removal failed: ' + message, 'text-danger');
                        loadRunnerStatus();
                        refreshSyncStatus();
                    }
                });
            } else {
                loadRunnerStatus();
                refreshSyncStatus();
                setScriptOutput('Schedule disabled in config. In-module execution is disabled, so cron was not removed automatically. Use the Manual Access section if you need to remove the host cron job manually.');
                setActionNotice('#script-runner-save-status', 'Scheduled sync disabled in config. Remove the host cron job manually outside the module if needed.', 'text-warning');
            }
        });
    });
});
</script>

<?php $this->view('partials/foot'); ?>

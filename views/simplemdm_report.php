<?php
$this->view('partials/head', ['breakpoints' => [
    'main' => [8, 12, 12, 12],
    'side' => [4, 12, 12, 12],
]]);
include_once __DIR__ . '/simplemdm_widget_modern_assets.php';

$widgets = [];
$provides_path = APP_ROOT . 'local/modules/simplemdm/provides.yml';
if (is_readable($provides_path)) {
    try {
        $provides = \Symfony\Component\Yaml\Yaml::parseFile($provides_path);
        if (isset($provides['widgets']) && is_array($provides['widgets'])) {
            foreach ($provides['widgets'] as $widget_id => $info) {
                if (! isset($info['view'])) {
                    continue;
                }
                $widgets[] = [
                    'id' => (string)$widget_id,
                    'view' => (string)$info['view'],
                ];
            }
        }
    } catch (\Throwable $e) {
        $widgets = [];
    }
}
?>

<style>
.simplemdm-report-header {
    margin-top: 10px;
    margin-bottom: 14px;
}
.simplemdm-report-header h1 {
    margin: 0 0 6px;
    color: var(--simplemdm-ink);
    font-weight: 800;
}
.simplemdm-report-header .lead {
    color: var(--simplemdm-muted);
    margin-bottom: 0;
}
</style>

<div class="container">
    <div class="row">
        <div class="col-lg-12 simplemdm-report-header">
            <h1><i class="fa fa-cloud"></i> SimpleMDM Report</h1>
            <p class="lead">Overview of devices managed by SimpleMDM, including enrollment/security posture, command status, sync telemetry, groups, listings, and API resource widgets.</p>
        </div>
    </div>

    <div class="row" id="simplemdm-report-grid">
        <?php foreach ($widgets as $widget): ?>
            <div id="simplemdm-widget-<?= htmlspecialchars($widget['id'], ENT_QUOTES, 'UTF-8') ?>">
                <?php $this->view($widget['view']); ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    $.getJSON(appUrl + '/module/simplemdm/get_config', function(cfg) {
        function enabled(key) {
            return !(cfg[key] !== undefined && String(cfg[key]) === '0');
        }

        $('[id^="simplemdm-widget-"]').each(function() {
            var widgetId = this.id.replace('simplemdm-widget-', '');
            var configKey = 'widget_' + widgetId;
            if (!enabled(configKey)) {
                $(this).hide().addClass('simplemdm-widget-hidden');
            }
        });
    });
});
</script>

<?php $this->view('partials/foot'); ?>

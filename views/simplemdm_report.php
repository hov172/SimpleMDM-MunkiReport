<?php
$this->view('partials/head', ['breakpoints' => [
    'main' => [8, 12, 12, 12],
    'side' => [4, 12, 12, 12],
]]);
include_once __DIR__ . '/simplemdm_widget_modern_assets.php';

$widgets = [];
$widget_load_error = '';
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
        $widget_load_error = (string)$e->getMessage();
    }
}

// Fail-safe: avoid a completely blank report if YAML parsing fails at runtime.
if (! $widgets) {
    $fallback_views = [
        'simplemdm_enrollment_widget',
        'simplemdm_dep_widget',
        'simplemdm_filevault_widget',
        'simplemdm_supervised_widget',
        'simplemdm_group_widget',
        'simplemdm_resource_types_widget',
        'simplemdm_trend_widget',
        'simplemdm_os_security_widget',
        'simplemdm_group_top_widget',
        'simplemdm_resource_mix_widget',
        'simplemdm_compliance_widget',
        'simplemdm_sync_health_widget',
        'simplemdm_devices_table_widget',
        'simplemdm_resources_listing_widget',
    ];
    foreach ($fallback_views as $view_name) {
        $widgets[] = [
            'id' => preg_replace('/_widget$/', '', (string)$view_name),
            'view' => (string)$view_name,
        ];
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
            <div id="simplemdm-report-info" class="alert alert-warning" style="display:none;margin-top:10px;"></div>
            <?php if ($widget_load_error !== ''): ?>
                <div class="alert alert-warning" style="margin-top:10px;">
                    Widget configuration parse failed; using fallback widget set. Details: <?= htmlspecialchars($widget_load_error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row" id="simplemdm-report-grid">
        <?php foreach ($widgets as $widget): ?>
            <div id="simplemdm-widget-<?= htmlspecialchars($widget['id'], ENT_QUOTES, 'UTF-8') ?>">
                <?php
                try {
                    $this->view($widget['view'], [], APP_ROOT . 'local/modules/simplemdm/views/');
                } catch (\Throwable $e) {
                    ?>
                    <div class="alert alert-danger" style="margin-bottom:12px;">
                        Failed to render widget <code><?= htmlspecialchars((string)$widget['id'], ENT_QUOTES, 'UTF-8') ?></code>
                        (view: <code><?= htmlspecialchars((string)$widget['view'], ENT_QUOTES, 'UTF-8') ?></code>):
                        <?= htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php
                }
                ?>
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

        var total = $('[id^="simplemdm-widget-"]').length;
        var visible = $('[id^="simplemdm-widget-"]:visible').length;
        if (total === 0) {
            $('#simplemdm-report-info').text('No SimpleMDM widgets are registered for this report.').show();
        } else if (visible === 0) {
            $('#simplemdm-report-info').text('All SimpleMDM report widgets are currently hidden. Enable them in Admin > SimpleMDM Settings > Widget Visibility.').show();
        } else {
            $('#simplemdm-report-info').hide();
        }
    }).fail(function() {
        var total = $('[id^="simplemdm-widget-"]').length;
        if (total === 0) {
            $('#simplemdm-report-info').text('Widgets failed to load due to configuration or permission issue.').show();
        } else {
            $('#simplemdm-report-info').text('Widget visibility settings could not be loaded; showing default widget set.').show();
        }
    });
});
</script>

<?php $this->view('partials/foot'); ?>

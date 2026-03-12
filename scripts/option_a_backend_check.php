#!/usr/bin/env php
<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this script from the command line.\n");
    exit(1);
}

$appRoot = rtrim((string) getenv('APP_ROOT_OVERRIDE'), '/');
if ($appRoot === '') {
    $appRoot = '/var/munkireport';
}
$appRoot .= '/';

$serial = isset($argv[1]) ? trim((string) $argv[1]) : '';
if ($serial === '') {
    fwrite(STDERR, "Usage: option_a_backend_check.php SERIAL_NUMBER\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] = '127.0.0.1';

define('APP_ROOT', $appRoot);

require_once APP_ROOT . 'app/helpers/env_helper.php';
require_once APP_ROOT . 'vendor/autoload.php';
require_once APP_ROOT . 'app/helpers/config_helper.php';

initDotEnv();
initConfig();
configAppendFile(APP_ROOT . 'app/config/app.php');
configAppendFile(APP_ROOT . 'app/config/db.php', 'connection');
configAppendFile(APP_ROOT . 'app/config/dashboard.php', 'dashboard');
configAppendFile(APP_ROOT . 'app/config/widget.php', 'widget');
configAppendFile(APP_ROOT . 'app/config/auth.php');
loadAuthConfig();

require APP_ROOT . 'system/kissmvc.php';
require APP_ROOT . 'app/helpers/site_helper.php';

require_once APP_ROOT . 'local/modules/simplemdm/simplemdm_controller.php';
foreach ([
    'simplemdm_config_model.php',
    'simplemdm_resource_model.php',
    'simplemdm_dashboard_snapshot_model.php',
    'simplemdm_command_model.php',
    'simplemdm_webhook_event_model.php',
    'simplemdm_relationship_edge_model.php',
    'simplemdm_device_history_model.php',
    'simplemdm_sync_run_model.php',
    'simplemdm_supplemental_summary_model.php',
    'simplemdm_client_fact_model.php',
    'simplemdm_client_fact_history_model.php',
    'simplemdm_client_reporter_nonce_model.php',
    'simplemdm_client_reporter_token_model.php',
] as $file) {
    require_once APP_ROOT . 'local/modules/simplemdm/' . $file;
}

$reflection = new ReflectionClass('Simplemdm_controller');
$controller = $reflection->newInstanceWithoutConstructor();

$modulePath = $reflection->getProperty('module_path');
$modulePath->setAccessible(true);
$modulePath->setValue($controller, APP_ROOT . 'local/modules/simplemdm');

$connect = $reflection->getMethod('connectDB');
$connect->setAccessible(true);
$connect->invoke($controller);

$refresh = $reflection->getMethod('refresh_supplemental_summary_for_serial');
$refresh->setAccessible(true);
$refresh->invoke($controller, $serial);

$row = Simplemdm_supplemental_summary_model::where('serial_number', $serial)->first();
if (! $row) {
    echo json_encode(['status' => 'missing', 'serial_number' => $serial], JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(2);
}

echo json_encode([
    'status' => 'success',
    'serial_number' => (string) $row->serial_number,
    'filevault_present' => $row->filevault_present !== null ? (int) $row->filevault_present : null,
    'filevault_enabled' => $row->filevault_enabled !== null ? (int) $row->filevault_enabled : null,
    'findmymac_present' => $row->findmymac_present !== null ? (int) $row->findmymac_present : null,
    'findmymac_enabled' => $row->findmymac_enabled !== null ? (int) $row->findmymac_enabled : null,
    'applecare_present' => $row->applecare_present !== null ? (int) $row->applecare_present : null,
    'applecare_coverage_end' => (string) $row->applecare_coverage_end,
    'applecare_coverage_status' => (string) $row->applecare_coverage_status,
    'profile_present' => $row->profile_present !== null ? (int) $row->profile_present : null,
    'profile_count' => $row->profile_count !== null ? (int) $row->profile_count : null,
    'managedinstalls_present' => $row->managedinstalls_present !== null ? (int) $row->managedinstalls_present : null,
    'managedinstalls_warning_count' => $row->managedinstalls_warning_count !== null ? (int) $row->managedinstalls_warning_count : null,
    'managedinstalls_error_count' => $row->managedinstalls_error_count !== null ? (int) $row->managedinstalls_error_count : null,
    'source_modules_json' => (string) $row->source_modules_json,
    'last_refresh_status' => (string) $row->last_refresh_status,
], JSON_UNESCAPED_SLASHES) . PHP_EOL;

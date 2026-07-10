<?php

// A MunkiReport module is always installed at <host>/local/modules/<name>/,
// which is the module system's own contract -- this relative depth is safe.
require __DIR__ . '/../../../../vendor/autoload.php';

$capsule = new \Illuminate\Database\Capsule\Manager();
$capsule->addConnection([
    'driver'   => 'sqlite',
    'database' => ':memory:',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Run the module's real migrations against the in-memory connection so tests
// exercise the actual, currently-shipping schema -- not a hand-maintained copy.
$migrationFiles = [
    __DIR__ . '/../migrations/2026_07_07_000000_simplemdm_mcp_finding.php',
    __DIR__ . '/../migrations/2026_07_09_000000_simplemdm_mcp_finding_lifecycle.php',
    __DIR__ . '/../migrations/2026_07_09_100000_simplemdm_mcp_finding_category.php',
];
foreach ($migrationFiles as $file) {
    require_once $file;
}
(new SimplemdmMcpFinding())->up();
(new SimplemdmMcpFindingLifecycle())->up();
(new SimplemdmMcpFindingCategory())->up();

require_once __DIR__ . '/../simplemdm_mcp_finding_model.php';

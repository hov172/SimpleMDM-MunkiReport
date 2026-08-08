<?php
/**
 * SEC-003 verification: business-unit / machine-group scoping.
 *
 * Not part of the PHPUnit suite -- it needs a booted MunkiReport and a real
 * database, neither of which tests/bootstrap.php provides. Run it by hand
 * against an instance when you want to confirm the scoping still holds.
 * See docs/TESTING.md section 14 for the exact commands.
 *
 * It enables business units in-process only, inserts its own fixture estate
 * inside a transaction, asserts, and always rolls back. It makes no permanent
 * change to the instance; the accompanying docs show how to confirm that with
 * row counts before and after.
 *
 * Override the app root with MR_APP_ROOT if it is not /var/munkireport/.
 */
define('APP_ROOT', getenv('MR_APP_ROOT') ?: '/var/munkireport/');
define('PUBLIC_ROOT', APP_ROOT . 'public/');

$_SERVER['REQUEST_METHOD'] = 'GET';          // keeps authorized() off the CSRF path
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';

// Boot everything except the final dispatch block.
require '/tmp/boot_nodispatch.php';

use Illuminate\Database\Capsule\Manager as DB;

$pass = 0; $fail = 0;
function check($label, $got, $want) {
    global $pass, $fail;
    $ok = ($got === $want);
    $ok ? $pass++ : $fail++;
    printf("  [%s] %-58s got=%s want=%s\n", $ok ? 'PASS' : 'FAIL', $label,
           var_export($got, true), var_export($want, true));
}

// Serials: two inside the caller's group, one outside, one with no core record.
const IN_A  = 'SEC003-IN-A';
const IN_B  = 'SEC003-IN-B';
const OUT_C = 'SEC003-OUT-C';
const ORPHAN = 'SEC003-ORPHAN';     // in simplemdm, no reportdata row (MCP-only shape)

const GRP_MINE   = 9101;
const GRP_THEIRS = 9102;

// The Eloquent capsule is not registered globally until a controller connects,
// so build the controller first, then open the transaction.
require_once APP_ROOT . 'local/modules/simplemdm/simplemdm_controller.php';
$_SESSION = ['user' => 'boot', 'auth' => 'local', 'role' => 'admin', 'machine_groups' => []];
$c = new Simplemdm_controller();
$bootRef = new ReflectionClass($c);
$connect = $bootRef->getMethod('connectDB');   // protected on Controller
$connect->setAccessible(true);
$connect->invoke($c);

DB::connection()->beginTransaction();

try {
    // ---------- fixtures ----------
    foreach ([[IN_A, GRP_MINE], [IN_B, GRP_MINE], [OUT_C, GRP_THEIRS]] as [$serial, $grp]) {
        DB::table('reportdata')->insert([
            'serial_number' => $serial, 'machine_group' => $grp,
            'remote_ip' => '127.0.0.1', 'reg_timestamp' => time(),
            'timestamp' => time(), 'archive_status' => 0,
        ]);
    }
    foreach ([IN_A, IN_B, OUT_C, ORPHAN] as $i => $serial) {
        DB::table('simplemdm')->insert([
            'serial_number' => $serial, 'simplemdm_id' => 900000 + $i,
            'device_name' => 'harness-' . $serial, 'status' => 'enrolled',
        ]);
        DB::table('simplemdm_mcp_finding')->insert([
            'serial_number' => $serial, 'source' => 'sec003harness',
            'finding_type' => 'probe', 'category' => 'test', 'severity' => 'info',
            'status' => 'open', 'fingerprint' => 'fp-' . $serial,
            'message' => 'harness probe', 'occurrence_count' => 1,
            'first_seen_at' => gmdate('c'), 'last_seen_at' => gmdate('c'),
            'reported_at' => gmdate('c'),
        ]);
    }

    // ---------- simulate a business-unit-restricted, non-admin session ----------
    $GLOBALS['conf']['enable_business_units'] = true;
    $_SESSION = [
        'user'           => 'sec003-tester',
        'auth'           => 'local',
        'role'           => 'user',          // NOT admin
        'machine_groups' => [GRP_MINE],      // only one of the two groups
        'business_unit'  => 1,
    ];
    unset($GLOBALS['machine_groups']);       // clear get_machine_group() memo

    $r = new ReflectionClass($c);
    $call = function ($m, ...$a) use ($c, $r) {
        $mm = $r->getMethod($m); $mm->setAccessible(true);
        return $mm->invoke($c, ...$a);
    };
    $json = function ($m, ...$a) use ($c) {
        ob_start(); $c->$m(...$a); return json_decode(ob_get_clean(), true);
    };

    echo "\n--- preconditions ---\n";
    check('conf(enable_business_units)', conf('enable_business_units', false), true);
    check('caller is not global admin',  (bool) $c->authorized('global'),      false);
    check('machine_scope_enabled()',     $call('machine_scope_enabled'),       true);
    check('get_filtered_groups()',       get_filtered_groups(),                [GRP_MINE]);

    echo "\n--- core helper: authorized_for_serial ---\n";
    check('in-group serial',    authorized_for_serial(IN_A),  true);
    check('out-of-group serial',authorized_for_serial(OUT_C), false);
    check('orphan (no reportdata row)', authorized_for_serial(ORPHAN), false);

    echo "\n--- SEC-003 guard: require_serial_access ---\n";
    ob_start(); $inOk  = $call('require_serial_access', IN_A);  $inBody  = ob_get_clean();
    ob_start(); $outOk = $call('require_serial_access', OUT_C); $outBody = ob_get_clean();
    check('allows in-scope serial',  $inOk,  true);
    check('no body emitted for allow', trim($inBody), '');
    check('denies out-of-scope serial', $outOk, false);
    check('denial body is a 404-shaped Not found',
          json_decode($outBody, true)['message'] ?? null, 'Not found');

    echo "\n--- SEC-003 query scoping ---\n";
    $devices = $call('scoped_devices')->whereIn('simplemdm.serial_number',
                  [IN_A, IN_B, OUT_C, ORPHAN])->pluck('serial_number')->all();
    sort($devices);
    check('scoped_devices() excludes other groups + orphan', $devices, [IN_A, IN_B]);

    $finds = $call('scoped_findings')->where('source', 'sec003harness')
                 ->pluck('serial_number')->all();
    sort($finds);
    check('scoped_findings() excludes other groups + orphan', $finds, [IN_A, IN_B]);

    $sql = $call('machine_group_sql_filter', 'serial_number', 'WHERE');
    check('raw-SQL filter casts group ids to int',
          (bool) preg_match('/machine_group IN \(' . GRP_MINE . '\)/', $sql), true);

    echo "\n--- endpoint level ---\n";
    $mcpAll = $json('get_mcp_findings');
    $seen = array_values(array_unique(array_map(
        fn($f) => $f['serial_number'],
        array_filter($mcpAll['findings'] ?? [], fn($f) => ($f['source'] ?? '') === 'sec003harness'))));
    sort($seen);
    check('get_mcp_findings() list is scoped', $seen, [IN_A, IN_B]);

    $mcpOut = $json('get_mcp_findings', OUT_C);
    check('get_mcp_findings(out-of-scope serial) -> Not found',
          $mcpOut['message'] ?? null, 'Not found');

    echo "\n--- exemptions must still hold ---\n";
    $tr = $r->getProperty('token_read_request'); $tr->setAccessible(true);
    $tr->setValue($c, true);
    check('sync-token caller bypasses scoping', $call('machine_scope_enabled'), false);
    check('sync-token caller allowed out-of-scope serial',
          $call('require_serial_access', OUT_C), true);
    $tr->setValue($c, false);

    $_SESSION['role'] = 'admin';
    check('global admin bypasses scoping', $call('machine_scope_enabled'), false);
    $_SESSION['role'] = 'user';

    $GLOBALS['conf']['enable_business_units'] = false;
    check('scoping off when business units disabled', $call('machine_scope_enabled'), false);
    check('orphan visible again with BUs off', $call('require_serial_access', ORPHAN), true);

} catch (\Throwable $e) {
    echo "\nHARNESS ERROR: " . get_class($e) . ': ' . $e->getMessage()
       . "\n  at " . $e->getFile() . ':' . $e->getLine() . "\n";
    $fail++;
} finally {
    DB::connection()->rollBack();
    echo "\n[fixtures rolled back]\n";
}

printf("\n==== %d passed, %d failed ====\n", $pass, $fail);
exit($fail === 0 ? 0 : 1);

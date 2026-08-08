<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tripwires for business-unit / machine-group scoping.
 *
 * The module queries its own tables directly rather than going through core's
 * listing helpers, so core's machine-group filtering does not apply for free.
 * Without the explicit checks below, any signed-in user could read SimpleMDM
 * inventory, client facts and MCP findings for every device in the install,
 * including by requesting an out-of-scope serial directly.
 *
 * Scoping deliberately only engages when business units are enabled: with them
 * off, core hands every user every machine group, and filtering would instead
 * hide SimpleMDM devices that have no MunkiReport record at all (an MCP-only
 * serial has no reportdata row, so its machine_group is NULL).
 */
final class MachineScopeGuardTest extends TestCase
{
    private static string $src;

    public static function setUpBeforeClass(): void
    {
        self::$src = (string) file_get_contents(
            __DIR__ . '/../../simplemdm_controller.php'
        );
    }

    public function testScopingHelpersExist(): void
    {
        foreach ([
            'private function machine_scope_enabled',
            'private function require_serial_access',
            'private function scope_to_machine_groups',
            'private function machine_group_sql_filter',
        ] as $signature) {
            $this->assertStringContainsString(
                $signature,
                self::$src,
                sprintf('%s() is missing — machine-group scoping has been removed.', $signature)
            );
        }
    }

    public function testScopingIsGatedOnBusinessUnitsAndSkipsGlobalAdmins(): void
    {
        $this->assertStringContainsString(
            "conf('enable_business_units', false)",
            self::$src,
            'machine_scope_enabled() must stay gated on enable_business_units, or MCP-only devices '
            . '(no reportdata row) disappear for every non-admin user.'
        );

        $this->assertStringContainsString(
            "return ! \$this->authorized('global');",
            self::$src,
            'Global admins can access every machine group and must not be scoped.'
        );
    }

    public function testHeadlessTokenCallersAreExempt(): void
    {
        $this->assertStringContainsString(
            'if ($this->token_read_request === true) {',
            self::$src,
            'Sync-token clients have no session and therefore no machine groups; scoping them would '
            . 'break every headless integration.'
        );
    }

    /**
     * @dataProvider serialScopedEndpoints
     */
    public function testSerialScopedEndpointChecksAccess(string $function): void
    {
        $start = strpos(self::$src, 'function ' . $function . '(');
        $this->assertNotFalse($start, sprintf('%s() not found in the controller.', $function));

        // Look only at the opening stretch of the method; the guard belongs
        // before any data access.
        $body = substr(self::$src, $start, 900);

        $this->assertStringContainsString(
            'require_serial_access(',
            $body,
            sprintf(
                '%s() takes a serial number but never calls require_serial_access(). Any signed-in user '
                . 'could read another business unit\'s device by passing its serial.',
                $function
            )
        );
    }

    public static function serialScopedEndpoints(): array
    {
        return [
            'get_simplemdm_data'      => ['get_simplemdm_data'],
            'get_supplemental_data'   => ['get_supplemental_data'],
            'device page'             => ['device'],
            'get_device_resources'    => ['get_device_resources'],
            'get_device_subresources' => ['get_device_subresources'],
            'get_mcp_findings'        => ['get_mcp_findings'],
            'get_events'              => ['get_events'],
        ];
    }

    public function testListAndExportQueriesAreScoped(): void
    {
        foreach ([
            'scope_to_machine_groups($query, \'simplemdm.serial_number\')',
            'scope_to_machine_groups($query, \'serial_number\')',
        ] as $call) {
            $this->assertStringContainsString(
                $call,
                self::$src,
                'A list/export query has lost its machine-group scoping.'
            );
        }

        $this->assertGreaterThanOrEqual(
            2,
            substr_count(self::$src, 'machine_group_sql_filter('),
            'The raw-SQL aggregates (assignment groups, OS security) must both apply the group filter.'
        );
    }

    public function testGroupIdsAreCastToIntBeforeReachingSql(): void
    {
        $this->assertStringContainsString(
            "array_map('intval', \$groups)",
            self::$src,
            'Machine group ids must be cast to int before interpolation into the raw SQL filter.'
        );
    }
}

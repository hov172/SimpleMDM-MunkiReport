<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tripwires for how the module handles its shared secrets.
 *
 * Three regressions are cheap to reintroduce and expensive to notice:
 *
 *  - Putting the SimpleMDM API key back on a command line. Arguments are
 *    visible in `ps` and /proc/<pid>/cmdline to every local account, and an
 *    installed crontab line persists that exposure.
 *  - Reading the action secret from the query string, which copies it into web
 *    server access logs, proxy logs and Referer headers.
 *  - Widening the module download archive so it sweeps up whatever tooling or
 *    scratch state happens to live in the module directory.
 */
final class SecretHandlingGuardTest extends TestCase
{
    private static string $controller;
    private static string $installCron;

    public static function setUpBeforeClass(): void
    {
        self::$controller = (string) file_get_contents(
            __DIR__ . '/../../simplemdm_controller.php'
        );
        self::$installCron = (string) file_get_contents(
            __DIR__ . '/../../scripts/install_cron.sh'
        );
    }

    public function testRunScriptNeverPutsTheApiKeyOnTheCommandLine(): void
    {
        $this->assertStringNotContainsString(
            '--api-key %s',
            self::$controller,
            'run_script() is interpolating the API key into a shell command again. '
            . 'Pass it through run_local_script_command()\'s $env argument instead.'
        );

        $this->assertStringContainsString(
            "'SIMPLEMDM_API_KEY' => \$this->get_stored_api_key()",
            self::$controller,
            'run_script() must hand the key to the child process via the environment.'
        );
    }

    public function testScriptRunnerOutputIsRedacted(): void
    {
        $this->assertStringContainsString(
            "'stdout' => \$this->redact_secrets(",
            self::$controller,
            'run_script() must redact configured secrets out of script stdout before returning it.'
        );
        $this->assertStringContainsString(
            "'stderr' => \$this->redact_secrets(",
            self::$controller,
            'run_script() must redact configured secrets out of script stderr before returning it.'
        );
    }

    public function testCronEntryCarriesNoSecret(): void
    {
        $this->assertStringNotContainsString(
            "--api-key '\$SIMPLEMDM_API_KEY'",
            self::$installCron,
            'install_cron.sh is writing the API key into the crontab line again. '
            . 'The job must source the 0600 env file instead.'
        );

        $this->assertStringContainsString(
            'set -a; . ',
            self::$installCron,
            'The cron entry must source the env file so secrets stay off the command line.'
        );

        $this->assertStringContainsString(
            'umask 077',
            self::$installCron,
            'The env file must be created under a restrictive umask so it is never briefly world-readable.'
        );
    }

    public function testEnvFileCannotLandInsideTheModuleDirectory(): void
    {
        $this->assertStringContainsString(
            'must not live inside the module directory',
            self::$installCron,
            'install_cron.sh must refuse an --env-file path under the module, which is web-served.'
        );
    }

    public function testActionSecretIsNotReadFromTheQueryString(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/\$_GET\[\s*[\'"]action_secret[\'"]\s*\]/',
            self::$controller,
            'The action secret is being read from $_GET again — that writes it to access logs and Referer headers.'
        );
    }

    public function testModuleArchiveExcludesDotEntriesAndDevDirectories(): void
    {
        $this->assertStringContainsString(
            'private function is_excluded_from_module_archive',
            self::$controller,
            'The archive exclusion helper has gone missing; create_module_archive() would ship everything again.'
        );

        foreach (['vendor', 'tests', 'node_modules', '__pycache__'] as $dir) {
            $this->assertStringContainsString(
                "'" . $dir . "'",
                self::$controller,
                sprintf('%s must stay in the module archive exclusion list.', $dir)
            );
        }
    }

    public function testClientReporterIntegrityControlsDefaultOn(): void
    {
        foreach ([
            'client_reporter_hmac_enabled',
            'client_reporter_replay_protection_enabled',
            'client_reporter_per_device_tokens_enabled',
        ] as $key) {
            $this->assertStringNotContainsString(
                "get_config_value('" . $key . "', '0')",
                self::$controller,
                sprintf(
                    '%s defaults to off again. Without it, one shared secret lets any device write facts for any '
                    . 'serial and lets a captured request be replayed.',
                    $key
                )
            );
        }
    }

    public function testFailedSharedSecretAttemptsAreThrottled(): void
    {
        $this->assertStringContainsString(
            'private function is_auth_throttled',
            self::$controller,
            'The failed-authentication throttle has been removed.'
        );

        // Every endpoint that accepts a shared secret should gate on it.
        $this->assertGreaterThanOrEqual(
            8,
            substr_count(self::$controller, 'deny_shared_secret('),
            'Fewer endpoints are throttled than before — a shared-secret endpoint has lost its gate.'
        );
    }

    public function testThrottleDirectoryIsNotBlindlyTrusted(): void
    {
        // sys_get_temp_dir() is shared with every local account. A directory
        // pre-created by another user (or a symlink standing in for one) would
        // let them clear the counters or pin them at the limit, turning the
        // throttle into a denial of service against the sync integration.
        foreach ([
            'is_link($dir)'      => 'reject a symlink standing in for the directory',
            'posix_geteuid()'    => 'require the directory to be owned by this process',
            '0077'               => 'reject a group- or world-writable directory',
        ] as $needle => $why) {
            $this->assertStringContainsString(
                $needle,
                self::$controller,
                sprintf('throttle_dir() must %s.', $why)
            );
        }

        $this->assertStringContainsString(
            "hash('sha256', \$this->module_path)",
            self::$controller,
            'The throttle directory name must be per-install so two MunkiReport instances on one host '
            . 'do not share counters.'
        );
    }

    public function testThrottleCounterIncrementIsAtomic(): void
    {
        // A read-modify-write split across two calls loses increments under
        // concurrency, which is exactly the condition a parallel guessing
        // campaign creates. Measured: 40 concurrent increments recorded 2
        // with the split version, 40 with a single held lock.
        $this->assertStringContainsString(
            "fopen(\$file, 'c+')",
            self::$controller,
            "record_auth_failure() must open the counter with 'c+' so it can read and write "
            . 'under one lock without truncating.'
        );

        $this->assertStringContainsString(
            'flock($handle, LOCK_EX)',
            self::$controller,
            'record_auth_failure() must hold an exclusive lock across the read and the write.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/file_put_contents\(\s*\$file,\s*json_encode\(\[\s*\x27count\x27/',
            self::$controller,
            'The counter is being written with file_put_contents() again. LOCK_EX there does not '
            . 'help: the stale read has already happened before the lock is taken.'
        );
    }

    public function testEnvFilePathIsCanonicalisedBeforeContainmentCheck(): void
    {
        // A textual prefix match is bypassed by '..' or a symlinked parent,
        // which would place the secrets file under the web root while
        // appearing to pass the check.
        $this->assertStringContainsString(
            'pwd -P',
            self::$installCron,
            'install_cron.sh must resolve --env-file and the module directory with `pwd -P` '
            . 'before comparing them.'
        );

        $this->assertStringContainsString(
            'MODULE_DIR_REAL',
            self::$installCron,
            'The containment check must compare against the canonicalised module directory.'
        );

        $this->assertStringContainsString(
            'must not be a symlink',
            self::$installCron,
            'install_cron.sh must refuse a symlinked --env-file; the redirect that writes it would '
            . 'follow the link straight past the containment check.'
        );

        $this->assertStringNotContainsString(
            '${ENV_FILE##"$MODULE_DIR"/*}',
            self::$installCron,
            'The old textual prefix match is back; it does not resolve `..` or symlinks.'
        );
    }
}

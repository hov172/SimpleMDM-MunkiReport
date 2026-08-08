<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../simplemdm_config_policy.php';

/**
 * Behaviour of the shared secret-disclosure policy.
 *
 * get_config() is reachable with nothing but the sync API key (via
 * index?op=get_config, a route that deliberately carries no session). Whatever
 * that response contains is readable by every holder of that one shared
 * secret, so the question "is this setting a credential?" has to have exactly
 * one answer in the codebase, and its default has to be "yes".
 */
final class ConfigSecretPolicyTest extends TestCase
{
    public function testCredentialShapedNamesAreSecret(): void
    {
        foreach ([
            'api_key',
            'webhook_secret',
            'action_api_secret',
            'client_reporter_secret',
            'client_reporter_device_tokens_json',
            'smtp_password',
            'signing_private_key',
            'vendor_credentials_json',
            'mcp_api_token',
        ] as $name) {
            $this->assertTrue(
                Simplemdm_config_policy::isSecret($name),
                sprintf('%s carries a credential and must never reach a non-global caller.', $name)
            );
        }
    }

    public function testKeyMaterialIsSecretUnderAnyName(): void
    {
        // The first cut of this policy matched only the literal names
        // 'api_key' and 'private_key', so a future 'signing_key' or 'hmac_key'
        // would have been disclosed -- exactly the fail-open behaviour the
        // policy exists to prevent.
        foreach ([
            'key',
            'api_key',
            'private_key',
            'signing_key',
            'hmac_key',
            'encryption_key',
            'shared_key',
            'key_material',
            'client_reporter_hmac_key',
        ] as $name) {
            $this->assertTrue(
                Simplemdm_config_policy::isSecret($name),
                sprintf('%s names key material and must not reach a non-global caller.', $name)
            );
        }
    }

    public function testCryptographicSaltsAndPeppersAreSecret(): void
    {
        foreach (['password_salt', 'auth_pepper'] as $name) {
            $this->assertTrue(
                Simplemdm_config_policy::isSecret($name),
                sprintf('%s is key material by another name.', $name)
            );
        }
    }

    public function testAListOfKeyNamesIsNotKeyMaterial(): void
    {
        // 'keys' plural here means "which fact names are allowed", not
        // credentials. The sync runner and the client reporter both read it.
        $this->assertFalse(
            Simplemdm_config_policy::isSecret('client_reporter_allowed_fact_keys_json'),
            'An allow-list of permitted field names is configuration, not a credential.'
        );
    }

    public function testWordsMerelyContainingKeyAreNotSecret(): void
    {
        // 'key' has to be a whole underscore-delimited segment, or every
        // setting with 'monkey' or 'keyboard' in its name would be redacted.
        foreach (['keyboard_layout', 'monkey_patch_notes'] as $name) {
            $this->assertFalse(
                Simplemdm_config_policy::isSecret($name),
                sprintf('%s does not name key material; the match is too loose.', $name)
            );
        }
    }

    public function testFlagsAboutSecretsAreNotSecret(): void
    {
        // '..._enabled' is a boolean switch, not the thing it switches on. The
        // sync script reads these to decide which integrity controls to apply,
        // so redacting them would break it for no security gain.
        foreach ([
            'client_reporter_per_device_tokens_enabled',
            'client_reporter_hmac_enabled',
            'client_reporter_replay_protection_enabled',
        ] as $name) {
            $this->assertFalse(
                Simplemdm_config_policy::isSecret($name),
                sprintf('%s is a feature flag; redacting it would break the sync runner.', $name)
            );
        }
    }

    public function testTokenPresenceMetadataIsNotSecret(): void
    {
        // client_reporter_token_metadata() reports has_token as a boolean and
        // never the stored hash, so the metadata blob is safe by construction.
        $this->assertFalse(
            Simplemdm_config_policy::isSecret('client_reporter_device_token_metadata_json'),
            'Token metadata carries presence booleans, not token material.'
        );
    }

    public function testPresenceFlagsAreNotThemselvesRedacted(): void
    {
        // Without this, redacting 'api_key' to 'api_key_set' would make the
        // flag itself look like a secret on the next pass.
        $this->assertFalse(
            Simplemdm_config_policy::isSecret('api_key_set'),
            'A presence flag reports only whether a secret exists and must survive redaction.'
        );
    }

    public function testOrdinarySettingsAreNotSecret(): void
    {
        foreach ([
            'sync_interval_minutes',
            'sync_last_api_errors',
            'sync_last_api_error_details',
            'compliance_min_os',
            'script_runner_python_bin',
            'mcp_findings_retention_days',
            'client_reporter_allowed_fact_keys_json',
        ] as $name) {
            $this->assertFalse(
                Simplemdm_config_policy::isSecret($name),
                sprintf('%s is ordinary configuration and the runner needs its value.', $name)
            );
        }
    }

    public function testRedactReplacesSecretValuesWithPresenceFlagsForNonGlobalCallers(): void
    {
        // Arrange
        $config = [
            'api_key'                => 'sk-live-abc123',
            'webhook_secret'         => 'whsec-xyz',
            'client_reporter_secret' => '',
            'sync_interval_minutes'  => '15',
        ];

        // Act
        $redacted = Simplemdm_config_policy::redact($config, false);

        // Assert
        $this->assertArrayNotHasKey('api_key', $redacted);
        $this->assertArrayNotHasKey('webhook_secret', $redacted);
        $this->assertArrayNotHasKey('client_reporter_secret', $redacted);

        $this->assertSame('1', $redacted['api_key_set']);
        $this->assertSame('1', $redacted['webhook_secret_set']);
        $this->assertSame('0', $redacted['client_reporter_secret_set'], 'An empty secret reports as unset.');

        $this->assertSame('15', $redacted['sync_interval_minutes'], 'Ordinary settings pass through untouched.');

        $this->assertStringNotContainsString(
            'sk-live-abc123',
            (string) json_encode($redacted),
            'No secret value may survive anywhere in the serialised response.'
        );
    }

    public function testWhitespaceOnlySecretReportsAsUnset(): void
    {
        $redacted = Simplemdm_config_policy::redact(['webhook_secret' => "   \n"], false);

        $this->assertSame('0', $redacted['webhook_secret_set']);
    }

    public function testRedactPreservesSecretsForGlobalAdmins(): void
    {
        // Arrange
        $config = ['api_key' => 'sk-live-abc123', 'sync_interval_minutes' => '15'];

        // Act
        $redacted = Simplemdm_config_policy::redact($config, true);

        // Assert — the admin settings page needs the real values to render.
        $this->assertSame($config, $redacted);
    }

    public function testAnUnrecognisedFutureSecretIsRedactedWithoutBeingListedAnywhere(): void
    {
        // The whole point of the policy: a setting nobody remembered to
        // enumerate still fails closed.
        $redacted = Simplemdm_config_policy::redact(
            ['some_future_integration_secret' => 'hunter2'],
            false
        );

        $this->assertArrayNotHasKey('some_future_integration_secret', $redacted);
        $this->assertSame('1', $redacted['some_future_integration_secret_set']);
    }

    public function testRedactDoesNotMutateItsInput(): void
    {
        $config = ['api_key' => 'sk-live-abc123'];
        $before = $config;

        Simplemdm_config_policy::redact($config, false);

        $this->assertSame($before, $config, 'redact() must return a new array rather than edit in place.');
    }
}

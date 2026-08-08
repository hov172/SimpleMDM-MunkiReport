<?php

/**
 * Which SimpleMDM settings are credentials, and how they are reported to a
 * caller that may not see them.
 *
 * get_config() answers on index?op=get_config, a route that carries no session
 * by design: the sync runner authenticates with the shared API key alone. So
 * every value in that response is readable by anyone holding that one secret,
 * and "is this setting a credential?" needs a single answer with a safe
 * default.
 *
 * This replaces a hand-maintained list of four secret names that sat beside a
 * writable-settings list of roughly fifty. Matching by name shape means a
 * setting added later is redacted without anyone remembering to come back
 * here -- the failure mode is a redacted non-secret (visible, annoying, and
 * caught by a test) rather than a disclosed secret (silent).
 *
 * @package munkireport
 **/
class Simplemdm_config_policy
{
    /**
     * Appended to a redacted key to report presence without the value:
     * api_key becomes api_key_set => '1'.
     **/
    const PRESENCE_SUFFIX = '_set';

    /**
     * Name fragments that mark a setting as carrying credential material.
     *
     * 'key' has to be a whole underscore-delimited segment: it must catch
     * api_key, signing_key and hmac_key without swallowing
     * client_reporter_allowed_fact_keys_json, which is an allow-list of
     * permitted field names that the sync runner reads.
     **/
    const SECRET_NAME_PATTERN = '/(secret|token|password|passphrase|credential|salt|pepper|(^|_)key(_|$))/i';

    /**
     * Suffixes that describe a secret rather than contain one. '..._enabled'
     * is the switch, not the thing it switches on, and the sync runner reads
     * those flags to decide which integrity controls apply.
     **/
    const NON_SECRET_SUFFIXES = ['_enabled'];

    /**
     * Credential-shaped names that are verifiably not credentials. Keep this
     * short, and say why for each entry.
     *
     * - client_reporter_device_token_metadata_json: built by
     *   client_reporter_token_metadata(), which reports has_token as a boolean
     *   and never the stored hash.
     **/
    const EXEMPT_KEYS = ['client_reporter_device_token_metadata_json'];

    /**
     * Whether a setting name identifies credential material.
     *
     * @param string $name
     * @return bool
     **/
    public static function isSecret($name)
    {
        $name = (string) $name;
        if ($name === '') {
            return false;
        }

        if (in_array($name, self::EXEMPT_KEYS, true)) {
            return false;
        }

        // A presence flag reports only whether a secret exists. Without this,
        // redacting api_key would produce api_key_set, which would itself look
        // like a secret and redact to api_key_set_set.
        if (self::hasSuffix($name, self::PRESENCE_SUFFIX)) {
            return false;
        }

        foreach (self::NON_SECRET_SUFFIXES as $suffix) {
            if (self::hasSuffix($name, $suffix)) {
                return false;
            }
        }

        return (bool) preg_match(self::SECRET_NAME_PATTERN, $name);
    }

    /**
     * The key under which a redacted setting's presence is reported.
     *
     * @param string $name
     * @return string
     **/
    public static function presenceFlagFor($name)
    {
        return (string) $name . self::PRESENCE_SUFFIX;
    }

    /**
     * Strip credential values out of an assembled configuration response.
     *
     * Global admins get the response unchanged -- the settings page has to
     * render the real values. Every other caller gets each secret replaced by
     * a '1'/'0' presence flag, so the UI can still say "an API key is stored"
     * without handing over the key.
     *
     * Returns a new array; the input is left untouched.
     *
     * @param array $config Assembled configuration, key => value
     * @param bool $is_global Whether the caller is a global admin
     * @return array
     **/
    public static function redact(array $config, $is_global)
    {
        if ($is_global) {
            return $config;
        }

        $safe = [];
        foreach ($config as $name => $value) {
            if (! self::isSecret($name)) {
                $safe[$name] = $value;
                continue;
            }

            $safe[self::presenceFlagFor($name)] = self::hasValue($value) ? '1' : '0';
        }

        return $safe;
    }

    /**
     * @param string $name
     * @param string $suffix
     * @return bool
     **/
    private static function hasSuffix($name, $suffix)
    {
        return substr($name, -strlen($suffix)) === $suffix;
    }

    /**
     * Whether a stored setting counts as populated. Whitespace is not a
     * secret, and a non-scalar (a decoded list of device tokens, say) counts
     * as present when it is non-empty.
     *
     * @param mixed $value
     * @return bool
     **/
    private static function hasValue($value)
    {
        if (is_scalar($value)) {
            return trim((string) $value) !== '';
        }

        return ! empty($value);
    }
}

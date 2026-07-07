<?php

/**
 * SimpleMDM module class
 *
 * @package munkireport
 * @author simplemdm module
 **/
class Simplemdm_controller extends Module_controller
{
    private $sync_actions = ['ingest', 'ingest_resources', 'ingest_commands', 'ingest_client_facts', 'ingest_mcp_findings', 'webhook', 'update_sync_status', 'begin_sync_run', 'get_config'];
    // Read-only routes that may authenticate with the sync token (X-SIMPLEMDM-API-KEY)
    // instead of a MunkiReport session. Token is validated once in the constructor;
    // the authorized() override below vouches for these requests so they also pass
    // the core Module controller filter.
    private $token_read_actions = ['get_sync_telemetry', 'get_compliance_stats', 'get_command_status_stats', 'get_assignment_group_stats', 'get_resource_type_stats', 'get_os_security_stats', 'get_supplemental_status', 'get_supplemental_overview_stats', 'get_supplemental_applecare_stats', 'get_device_resources', 'get_events', 'get_dashboard_trend', 'get_supplemental_data', 'get_client_facts', 'get_runner_status', 'get_mcp_findings'];
    private $token_read_request = false;
    private $downloadable_scripts = ['simplemdm_sync.py', 'install_cron.sh', 'remove_cron.sh'];

    function __construct()
    {
        // Store module path
        $this->module_path = dirname(__FILE__);
        require_once $this->module_path . '/simplemdm_model.php';
        require_once $this->module_path . '/simplemdm_config_model.php';
        require_once $this->module_path . '/simplemdm_resource_model.php';
        require_once $this->module_path . '/simplemdm_dashboard_snapshot_model.php';
        require_once $this->module_path . '/simplemdm_command_model.php';
        require_once $this->module_path . '/simplemdm_webhook_event_model.php';
        require_once $this->module_path . '/simplemdm_relationship_edge_model.php';
        require_once $this->module_path . '/simplemdm_device_history_model.php';
        require_once $this->module_path . '/simplemdm_sync_run_model.php';
        require_once $this->module_path . '/simplemdm_supplemental_summary_model.php';
        require_once $this->module_path . '/simplemdm_client_fact_model.php';
        require_once $this->module_path . '/simplemdm_client_fact_history_model.php';
        require_once $this->module_path . '/simplemdm_client_reporter_nonce_model.php';
        require_once $this->module_path . '/simplemdm_client_reporter_token_model.php';
        require_once $this->module_path . '/simplemdm_mcp_finding_model.php';

        // Check if authorized (except token-protected sync endpoints)
        $is_sync_action = false;
        if (isset($GLOBALS['engine'])) {
            $uri = trim($GLOBALS['engine']->get_uri_string(), '/');
            $parts = explode('/', $uri);
            if (count($parts) >= 3 && $parts[0] === 'module' && $parts[1] === 'simplemdm') {
                $is_sync_action = in_array($parts[2], $this->sync_actions, true);
                if (! $is_sync_action && $parts[2] === 'index') {
                    $op = isset($_GET['op']) ? trim((string)$_GET['op']) : '';
                    $is_sync_action = in_array($op, $this->sync_actions, true);
                }
                // Vouch for token-carrying requests to whitelisted read routes AND to
                // direct sync routes (e.g. POST ingest_mcp_findings): both must pass the
                // core Module controller filter, which only exempts index/get_script.
                // Sync actions still re-validate their own token/secret before any work.
                // get_config is excluded: the vouch makes authorized('global') true,
                // which get_config uses to decide full-vs-masked secrets — token
                // callers must keep getting masked output via index?op=get_config.
                if ((in_array($parts[2], $this->token_read_actions, true)
                        || (in_array($parts[2], $this->sync_actions, true) && $parts[2] !== 'get_config'))
                    && $this->is_valid_sync_token()) {
                    $this->token_read_request = true;
                }
            }
        }

        if (! $is_sync_action && ! $this->authorized()) {
            die('Authenticate first.');
        }
    }

    /**
     * Vouch for token-authenticated read-only requests.
     *
     * MunkiReport core (app/controllers/Module.php) session-gates every module
     * action except index/get_script by calling this method. For requests to
     * a whitelisted read-only action carrying a valid sync token (validated in
     * the constructor), report authorized so headless API clients can read
     * dashboard data without a browser session.
     **/
    public function authorized($what = '')
    {
        if ($this->token_read_request) {
            return true;
        }
        return parent::authorized($what);
    }

    private function get_stored_api_key()
    {
        $this->connectDB();
        $setting = Simplemdm_config_model::where('name', 'api_key')->first();
        return $setting ? trim((string)$setting->value) : '';
    }

    private function get_config_value($name, $default = '')
    {
        $row = Simplemdm_config_model::where('name', (string)$name)->first();
        return $row ? (string)$row->value : (string)$default;
    }

    private function set_config_value($name, $value)
    {
        Simplemdm_config_model::updateOrCreate(
            ['name' => (string)$name],
            ['value' => (string)$value]
        );
    }

    private function has_sync_runs_table()
    {
        static $has_table = null;
        if ($has_table !== null) {
            return $has_table;
        }

        try {
            $has_table = \Illuminate\Database\Capsule\Manager::schema()->hasTable('simplemdm_sync_run');
        } catch (\Throwable $e) {
            $has_table = false;
        }

        return $has_table;
    }

    private function generate_sync_run_uuid()
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (\Throwable $e) {
            return uniqid('simplemdm_run_', true);
        }
    }

    private function get_current_actor_label()
    {
        foreach (['user', 'login', 'username'] as $key) {
            if (isset($_SESSION[$key]) && trim((string) $_SESSION[$key]) !== '') {
                return trim((string) $_SESSION[$key]);
            }
        }
        return '';
    }

    private function simplemdm_event_module($suffix)
    {
        $suffix = trim((string) $suffix);
        if ($suffix === '') {
            return 'simplemdm';
        }
        return 'simplemdm_' . preg_replace('/[^a-z0-9_]+/i', '_', strtolower($suffix));
    }

    private function built_in_event_catalog()
    {
        return [
            'action' => ['label' => 'Admin Action Accepted', 'description' => 'Create a current event when a mutating admin action is accepted upstream.', 'default_enabled' => '1'],
            'action_failure' => ['label' => 'Admin Action Failed', 'description' => 'Create a current event when a mutating admin action is rejected or fails upstream.', 'default_enabled' => '1'],
            'command' => ['label' => 'Command Failed', 'description' => 'Create a current event when a command transitions into a failed state.', 'default_enabled' => '1'],
            'recovery_lock' => ['label' => 'Recovery Lock Failed', 'description' => 'Create a current event when a recovery-lock command transitions into a failed state.', 'default_enabled' => '1'],
            'enrollment' => ['label' => 'Enrollment Regressed', 'description' => 'Create a current event when a device leaves the enrolled state.', 'default_enabled' => '1'],
            'dep' => ['label' => 'ADE/DEP Regressed', 'description' => 'Create a current event when ADE/DEP enrollment transitions from enabled to disabled.', 'default_enabled' => '1'],
            'filevault' => ['label' => 'FileVault Disabled', 'description' => 'Create a current event when FileVault transitions from enabled to disabled.', 'default_enabled' => '1'],
            'supervision' => ['label' => 'Supervision Disabled', 'description' => 'Create a current event when supervision transitions from enabled to disabled.', 'default_enabled' => '1'],
            'firewall' => ['label' => 'Firewall Disabled', 'description' => 'Create a current event when firewall protection transitions from enabled to disabled.', 'default_enabled' => '1'],
            'sip' => ['label' => 'SIP Disabled', 'description' => 'Create a current event when SIP transitions from enabled to disabled.', 'default_enabled' => '1'],
            'passcode' => ['label' => 'Passcode Noncompliant', 'description' => 'Create a current event when passcode compliance transitions from compliant to non-compliant.', 'default_enabled' => '1'],
            'activation_lock' => ['label' => 'Activation Lock Disabled', 'description' => 'Create a current event when Activation Lock transitions from enabled to disabled.', 'default_enabled' => '1'],
            'stale' => ['label' => 'Device Became Stale', 'description' => 'Create a current event when last seen time crosses the configured stale threshold.', 'default_enabled' => '1'],
        ];
    }

    private function built_in_event_settings()
    {
        $catalog = $this->built_in_event_catalog();
        $defaults = [];
        foreach ($catalog as $suffix => $meta) {
            $defaults[$suffix] = isset($meta['default_enabled']) && (string) $meta['default_enabled'] === '0' ? '0' : '1';
        }

        $raw = trim($this->get_config_value('event_builtin_settings_json', ''));
        if ($raw === '') {
            return $defaults;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $defaults;
        }

        foreach ($catalog as $suffix => $_meta) {
            if (array_key_exists($suffix, $decoded)) {
                $defaults[$suffix] = (string) $decoded[$suffix] === '0' ? '0' : '1';
            }
        }

        return $defaults;
    }

    private function is_built_in_event_enabled($suffix)
    {
        $catalog = $this->built_in_event_catalog();
        if (! array_key_exists($suffix, $catalog)) {
            return true;
        }

        $settings = $this->built_in_event_settings();
        return ! isset($settings[$suffix]) || (string) $settings[$suffix] !== '0';
    }

    private function custom_event_field_catalog()
    {
        return [
            'status' => ['label' => 'Enrollment Status', 'type' => 'string', 'triggers' => ['changed_to']],
            'is_supervised' => ['label' => 'Supervision', 'type' => 'bool', 'triggers' => ['became_disabled']],
            'is_dep_enrollment' => ['label' => 'ADE / DEP', 'type' => 'bool', 'triggers' => ['became_disabled']],
            'filevault_enabled' => ['label' => 'FileVault', 'type' => 'bool', 'triggers' => ['became_disabled']],
            'firewall_enabled' => ['label' => 'Firewall', 'type' => 'bool', 'triggers' => ['became_disabled']],
            'sip_enabled' => ['label' => 'SIP', 'type' => 'bool', 'triggers' => ['became_disabled']],
            'activation_lock_enabled' => ['label' => 'Activation Lock', 'type' => 'bool', 'triggers' => ['became_disabled']],
            'passcode_compliant' => ['label' => 'Passcode Compliance', 'type' => 'bool', 'triggers' => ['became_disabled']],
            'last_seen_at' => ['label' => 'Last Seen', 'type' => 'datetime', 'triggers' => ['older_than_hours']],
        ];
    }

    private function normalize_custom_event_rules($raw)
    {
        $rules = $raw;
        if (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed === '') {
                return [];
            }
            $rules = json_decode($trimmed, true);
        }

        if (! is_array($rules)) {
            throw new \RuntimeException('Custom event rules must be a JSON array.');
        }

        $field_catalog = $this->custom_event_field_catalog();
        $built_in_suffixes = array_keys($this->built_in_event_catalog());
        $normalized = [];
        $seen_suffixes = [];

        foreach ($rules as $index => $rule) {
            if (! is_array($rule)) {
                throw new \RuntimeException('Each custom event rule must be a JSON object.');
            }

            $enabled = isset($rule['enabled']) && (string) $rule['enabled'] === '0' ? '0' : '1';
            $suffix = strtolower(trim((string) ($rule['suffix'] ?? '')));
            $suffix = preg_replace('/[^a-z0-9_]+/', '_', $suffix);
            $suffix = trim($suffix, '_');
            if ($suffix === '') {
                throw new \RuntimeException('Each custom event rule requires a non-empty suffix.');
            }
            if (in_array($suffix, $built_in_suffixes, true)) {
                throw new \RuntimeException('Custom event suffix "' . $suffix . '" conflicts with a built-in event.');
            }
            if (isset($seen_suffixes[$suffix])) {
                throw new \RuntimeException('Duplicate custom event suffix "' . $suffix . '" is not allowed.');
            }
            $seen_suffixes[$suffix] = true;

            $source_field = trim((string) ($rule['source_field'] ?? ''));
            if (! isset($field_catalog[$source_field])) {
                throw new \RuntimeException('Custom event source field "' . $source_field . '" is not supported.');
            }

            $trigger_type = trim((string) ($rule['trigger_type'] ?? ''));
            if (! in_array($trigger_type, $field_catalog[$source_field]['triggers'], true)) {
                throw new \RuntimeException('Trigger "' . $trigger_type . '" is not allowed for source field "' . $source_field . '".');
            }

            $severity = strtolower(trim((string) ($rule['severity'] ?? 'warning')));
            if (! in_array($severity, ['info', 'warning', 'danger', 'success'], true)) {
                $severity = 'warning';
            }

            $message = trim((string) ($rule['message'] ?? ''));
            if ($message === '') {
                throw new \RuntimeException('Custom event "' . $suffix . '" requires a message.');
            }

            $target_value = trim((string) ($rule['target_value'] ?? ''));
            if ($trigger_type === 'changed_to' && $target_value === '') {
                throw new \RuntimeException('Custom event "' . $suffix . '" requires target_value for changed_to.');
            }

            $threshold_hours = '';
            if ($trigger_type === 'older_than_hours') {
                $hours = (int) ($rule['threshold_hours'] ?? 0);
                if ($hours < 1) {
                    throw new \RuntimeException('Custom event "' . $suffix . '" requires threshold_hours >= 1.');
                }
                $threshold_hours = (string) $hours;
            }

            $normalized[] = [
                'enabled' => $enabled,
                'suffix' => $suffix,
                'label' => trim((string) ($rule['label'] ?? '')),
                'source_field' => $source_field,
                'trigger_type' => $trigger_type,
                'severity' => $severity,
                'message' => $message,
                'target_value' => $trigger_type === 'changed_to' ? $target_value : '',
                'threshold_hours' => $threshold_hours,
                'sort_order' => (string) $index,
            ];
        }

        return $normalized;
    }

    private function custom_event_rules()
    {
        try {
            return $this->normalize_custom_event_rules($this->get_config_value('custom_event_rules_json', '[]'));
        } catch (\RuntimeException $e) {
            return [];
        }
    }

    private function store_simplemdm_event($serial_number, $suffix, $type, $message, $data = [])
    {
        $serial_number = trim((string) $serial_number);
        if ($serial_number === '') {
            return;
        }
        if (! $this->is_built_in_event_enabled($suffix)) {
            return;
        }

        $payload = '';
        if ($data !== '' && $data !== null) {
            if (is_array($data) || is_object($data)) {
                $payload = json_encode($data);
                if ($payload === false) {
                    $payload = '';
                }
            } else {
                $payload = (string) $data;
            }
        }

        store_event(
            $serial_number,
            $this->simplemdm_event_module($suffix),
            (string) $type,
            (string) $message,
            $payload
        );
    }

    private function delete_simplemdm_event($serial_number, $suffix)
    {
        $serial_number = trim((string) $serial_number);
        if ($serial_number === '') {
            return;
        }

        Event_model::where('serial_number', $serial_number)
            ->where('module', $this->simplemdm_event_module($suffix))
            ->delete();
    }

    private function find_device_by_serial_or_id($serial_number = '', $simplemdm_id = '')
    {
        $serial_number = trim((string) $serial_number);
        $simplemdm_id = trim((string) $simplemdm_id);

        if ($serial_number !== '') {
            $row = Simplemdm_model::where('serial_number', $serial_number)->first();
            if ($row) {
                return $row;
            }
        }

        if ($simplemdm_id !== '') {
            return Simplemdm_model::where('simplemdm_id', $simplemdm_id)->first();
        }

        return null;
    }

    private function get_device_snapshot($serial_number = '', $simplemdm_id = '')
    {
        $row = $this->find_device_by_serial_or_id($serial_number, $simplemdm_id);
        if (! $row) {
            return null;
        }

        return [
            'serial_number' => (string) ($row->serial_number ?? ''),
            'simplemdm_id' => (string) ($row->simplemdm_id ?? ''),
            'status' => isset($row->status) ? (string) $row->status : null,
            'last_seen_at' => isset($row->last_seen_at) ? (string) $row->last_seen_at : null,
            'is_supervised' => isset($row->is_supervised) ? (int) $row->is_supervised : null,
            'is_dep_enrollment' => isset($row->is_dep_enrollment) ? (int) $row->is_dep_enrollment : null,
            'filevault_enabled' => isset($row->filevault_enabled) ? (int) $row->filevault_enabled : null,
            'firewall_enabled' => isset($row->firewall_enabled) ? (int) $row->firewall_enabled : null,
            'sip_enabled' => isset($row->sip_enabled) ? (int) $row->sip_enabled : null,
            'activation_lock_enabled' => isset($row->activation_lock_enabled) ? (int) $row->activation_lock_enabled : null,
            'passcode_compliant' => isset($row->passcode_compliant) ? (int) $row->passcode_compliant : null,
        ];
    }

    private function normalize_device_status($value)
    {
        return strtolower(trim((string) $value));
    }

    private function normalize_device_bool($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value === true || $value === 1 || $value === '1' || $value === 'true') {
            return 1;
        }
        if ($value === false || $value === 0 || $value === '0' || $value === 'false') {
            return 0;
        }
        return null;
    }

    private function parse_simplemdm_timestamp($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $ts = strtotime($value);
        if ($ts === false || $ts <= 0) {
            return null;
        }

        return (int) $ts;
    }

    private function get_stale_event_threshold_seconds()
    {
        $hours = (int) $this->get_config_value('event_stale_threshold_hours', '168');
        if ($hours < 1) {
            $hours = 168;
        }

        return $hours * 3600;
    }

    private function is_device_stale($last_seen_at)
    {
        $ts = $this->parse_simplemdm_timestamp($last_seen_at);
        if ($ts === null) {
            return false;
        }

        return $ts <= (time() - $this->get_stale_event_threshold_seconds());
    }

    private function is_device_stale_for_hours($last_seen_at, $hours)
    {
        $ts = $this->parse_simplemdm_timestamp($last_seen_at);
        if ($ts === null) {
            return false;
        }

        $hours = (int) $hours;
        if ($hours < 1) {
            $hours = 1;
        }

        return $ts <= (time() - ($hours * 3600));
    }

    private function evaluate_custom_device_events($before, $after, $record = [])
    {
        if (! is_array($after) || empty($after['serial_number'])) {
            return;
        }

        $serial = (string) $after['serial_number'];
        foreach ($this->custom_event_rules() as $rule) {
            if ((string) ($rule['enabled'] ?? '1') !== '1') {
                continue;
            }

            $source_field = (string) ($rule['source_field'] ?? '');
            if ($source_field === '' || ! array_key_exists($source_field, $record)) {
                continue;
            }

            $suffix = (string) ($rule['suffix'] ?? '');
            if ($suffix === '') {
                continue;
            }

            $trigger_type = (string) ($rule['trigger_type'] ?? '');
            $severity = (string) ($rule['severity'] ?? 'warning');
            $message = (string) ($rule['message'] ?? '');
            $event_data = [
                'source' => 'simplemdm',
                'reason' => 'custom_event',
                'serial_number' => $serial,
                'simplemdm_id' => (string) ($after['simplemdm_id'] ?? ''),
                'custom_event_suffix' => $suffix,
                'custom_event_label' => (string) ($rule['label'] ?? ''),
                'source_field' => $source_field,
                'trigger_type' => $trigger_type,
            ];

            $should_store = false;
            $should_clear = false;

            if ($trigger_type === 'became_disabled') {
                $old_value = $this->normalize_device_bool(is_array($before) ? ($before[$source_field] ?? null) : null);
                $new_value = $this->normalize_device_bool($after[$source_field] ?? null);
                $should_store = $old_value === 1 && $new_value !== 1;
                $should_clear = $new_value === 1;
                $event_data['current_value'] = $new_value;
            } elseif ($trigger_type === 'changed_to') {
                $target_value = $this->normalize_device_status($rule['target_value'] ?? '');
                $old_value = $this->normalize_device_status(is_array($before) ? ($before[$source_field] ?? '') : '');
                $new_value = $this->normalize_device_status($after[$source_field] ?? '');
                $should_store = $target_value !== '' && $old_value !== $target_value && $new_value === $target_value;
                $should_clear = $target_value !== '' && $new_value !== '' && $new_value !== $target_value;
                $event_data['target_value'] = $target_value;
                $event_data['current_value'] = $new_value;
            } elseif ($trigger_type === 'older_than_hours') {
                $threshold_hours = (int) ($rule['threshold_hours'] ?? 0);
                $old_stale = is_array($before) ? $this->is_device_stale_for_hours($before[$source_field] ?? null, $threshold_hours) : false;
                $new_stale = $this->is_device_stale_for_hours($after[$source_field] ?? null, $threshold_hours);
                $should_store = ! $old_stale && $new_stale;
                $should_clear = ! $new_stale && trim((string) ($after[$source_field] ?? '')) !== '';
                $event_data['threshold_hours'] = $threshold_hours;
                $event_data['current_value'] = isset($after[$source_field]) ? (string) $after[$source_field] : '';
            }

            if ($should_store) {
                $this->store_simplemdm_event($serial, $suffix, $severity, $message, $event_data);
            } elseif ($should_clear) {
                $this->delete_simplemdm_event($serial, $suffix);
            }
        }
    }

    private function evaluate_device_regression_events($before, $after, $record = [])
    {
        if (! is_array($after) || empty($after['serial_number'])) {
            return;
        }

        $serial = (string) $after['serial_number'];
        $data = [
            'source' => 'simplemdm',
            'serial_number' => $serial,
            'simplemdm_id' => (string) ($after['simplemdm_id'] ?? ''),
        ];

        if (is_array($record) && array_key_exists('status', $record)) {
            $old_status = $this->normalize_device_status(is_array($before) ? ($before['status'] ?? '') : '');
            $new_status = $this->normalize_device_status($after['status'] ?? '');
            if ($old_status === 'enrolled' && $new_status !== 'enrolled') {
                $this->store_simplemdm_event(
                    $serial,
                    'enrollment',
                    'warning',
                    'SimpleMDM: device is no longer enrolled',
                    array_merge($data, ['reason' => 'unenrolled', 'status' => $new_status])
                );
            } elseif ($new_status === 'enrolled') {
                $this->delete_simplemdm_event($serial, 'enrollment');
            }
        }

        if (is_array($record) && array_key_exists('filevault_enabled', $record)) {
            $old_filevault = $this->normalize_device_bool(is_array($before) ? ($before['filevault_enabled'] ?? null) : null);
            $new_filevault = $this->normalize_device_bool($after['filevault_enabled'] ?? null);
            if ($old_filevault === 1 && $new_filevault !== 1) {
                $this->store_simplemdm_event(
                    $serial,
                    'filevault',
                    'warning',
                    'SimpleMDM: FileVault became disabled',
                    array_merge($data, ['reason' => 'filevault_disabled', 'filevault_enabled' => $new_filevault])
                );
            } elseif ($new_filevault === 1) {
                $this->delete_simplemdm_event($serial, 'filevault');
            }
        }

        if (is_array($record) && array_key_exists('is_supervised', $record)) {
            $old_supervised = $this->normalize_device_bool(is_array($before) ? ($before['is_supervised'] ?? null) : null);
            $new_supervised = $this->normalize_device_bool($after['is_supervised'] ?? null);
            if ($old_supervised === 1 && $new_supervised !== 1) {
                $this->store_simplemdm_event(
                    $serial,
                    'supervision',
                    'warning',
                    'SimpleMDM: supervision became disabled',
                    array_merge($data, ['reason' => 'supervision_disabled', 'is_supervised' => $new_supervised])
                );
            } elseif ($new_supervised === 1) {
                $this->delete_simplemdm_event($serial, 'supervision');
            }
        }

        if (is_array($record) && array_key_exists('is_dep_enrollment', $record)) {
            $old_dep = $this->normalize_device_bool(is_array($before) ? ($before['is_dep_enrollment'] ?? null) : null);
            $new_dep = $this->normalize_device_bool($after['is_dep_enrollment'] ?? null);
            if ($old_dep === 1 && $new_dep !== 1) {
                $this->store_simplemdm_event(
                    $serial,
                    'dep',
                    'warning',
                    'SimpleMDM: ADE enrollment became disabled',
                    array_merge($data, ['reason' => 'dep_disabled', 'is_dep_enrollment' => $new_dep])
                );
            } elseif ($new_dep === 1) {
                $this->delete_simplemdm_event($serial, 'dep');
            }
        }

        if (is_array($record) && array_key_exists('firewall_enabled', $record)) {
            $old_firewall = $this->normalize_device_bool(is_array($before) ? ($before['firewall_enabled'] ?? null) : null);
            $new_firewall = $this->normalize_device_bool($after['firewall_enabled'] ?? null);
            if ($old_firewall === 1 && $new_firewall !== 1) {
                $this->store_simplemdm_event(
                    $serial,
                    'firewall',
                    'warning',
                    'SimpleMDM: firewall became disabled',
                    array_merge($data, ['reason' => 'firewall_disabled', 'firewall_enabled' => $new_firewall])
                );
            } elseif ($new_firewall === 1) {
                $this->delete_simplemdm_event($serial, 'firewall');
            }
        }

        if (is_array($record) && array_key_exists('sip_enabled', $record)) {
            $old_sip = $this->normalize_device_bool(is_array($before) ? ($before['sip_enabled'] ?? null) : null);
            $new_sip = $this->normalize_device_bool($after['sip_enabled'] ?? null);
            if ($old_sip === 1 && $new_sip !== 1) {
                $this->store_simplemdm_event(
                    $serial,
                    'sip',
                    'warning',
                    'SimpleMDM: SIP became disabled',
                    array_merge($data, ['reason' => 'sip_disabled', 'sip_enabled' => $new_sip])
                );
            } elseif ($new_sip === 1) {
                $this->delete_simplemdm_event($serial, 'sip');
            }
        }

        if (is_array($record) && array_key_exists('activation_lock_enabled', $record)) {
            $old_activation_lock = $this->normalize_device_bool(is_array($before) ? ($before['activation_lock_enabled'] ?? null) : null);
            $new_activation_lock = $this->normalize_device_bool($after['activation_lock_enabled'] ?? null);
            if ($old_activation_lock === 1 && $new_activation_lock !== 1) {
                $this->store_simplemdm_event(
                    $serial,
                    'activation_lock',
                    'warning',
                    'SimpleMDM: activation lock became disabled',
                    array_merge($data, ['reason' => 'activation_lock_disabled', 'activation_lock_enabled' => $new_activation_lock])
                );
            } elseif ($new_activation_lock === 1) {
                $this->delete_simplemdm_event($serial, 'activation_lock');
            }
        }

        if (is_array($record) && array_key_exists('passcode_compliant', $record)) {
            $old_passcode = $this->normalize_device_bool(is_array($before) ? ($before['passcode_compliant'] ?? null) : null);
            $new_passcode = $this->normalize_device_bool($after['passcode_compliant'] ?? null);
            if ($old_passcode === 1 && $new_passcode !== 1) {
                $this->store_simplemdm_event(
                    $serial,
                    'passcode',
                    'warning',
                    'SimpleMDM: passcode compliance failed',
                    array_merge($data, ['reason' => 'passcode_noncompliant', 'passcode_compliant' => $new_passcode])
                );
            } elseif ($new_passcode === 1) {
                $this->delete_simplemdm_event($serial, 'passcode');
            }
        }

        if (is_array($record) && array_key_exists('last_seen_at', $record)) {
            $old_stale = is_array($before) ? $this->is_device_stale($before['last_seen_at'] ?? null) : false;
            $new_stale = $this->is_device_stale($after['last_seen_at'] ?? null);
            $last_seen_at = isset($after['last_seen_at']) ? (string) $after['last_seen_at'] : '';

            if (! $old_stale && $new_stale) {
                $this->store_simplemdm_event(
                    $serial,
                    'stale',
                    'warning',
                    'SimpleMDM: device has gone stale',
                    array_merge($data, ['reason' => 'device_stale', 'last_seen_at' => $last_seen_at])
                );
            } elseif (! $new_stale && $last_seen_at !== '') {
                $this->delete_simplemdm_event($serial, 'stale');
            }
        }

        $this->evaluate_custom_device_events($before, $after, $record);
    }

    private function emit_device_regression_events_from_records($records)
    {
        if (! is_array($records) || ! $records) {
            return;
        }

        $snapshots = [];
        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }
            $serial = isset($record['serial_number']) ? trim((string) $record['serial_number']) : '';
            $simplemdm_id = isset($record['simplemdm_id']) ? trim((string) $record['simplemdm_id']) : '';
            if ($serial === '' && $simplemdm_id === '') {
                continue;
            }
            $key = ($serial !== '' ? $serial : 'id:' . $simplemdm_id);
            if (! array_key_exists($key, $snapshots)) {
                $snapshots[$key] = $this->get_device_snapshot($serial, $simplemdm_id);
            }
        }

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }
            $serial = isset($record['serial_number']) ? trim((string) $record['serial_number']) : '';
            $simplemdm_id = isset($record['simplemdm_id']) ? trim((string) $record['simplemdm_id']) : '';
            if ($serial === '' && $simplemdm_id === '') {
                continue;
            }
            $key = ($serial !== '' ? $serial : 'id:' . $simplemdm_id);
            $after = $this->get_device_snapshot($serial, $simplemdm_id);
            if (! $after) {
                continue;
            }
            $this->evaluate_device_regression_events(
                array_key_exists($key, $snapshots) ? $snapshots[$key] : null,
                $after,
                $record
            );
        }
    }

    private function normalize_command_status($value)
    {
        return strtolower(trim((string) $value));
    }

    private function is_failed_command_status($value)
    {
        $status = $this->normalize_command_status($value);
        if ($status === '') {
            return false;
        }

        foreach (['error', 'fail', 'denied', 'reject', 'cancel', 'invalid', 'expired', 'timeout'] as $needle) {
            if (strpos($status, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function emit_command_failure_event($before, $after)
    {
        if (! $after || ! isset($after->serial_number) || trim((string) $after->serial_number) === '') {
            return;
        }

        $serial_number = (string) $after->serial_number;
        $previous_status = $before ? $this->normalize_command_status($before->status ?? '') : '';
        $current_status = $this->normalize_command_status($after->status ?? '');
        if (! $this->is_failed_command_status($current_status)) {
            if ($current_status !== '') {
                $this->delete_simplemdm_event($serial_number, 'command');
                $command_type = trim((string) ($after->command_type ?? ''));
                if ($command_type !== '' && strpos(strtolower($command_type), 'recovery_lock') !== false) {
                    $this->delete_simplemdm_event($serial_number, 'recovery_lock');
                }
            }
            return;
        }
        if ($this->is_failed_command_status($previous_status) && $previous_status === $current_status) {
            return;
        }

        $command_type = trim((string) ($after->command_type ?? 'command'));
        $message = 'SimpleMDM: command failed';
        if ($command_type !== '') {
            $message .= ': ' . $command_type;
        }

        $this->store_simplemdm_event(
            $serial_number,
            'command',
            'danger',
            $message,
            [
                'source' => 'simplemdm',
                'reason' => 'command_failed',
                'serial_number' => (string) $after->serial_number,
                'simplemdm_id' => (string) ($after->device_id ?? ''),
                'command_uuid' => (string) ($after->command_uuid ?? ''),
                'command_type' => $command_type,
                'status' => $current_status,
                'error_message' => (string) ($after->error_message ?? ''),
            ]
        );

        if (strpos(strtolower($command_type), 'recovery_lock') !== false) {
            $this->store_simplemdm_event(
                $serial_number,
                'recovery_lock',
                'danger',
                'SimpleMDM: recovery lock command failed',
                [
                    'source' => 'simplemdm',
                    'reason' => 'recovery_lock_failed',
                    'serial_number' => $serial_number,
                    'simplemdm_id' => (string) ($after->device_id ?? ''),
                    'command_uuid' => (string) ($after->command_uuid ?? ''),
                    'command_type' => $command_type,
                    'status' => $current_status,
                    'error_message' => (string) ($after->error_message ?? ''),
                ]
            );
        }
    }

    private function has_supplemental_summary_table()
    {
        static $has_table = null;
        if ($has_table !== null) {
            return $has_table;
        }

        try {
            $has_table = \Illuminate\Database\Capsule\Manager::schema()->hasTable('simplemdm_supplemental_summary');
        } catch (\Throwable $e) {
            $has_table = false;
        }

        return $has_table;
    }

    private function has_client_fact_table()
    {
        static $has_table = null;
        if ($has_table !== null) {
            return $has_table;
        }

        try {
            $has_table = \Illuminate\Database\Capsule\Manager::schema()->hasTable('simplemdm_client_fact');
        } catch (\Throwable $e) {
            $has_table = false;
        }

        return $has_table;
    }

    private function has_client_fact_history_table()
    {
        static $has_table = null;
        if ($has_table !== null) {
            return $has_table;
        }

        try {
            $has_table = \Illuminate\Database\Capsule\Manager::schema()->hasTable('simplemdm_client_fact_history');
        } catch (\Throwable $e) {
            $has_table = false;
        }

        return $has_table;
    }

    private function has_client_reporter_nonce_table()
    {
        static $has_table = null;
        if ($has_table !== null) {
            return $has_table;
        }

        try {
            $has_table = \Illuminate\Database\Capsule\Manager::schema()->hasTable('simplemdm_client_reporter_nonce');
        } catch (\Throwable $e) {
            $has_table = false;
        }

        return $has_table;
    }

    private function has_client_reporter_token_table()
    {
        static $has_table = null;
        if ($has_table !== null) {
            return $has_table;
        }

        try {
            $has_table = \Illuminate\Database\Capsule\Manager::schema()->hasTable('simplemdm_client_reporter_token');
        } catch (\Throwable $e) {
            $has_table = false;
        }

        return $has_table;
    }

    private function supplemental_registry()
    {
        $registry = [
            'filevault_status' => [
                'source_id' => 'filevault_status',
                'label' => 'FileVault Status',
                'table' => 'filevault_status',
                'join_key' => 'serial_number',
                'required_columns' => ['serial_number', 'filevault_status'],
            ],
            'findmymac' => [
                'source_id' => 'findmymac',
                'label' => 'Find My Mac',
                'table' => 'findmymac',
                'join_key' => 'serial_number',
                'required_columns' => ['serial_number', 'status'],
            ],
            'applecare' => [
                'source_id' => 'applecare',
                'label' => 'Warranty / AppleCare',
                'table' => 'warranty',
                'join_key' => 'serial_number',
                'required_columns' => ['serial_number', 'end_date', 'status'],
            ],
            'profile' => [
                'source_id' => 'profile',
                'label' => 'Profiles',
                'table' => 'profile',
                'join_key' => 'serial_number',
                'required_columns' => ['serial_number', 'timestamp'],
            ],
            'managedinstalls' => [
                'source_id' => 'managedinstalls',
                'label' => 'Managed Installs',
                'table' => 'managedinstalls',
                'join_key' => 'serial_number',
                'required_columns' => ['serial_number', 'status'],
            ],
        ];

        foreach ($this->discover_generic_supplemental_sources() as $source_id => $definition) {
            if (! isset($registry[$source_id])) {
                $registry[$source_id] = $definition;
            }
        }

        $override_json = trim($this->get_config_value('supplemental_registry_json', ''));
        if ($override_json !== '') {
            $decoded = json_decode($override_json, true);
            if (is_array($decoded)) {
                foreach ($decoded as $source_id => $definition) {
                    if (! is_array($definition)) {
                        continue;
                    }
                    $source_id = trim((string) $source_id);
                    if ($source_id === '') {
                        continue;
                    }
                    $base = isset($registry[$source_id]) && is_array($registry[$source_id]) ? $registry[$source_id] : [];
                    if (isset($definition['required_columns']) && ! is_array($definition['required_columns'])) {
                        unset($definition['required_columns']);
                    }
                    $registry[$source_id] = array_merge($base, $definition, ['source_id' => $source_id]);
                }
            }
        }

        return $registry;
    }

    private function list_loaded_module_ids()
    {
        $modules_dir = APP_ROOT . 'local/modules';
        if (! is_dir($modules_dir)) {
            return [];
        }

        $ids = [];
        foreach (glob($modules_dir . '/*', GLOB_ONLYDIR) ?: [] as $path) {
            $module_id = basename((string) $path);
            if ($module_id === '' || $module_id === 'simplemdm') {
                continue;
            }
            $ids[$module_id] = $module_id;
        }

        ksort($ids);
        return array_values($ids);
    }

    private function module_php_files($module_id)
    {
        $module_dir = APP_ROOT . 'local/modules/' . trim((string) $module_id);
        if (! is_dir($module_dir)) {
            return [];
        }

        $files = glob($module_dir . '/*.php') ?: [];
        sort($files);
        return $files;
    }

    private function infer_module_table_candidates($module_id)
    {
        $module_id = trim((string) $module_id);
        if ($module_id === '') {
            return [];
        }

        $candidates = [$module_id => $module_id];
        $patterns = [
            '/\bFROM\s+`?([a-zA-Z0-9_]+)`?/i',
            '/\bJOIN\s+`?([a-zA-Z0-9_]+)`?/i',
            '/->from\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]\s*\)/i',
            '/(?:Capsule|Manager)::table\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]\s*\)/i',
            '/->table\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]\s*\)/i',
            '/\$(?:this->)?table\s*=\s*[\'"]([a-zA-Z0-9_]+)[\'"]/i',
        ];

        foreach ($this->module_php_files($module_id) as $file) {
            $contents = @file_get_contents($file);
            if (! is_string($contents) || $contents === '') {
                continue;
            }

            foreach ($patterns as $pattern) {
                if (! preg_match_all($pattern, $contents, $matches)) {
                    continue;
                }
                foreach ($matches[1] as $match) {
                    $table = trim((string) $match);
                    if ($table === '' || preg_match('/^(select|where|left|right|inner|outer|using|on)$/i', $table)) {
                        continue;
                    }
                    $candidates[$table] = $table;
                }
            }
        }

        return array_values($candidates);
    }

    private function detect_generic_join_key_for_table($table)
    {
        foreach (['serial_number', 'machine_serial', 'serial'] as $column) {
            if ($this->schema_has_columns($table, [$column])) {
                return $column;
            }
        }

        return '';
    }

    private function detect_generic_freshness_column($table)
    {
        foreach (['updated_at', 'timestamp', 'add_time', 'profile_install_date', 'install_date', 'date', 'end_date'] as $column) {
            if ($this->schema_has_columns($table, [$column])) {
                return $column;
            }
        }

        return '';
    }

    private function discover_generic_supplemental_sources()
    {
        static $registry = null;
        if ($registry !== null) {
            return $registry;
        }

        $registry = [];
        try {
            $schema = \Illuminate\Database\Capsule\Manager::schema();
        } catch (\Throwable $e) {
            return $registry;
        }

        foreach ($this->list_loaded_module_ids() as $module_id) {
            if (in_array($module_id, ['filevault_status', 'findmymac', 'profile', 'managedinstalls', 'warranty'], true)) {
                continue;
            }
            $table_candidates = $this->infer_module_table_candidates($module_id);
            $selected_table = $module_id;
            $join_key = '';
            $table_exists = false;

            foreach ($table_candidates as $candidate) {
                try {
                    if (! $schema->hasTable($candidate)) {
                        continue;
                    }
                } catch (\Throwable $e) {
                    continue;
                }

                $candidate_join_key = $this->detect_generic_join_key_for_table($candidate);
                if ($candidate_join_key !== '') {
                    $selected_table = $candidate;
                    $join_key = $candidate_join_key;
                    $table_exists = true;
                    break;
                }
                if (! $table_exists) {
                    $selected_table = $candidate;
                    $table_exists = true;
                }
            }

            if ($join_key === '' && $table_exists) {
                $join_key = $this->detect_generic_join_key_for_table($selected_table);
            }

            $registry[$module_id] = [
                'source_id' => $module_id,
                'label' => ucwords(str_replace('_', ' ', $module_id)),
                'table' => $selected_table,
                'join_key' => $join_key,
                'required_columns' => $join_key !== '' ? [$join_key] : [],
                'generic' => true,
                'auto_discovered' => true,
                'table_candidates' => $table_candidates,
                'freshness_column' => $table_exists ? $this->detect_generic_freshness_column($selected_table) : '',
            ];
        }

        return $registry;
    }

    private function supplemental_enabled()
    {
        return $this->get_config_value('supplemental_enabled', '1') !== '0';
    }

    private function supplemental_disabled_source_ids()
    {
        $raw = trim($this->get_config_value('supplemental_disabled_sources_json', '[]'));
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        $ids = [];
        foreach ($decoded as $value) {
            $source_id = trim((string) $value);
            if ($source_id !== '') {
                $ids[$source_id] = true;
            }
        }

        return array_keys($ids);
    }

    private function supplemental_source_is_enabled($source_id)
    {
        return ! in_array((string) $source_id, $this->supplemental_disabled_source_ids(), true);
    }

    private function client_reporter_enabled()
    {
        return $this->get_config_value('client_reporter_enabled', '0') === '1';
    }

    private function client_reporter_history_enabled()
    {
        return $this->get_config_value('client_reporter_history_enabled', '1') === '1';
    }

    private function client_reporter_max_payload_bytes()
    {
        $bytes = (int) $this->get_config_value('client_reporter_max_payload_bytes', '16384');
        return $bytes > 0 ? $bytes : 16384;
    }

    private function client_reporter_hmac_enabled()
    {
        return $this->get_config_value('client_reporter_hmac_enabled', '0') === '1';
    }

    private function client_reporter_replay_protection_enabled()
    {
        return $this->get_config_value('client_reporter_replay_protection_enabled', '0') === '1';
    }

    private function client_reporter_per_device_tokens_enabled()
    {
        return $this->get_config_value('client_reporter_per_device_tokens_enabled', '0') === '1';
    }

    private function client_reporter_proxy_only_enabled()
    {
        return $this->get_config_value('client_reporter_proxy_only_enabled', '0') === '1';
    }

    private function client_reporter_max_time_skew_seconds()
    {
        $seconds = (int) $this->get_config_value('client_reporter_max_time_skew_seconds', '300');
        return $seconds >= 30 ? $seconds : 300;
    }

    private function client_reporter_ip_rules($key)
    {
        $raw = trim($this->get_config_value($key, ''));
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[\s,]+/', $raw) ?: [];
        $rules = [];
        foreach ($parts as $part) {
            $rule = trim((string) $part);
            if ($rule !== '') {
                $rules[$rule] = $rule;
            }
        }

        return array_values($rules);
    }

    private function client_reporter_allowed_fact_keys()
    {
        $defaults = ['mdm_profile_present', 'console_user', 'uptime_seconds', 'munki_last_run_result', 'local_filevault_enabled'];
        $raw = trim($this->get_config_value('client_reporter_allowed_fact_keys_json', json_encode($defaults)));
        if ($raw === '') {
            return $defaults;
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $defaults;
        }
        $keys = [];
        foreach ($decoded as $value) {
            $key = trim((string) $value);
            if ($key !== '') {
                $keys[] = $key;
            }
        }
        return $keys ?: $defaults;
    }

    private function client_fact_registry()
    {
        return [
            'mdm_profile_present' => ['fact_type' => 'mdm_health', 'type' => 'bool', 'label' => 'MDM Profile Present'],
            'console_user' => ['fact_type' => 'session', 'type' => 'string', 'label' => 'Console User'],
            'uptime_seconds' => ['fact_type' => 'session', 'type' => 'int', 'label' => 'Uptime Seconds'],
            'munki_last_run_result' => ['fact_type' => 'software', 'type' => 'string', 'label' => 'Munki Last Run Result'],
            'local_filevault_enabled' => ['fact_type' => 'security', 'type' => 'bool', 'label' => 'Local FileVault Enabled'],
        ];
    }

    private function client_reporter_token_metadata()
    {
        if (! $this->has_client_reporter_token_table()) {
            return [];
        }

        $rows = [];
        foreach (Simplemdm_client_reporter_token_model::orderBy('serial_number')->orderBy('label')->get() as $row) {
            $rows[] = [
                'serial_number' => (string) $row->serial_number,
                'label' => trim((string) $row->label) !== '' ? (string) $row->label : 'default',
                'enabled' => (int) $row->enabled === 1,
                'last_used_at' => (string) $row->last_used_at,
                'updated_at' => (string) $row->updated_at,
                'has_token' => trim((string) $row->token_hash) !== '',
            ];
        }

        return $rows;
    }

    private function normalize_client_reporter_token_rows($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Client reporter device tokens must be a JSON object or array.');
        }

        $rows = [];
        if (array_keys($decoded) !== range(0, count($decoded) - 1)) {
            foreach ($decoded as $serial_number => $token) {
                $rows[] = [
                    'serial_number' => trim((string) $serial_number),
                    'label' => 'default',
                    'token' => (string) $token,
                    'enabled' => true,
                ];
            }
        } else {
            foreach ($decoded as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $rows[] = [
                    'serial_number' => trim((string) ($item['serial_number'] ?? '')),
                    'label' => trim((string) ($item['label'] ?? 'default')) ?: 'default',
                    'token' => (string) ($item['token'] ?? ''),
                    'enabled' => ! array_key_exists('enabled', $item) || (string) $item['enabled'] !== '0',
                ];
            }
        }

        $normalized = [];
        foreach ($rows as $row) {
            $serial_number = strtoupper(trim((string) $row['serial_number']));
            $label = trim((string) $row['label']) ?: 'default';
            $token = trim((string) $row['token']);
            if ($serial_number === '' || $token === '') {
                continue;
            }
            $normalized[] = [
                'serial_number' => $serial_number,
                'label' => $label,
                'token_hash' => hash('sha256', $token),
                'enabled' => ! empty($row['enabled']) ? 1 : 0,
            ];
        }

        return $normalized;
    }

    private function sync_client_reporter_tokens($raw)
    {
        $rows = $this->normalize_client_reporter_token_rows($raw);
        if ($rows === null) {
            return null;
        }

        if (! $this->has_client_reporter_token_table()) {
            throw new \RuntimeException('Client reporter token table is unavailable. Run migrations first.');
        }

        Simplemdm_client_reporter_token_model::query()->delete();
        $now = date('Y-m-d H:i:s');
        foreach ($rows as $row) {
            Simplemdm_client_reporter_token_model::create([
                'serial_number' => $row['serial_number'],
                'label' => $row['label'],
                'token_hash' => $row['token_hash'],
                'enabled' => $row['enabled'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return count($rows);
    }

    private function supplemental_stale_after_minutes()
    {
        $minutes = (int) $this->get_config_value('supplemental_default_stale_after_minutes', '1440');
        return $minutes > 0 ? $minutes : 1440;
    }

    private function schema_has_columns($table, $columns)
    {
        try {
            $schema = \Illuminate\Database\Capsule\Manager::schema();
            foreach ((array) $columns as $column) {
                if (! $schema->hasColumn($table, $column)) {
                    return false;
                }
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function detect_supplemental_source($source)
    {
        $registry = $this->supplemental_registry();
        if (! isset($registry[$source])) {
            return [
                'source_id' => (string) $source,
                'detected' => false,
                'reason' => 'unknown_source',
            ];
        }

        $definition = $registry[$source];
        $table = $definition['table'];

        try {
            $schema = \Illuminate\Database\Capsule\Manager::schema();
            if (! $schema->hasTable($table)) {
                return array_merge($definition, [
                    'detected' => false,
                    'reason' => 'table_missing',
                    'has_data' => false,
                ]);
            }
        } catch (\Throwable $e) {
            return array_merge($definition, [
                'detected' => false,
                'reason' => 'schema_error',
                'has_data' => false,
            ]);
        }

        if (! empty($definition['generic']) && empty($definition['join_key'])) {
            return array_merge($definition, [
                'detected' => false,
                'reason' => 'no_supported_join_key',
                'has_data' => false,
            ]);
        }

        if (! $this->schema_has_columns($table, $definition['required_columns'])) {
            return array_merge($definition, [
                'detected' => false,
                'reason' => 'required_columns_missing',
                'has_data' => false,
            ]);
        }

        try {
            $has_data = \Illuminate\Database\Capsule\Manager::table($table)->limit(1)->count() > 0;
        } catch (\Throwable $e) {
            $has_data = false;
        }

        return array_merge($definition, [
            'detected' => true,
            'reason' => $has_data ? 'ok' : 'no_rows',
            'has_data' => $has_data,
        ]);
    }

    private function get_detected_supplemental_sources()
    {
        $detected = [];
        foreach (array_keys($this->supplemental_registry()) as $source_id) {
            $detected[$source_id] = array_merge(
                $this->detect_supplemental_source($source_id),
                ['enabled' => $this->supplemental_source_is_enabled($source_id)]
            );
        }
        return $detected;
    }

    private function parse_supplemental_datetime($value, $is_epoch = false)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($is_epoch) {
            $epoch = (int) $value;
            if ($epoch <= 0) {
                return null;
            }
            return gmdate('c', $epoch);
        }

        try {
            return (new \DateTime((string) $value))->format('c');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function format_supplemental_freshness($source_timestamp, $summary_refresh, $is_detected, $refresh_status = 'success')
    {
        if (! $is_detected) {
            return [
                'state' => 'module_not_detected',
                'source_timestamp' => null,
                'summary_refresh' => $summary_refresh,
                'basis' => null,
            ];
        }

        if ($refresh_status === 'failed') {
            return [
                'state' => 'refresh_failed',
                'source_timestamp' => $source_timestamp,
                'summary_refresh' => $summary_refresh,
                'basis' => $source_timestamp ? 'source_timestamp' : ($summary_refresh ? 'summary_refresh' : null),
            ];
        }

        $basis = $source_timestamp ?: $summary_refresh;
        if (! $basis) {
            return [
                'state' => 'missing',
                'source_timestamp' => null,
                'summary_refresh' => $summary_refresh,
                'basis' => null,
            ];
        }

        try {
            $age_minutes = (int) floor((time() - (new \DateTime($basis))->getTimestamp()) / 60);
        } catch (\Throwable $e) {
            $age_minutes = 0;
        }

        return [
            'state' => $age_minutes > $this->supplemental_stale_after_minutes() ? 'stale' : 'fresh',
            'source_timestamp' => $source_timestamp,
            'summary_refresh' => $summary_refresh,
            'basis' => $source_timestamp ? 'source_timestamp' : 'summary_refresh',
            'age_minutes' => max(0, $age_minutes),
        ];
    }

    private function normalize_filevault_enabled($status)
    {
        $value = strtolower(trim((string) $status));
        if ($value === '') {
            return null;
        }
        if (strpos($value, 'off') !== false || strpos($value, 'disable') !== false) {
            return 0;
        }
        if (strpos($value, 'on') !== false || strpos($value, 'encrypt') !== false) {
            return 1;
        }
        return null;
    }

    private function normalize_findmymac_enabled($status)
    {
        $value = strtolower(trim((string) $status));
        if ($value === '') {
            return null;
        }
        if (in_array($value, ['disabled', 'off', 'inactive', '0', 'unknown'], true)) {
            return 0;
        }
        return 1;
    }

    private function build_supplemental_source_payload($source_id, $serial_number, $summary_refresh = null, $refresh_status = 'success')
    {
        $source = $this->detect_supplemental_source($source_id);
        $payload = [
            'source_id' => $source_id,
            'label' => isset($source['label']) ? (string) $source['label'] : (string) $source_id,
            'table' => isset($source['table']) ? (string) $source['table'] : '',
            'detected' => ! empty($source['detected']),
            'enabled' => $this->supplemental_source_is_enabled($source_id),
            'generic' => ! empty($source['generic']),
            'auto_discovered' => ! empty($source['auto_discovered']),
            'reason' => isset($source['reason']) ? (string) $source['reason'] : '',
            'present' => null,
            'summary' => [],
            'detail' => [],
            'freshness' => $this->format_supplemental_freshness(null, $summary_refresh, ! empty($source['detected']), $refresh_status),
        ];

        if (empty($source['detected'])) {
            return $payload;
        }

        if (! $payload['enabled']) {
            $payload['present'] = 0;
            $payload['reason'] = 'disabled_in_settings';
            $payload['freshness'] = [
                'state' => 'disabled_in_settings',
                'source_timestamp' => null,
                'summary_refresh' => $summary_refresh,
                'basis' => null,
            ];
            return $payload;
        }

        try {
            switch ($source_id) {
                case 'filevault_status':
                    $row = \Illuminate\Database\Capsule\Manager::table('filevault_status')
                        ->where('serial_number', $serial_number)
                        ->first();
                    $payload['present'] = $row ? 1 : 0;
                    if (! $row) {
                        return $payload;
                    }
                    $enabled = $this->normalize_filevault_enabled($row->filevault_status ?? null);
                    $payload['summary'] = [
                        'enabled' => $enabled,
                        'status' => (string) ($row->filevault_status ?? ''),
                    ];
                    $payload['detail'] = [
                        'FileVault Status' => (string) ($row->filevault_status ?? ''),
                        'Users' => (string) ($row->filevault_users ?? ''),
                        'Auth Restart Support' => isset($row->auth_restart_support) ? (int) $row->auth_restart_support : null,
                        'Personal Recovery Key' => isset($row->has_personal_recovery_key) ? (int) $row->has_personal_recovery_key : null,
                        'Institutional Recovery Key' => isset($row->has_institutional_recovery_key) ? (int) $row->has_institutional_recovery_key : null,
                        'Using Recovery Key' => isset($row->using_recovery_key) ? (int) $row->using_recovery_key : null,
                        'Conversion State' => (string) ($row->conversion_state ?? ''),
                        'Conversion Percent' => isset($row->conversion_percent) ? (int) $row->conversion_percent : null,
                        'Bootstrap Token Supported' => isset($row->bootstraptoken_supported) ? (int) $row->bootstraptoken_supported : null,
                        'Bootstrap Token Escrowed' => isset($row->bootstraptoken_escrowed) ? (int) $row->bootstraptoken_escrowed : null,
                    ];
                    $payload['freshness'] = $this->format_supplemental_freshness(null, $summary_refresh, true, $refresh_status);
                    return $payload;

                case 'findmymac':
                    $row = \Illuminate\Database\Capsule\Manager::table('findmymac')
                        ->where('serial_number', $serial_number)
                        ->first();
                    $payload['present'] = $row ? 1 : 0;
                    if (! $row) {
                        return $payload;
                    }
                    $source_timestamp = $this->parse_supplemental_datetime($row->add_time ?? null, true);
                    $payload['summary'] = [
                        'enabled' => $this->normalize_findmymac_enabled($row->status ?? null),
                        'status' => (string) ($row->status ?? ''),
                    ];
                    $payload['detail'] = [
                        'Status' => (string) ($row->status ?? ''),
                        'Owner' => (string) ($row->ownerdisplayname ?? ''),
                        'Email' => (string) ($row->email ?? ''),
                        'Hostname' => (string) ($row->hostname ?? ''),
                        'Added At' => $source_timestamp,
                    ];
                    $payload['freshness'] = $this->format_supplemental_freshness($source_timestamp, $summary_refresh, true, $refresh_status);
                    return $payload;

                case 'applecare':
                    $row = \Illuminate\Database\Capsule\Manager::table('warranty')
                        ->where('serial_number', $serial_number)
                        ->first();
                    $payload['present'] = $row ? 1 : 0;
                    if (! $row) {
                        return $payload;
                    }
                    $payload['summary'] = [
                        'coverage_end' => (string) ($row->end_date ?? ''),
                        'coverage_status' => (string) ($row->status ?? ''),
                    ];
                    $payload['detail'] = [
                        'Purchase Date' => (string) ($row->purchase_date ?? ''),
                        'Coverage End' => (string) ($row->end_date ?? ''),
                        'Status' => (string) ($row->status ?? ''),
                        'Estimated Manufacture Date' => (string) ($row->est_mfg_date ?? ''),
                        'iCloud Logged In' => isset($row->icloud_logged_in) ? (int) $row->icloud_logged_in : null,
                    ];
                    $payload['freshness'] = $this->format_supplemental_freshness(null, $summary_refresh, true, $refresh_status);
                    return $payload;

                case 'profile':
                    $rows = \Illuminate\Database\Capsule\Manager::table('profile')
                        ->where('serial_number', $serial_number)
                        ->orderBy('timestamp', 'desc')
                        ->get();
                    $payload['present'] = $rows->count() > 0 ? 1 : 0;
                    if (! $rows->count()) {
                        return $payload;
                    }
                    $latest_timestamp = null;
                    $profiles = [];
                    foreach ($rows->take(25) as $row) {
                        $profile_timestamp = $this->parse_supplemental_datetime($row->timestamp ?? null, true);
                        if ($latest_timestamp === null && $profile_timestamp) {
                            $latest_timestamp = $profile_timestamp;
                        }
                        $profiles[] = [
                            'name' => (string) ($row->profile_name ?: $row->payload_display ?: $row->payload_name ?: ''),
                            'organization' => (string) ($row->profile_organization ?? ''),
                            'install_date' => $this->parse_supplemental_datetime($row->profile_install_date ?? null, true),
                            'verification_state' => (string) ($row->profile_verification_state ?? ''),
                            'method' => (string) ($row->profile_method ?? ''),
                            'user' => (string) ($row->user ?? ''),
                        ];
                    }
                    $payload['summary'] = [
                        'profile_count' => $rows->count(),
                    ];
                    $payload['detail'] = [
                        'Profile Count' => $rows->count(),
                        'Profiles' => $profiles,
                    ];
                    $payload['freshness'] = $this->format_supplemental_freshness($latest_timestamp, $summary_refresh, true, $refresh_status);
                    return $payload;

                case 'managedinstalls':
                    $rows = \Illuminate\Database\Capsule\Manager::table('managedinstalls')
                        ->where('serial_number', $serial_number)
                        ->orderBy('display_name', 'asc')
                        ->get();
                    $payload['present'] = $rows->count() > 0 ? 1 : 0;
                    if (! $rows->count()) {
                        return $payload;
                    }
                    $warning_count = 0;
                    $error_count = 0;
                    $status_breakdown = [];
                    $items = [];
                    foreach ($rows as $row) {
                        $status = strtolower(trim((string) $row->status));
                        if ($status !== '') {
                            if (! isset($status_breakdown[$status])) {
                                $status_breakdown[$status] = 0;
                            }
                            $status_breakdown[$status]++;
                            if (strpos($status, 'error') !== false || strpos($status, 'fail') !== false) {
                                $error_count++;
                            } elseif (strpos($status, 'warning') !== false || strpos($status, 'pending') !== false) {
                                $warning_count++;
                            }
                        }
                    }
                    foreach ($rows->take(25) as $row) {
                        $items[] = [
                            'name' => (string) ($row->display_name ?: $row->name ?: ''),
                            'version' => (string) ($row->version ?? ''),
                            'status' => (string) ($row->status ?? ''),
                            'type' => (string) ($row->type ?? ''),
                            'installed' => isset($row->installed) ? (int) $row->installed : null,
                        ];
                    }
                    $payload['summary'] = [
                        'warning_count' => $warning_count,
                        'error_count' => $error_count,
                        'item_count' => $rows->count(),
                    ];
                    $payload['detail'] = [
                        'Managed Installs Count' => $rows->count(),
                        'Warning Count' => $warning_count,
                        'Error Count' => $error_count,
                        'Status Breakdown' => $status_breakdown,
                        'Items' => $items,
                    ];
                    $payload['freshness'] = $this->format_supplemental_freshness(null, $summary_refresh, true, $refresh_status);
                    return $payload;

                default:
                    if (! empty($source['generic'])) {
                        $table = (string) ($source['table'] ?? '');
                        $join_key = (string) ($source['join_key'] ?? '');
                        $freshness_column = (string) ($source['freshness_column'] ?? '');
                        if ($table === '' || $join_key === '') {
                            return $payload;
                        }

                        $base_query = \Illuminate\Database\Capsule\Manager::table($table)->where($join_key, $serial_number);
                        $row_count = (int) $base_query->count();
                        $payload['present'] = $row_count > 0 ? 1 : 0;
                        $payload['summary'] = ['row_count' => $row_count];
                        if ($row_count < 1) {
                            return $payload;
                        }

                        $row_query = \Illuminate\Database\Capsule\Manager::table($table)->where($join_key, $serial_number);
                        if ($freshness_column !== '') {
                            $row_query->orderBy($freshness_column, 'desc');
                        }
                        $row = $row_query->first();
                        if (! $row) {
                            return $payload;
                        }

                        $source_timestamp = null;
                        if ($freshness_column !== '' && isset($row->{$freshness_column})) {
                            $source_timestamp = $this->parse_supplemental_datetime(
                                $row->{$freshness_column},
                                $freshness_column === 'add_time'
                            );
                            if ($source_timestamp !== null) {
                                $payload['summary']['latest_timestamp'] = $source_timestamp;
                            }
                        }

                        $detail = [
                            'Table' => $table,
                            'Join Key' => $join_key,
                            'Row Count' => $row_count,
                        ];
                        $added = 0;
                        foreach ((array) $row as $column => $value) {
                            if ($added >= 12) {
                                break;
                            }
                            if (is_array($value) || is_object($value)) {
                                continue;
                            }
                            $detail[ucwords(str_replace('_', ' ', (string) $column))] = $value === null ? '' : (string) $value;
                            $added++;
                        }
                        $payload['detail'] = $detail;
                        $payload['freshness'] = $this->format_supplemental_freshness($source_timestamp, $summary_refresh, true, $refresh_status);
                        return $payload;
                    }
            }
        } catch (\Throwable $e) {
            $payload['present'] = null;
            $payload['reason'] = 'query_failed';
            $payload['detail'] = ['error' => $e->getMessage()];
            $payload['freshness'] = $this->format_supplemental_freshness(null, $summary_refresh, true, 'failed');
            return $payload;
        }

        return $payload;
    }

    private function format_client_fact_value_for_output($row)
    {
        if ($row->fact_value_bool !== null) {
            return (int) $row->fact_value_bool;
        }
        if ($row->fact_value_int !== null) {
            return (int) $row->fact_value_int;
        }
        if ($row->fact_value_json !== null && trim((string) $row->fact_value_json) !== '') {
            $decoded = json_decode((string) $row->fact_value_json, true);
            return $decoded !== null ? $decoded : (string) $row->fact_value_json;
        }
        return (string) $row->fact_value_string;
    }

    private function build_client_reporter_payload($serial_number)
    {
        $payload = [
            'source_id' => 'client_reporter',
            'label' => 'Client Reporter',
            'table' => 'simplemdm_client_fact',
            'detected' => $this->has_client_fact_table(),
            'reason' => $this->has_client_fact_table() ? 'ok' : 'table_missing',
            'present' => 0,
            'summary' => [],
            'detail' => [],
            'freshness' => $this->format_supplemental_freshness(null, null, $this->has_client_fact_table(), 'success'),
        ];

        if (! $this->client_reporter_enabled()) {
            $payload['reason'] = 'client_reporter_disabled';
            return $payload;
        }

        if (! $this->has_client_fact_table()) {
            return $payload;
        }

        $rows = Simplemdm_client_fact_model::where('serial_number', $serial_number)
            ->orderBy('fact_key', 'asc')
            ->get();
        if (! $rows->count()) {
            return $payload;
        }

        $payload['present'] = 1;
        $latest_reported_at = null;
        $detail = [];
        $summary = [];
        $registry = $this->client_fact_registry();
        foreach ($rows as $row) {
            $value = $this->format_client_fact_value_for_output($row);
            $label = isset($registry[$row->fact_key]['label']) ? $registry[$row->fact_key]['label'] : (string) $row->fact_key;
            $detail[$label] = $value;
            $summary[$row->fact_key] = $value;
            $reported_at = $this->format_sync_datetime($row->reported_at);
            if ($reported_at !== '') {
                if ($latest_reported_at === null || strtotime($reported_at) > strtotime($latest_reported_at)) {
                    $latest_reported_at = $reported_at;
                }
            }
        }
        $detail['Reported Facts'] = $rows->count();
        $payload['summary'] = $summary;
        $payload['detail'] = $detail;
        $payload['freshness'] = $this->format_supplemental_freshness($latest_reported_at, null, true, 'success');
        return $payload;
    }

    private function build_supplemental_summary_payload($serial_number)
    {
        $refresh_time = gmdate('c');
        $sources = [];
        $source_modules = [];
        $source_freshness = [];
        $status = 'success';

        foreach (array_keys($this->supplemental_registry()) as $source_id) {
            $payload = $this->build_supplemental_source_payload($source_id, $serial_number, $refresh_time, 'success');
            $sources[$source_id] = $payload;
            $source_freshness[$source_id] = $payload['freshness'];
            if ($payload['reason'] === 'query_failed') {
                $status = $status === 'success' ? 'partial' : $status;
            }
            if ((int) $payload['present'] === 1) {
                $source_modules[] = $source_id;
            }
        }

        return [
            'row' => [
                'serial_number' => $serial_number,
                'source_modules_json' => json_encode(array_values($source_modules)),
                'last_refresh' => $this->normalize_sync_datetime($refresh_time) ?: gmdate('Y-m-d H:i:s'),
                'last_refresh_status' => $status,
                'source_freshness_json' => json_encode($source_freshness),
                'filevault_present' => $sources['filevault_status']['present'],
                'filevault_enabled' => $sources['filevault_status']['summary']['enabled'] ?? null,
                'findmymac_present' => $sources['findmymac']['present'],
                'findmymac_enabled' => $sources['findmymac']['summary']['enabled'] ?? null,
                'applecare_present' => $sources['applecare']['present'],
                'applecare_coverage_end' => $sources['applecare']['summary']['coverage_end'] ?? null,
                'applecare_coverage_status' => $sources['applecare']['summary']['coverage_status'] ?? null,
                'profile_present' => $sources['profile']['present'],
                'profile_count' => $sources['profile']['summary']['profile_count'] ?? null,
                'managedinstalls_present' => $sources['managedinstalls']['present'],
                'managedinstalls_warning_count' => $sources['managedinstalls']['summary']['warning_count'] ?? null,
                'managedinstalls_error_count' => $sources['managedinstalls']['summary']['error_count'] ?? null,
            ],
            'sources' => $sources,
        ];
    }

    private function refresh_supplemental_summary_for_serial($serial_number)
    {
        if (! $this->has_supplemental_summary_table()) {
            return null;
        }

        $serial_number = trim((string) $serial_number);
        if ($serial_number === '') {
            return null;
        }

        $payload = $this->build_supplemental_summary_payload($serial_number);
        return Simplemdm_supplemental_summary_model::updateOrCreate(
            ['serial_number' => $serial_number],
            $payload['row']
        );
    }

    private function maybe_refresh_supplemental_summary_for_serial($serial_number)
    {
        if (! $this->has_supplemental_summary_table()) {
            return null;
        }

        $row = Simplemdm_supplemental_summary_model::where('serial_number', $serial_number)->first();
        if (! $row) {
            return $this->refresh_supplemental_summary_for_serial($serial_number);
        }

        $should_refresh = false;
        try {
            if (! $row->last_refresh) {
                $should_refresh = true;
            } else {
                $age_minutes = (int) floor((time() - (new \DateTime((string) $row->last_refresh))->getTimestamp()) / 60);
                $should_refresh = $age_minutes > $this->supplemental_stale_after_minutes();
            }
        } catch (\Throwable $e) {
            $should_refresh = true;
        }

        return $should_refresh ? $this->refresh_supplemental_summary_for_serial($serial_number) : $row;
    }

    private function serialize_supplemental_summary_row($row)
    {
        if (! $row) {
            return null;
        }

        return [
            'serial_number' => (string) $row->serial_number,
            'source_modules' => json_decode((string) $row->source_modules_json, true) ?: [],
            'last_refresh' => $this->format_sync_datetime($row->last_refresh),
            'last_refresh_status' => (string) $row->last_refresh_status,
            'source_freshness' => json_decode((string) $row->source_freshness_json, true) ?: [],
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
        ];
    }

    private function normalize_sync_datetime($value)
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }
        if (strpos($raw, ' - ') !== false) {
            $raw = trim(explode(' - ', $raw, 2)[0]);
        }
        if (substr($raw, -1) === 'Z') {
            $raw = substr($raw, 0, -1) . '+00:00';
        }
        try {
            return (new \DateTime($raw))->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function format_sync_datetime($value)
    {
        if (! $value) {
            return '';
        }
        try {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('c');
            }
            return (new \DateTime((string) $value))->format('c');
        } catch (\Throwable $e) {
            return trim((string) $value);
        }
    }

    private function normalize_run_status($value)
    {
        $status = strtolower(trim((string) $value));
        if ($status === '') {
            return '';
        }
        if (in_array($status, ['success', 'failed', 'running', 'queued', 'idle', 'skipped'], true)) {
            return $status;
        }
        if (in_array($status, ['error', 'failure'], true)) {
            return 'failed';
        }
        return $status;
    }

    private function format_run_status_label($value)
    {
        $status = $this->normalize_run_status($value);
        if ($status === '') {
            return '';
        }
        if ($status === 'idle') {
            return 'Idle';
        }
        return ucwords(str_replace('_', ' ', $status));
    }

    private function extract_sync_counts($summary)
    {
        $summary = (string) $summary;
        $counts = [
            'devices_synced' => null,
            'resources_synced' => null,
            'commands_synced' => null,
        ];
        if (preg_match('/([0-9]+)\s+devices?/i', $summary, $m)) {
            $counts['devices_synced'] = (int) $m[1];
        }
        if (preg_match('/([0-9]+)\s+resources?/i', $summary, $m)) {
            $counts['resources_synced'] = (int) $m[1];
        }
        if (preg_match('/([0-9]+)\s+commands?/i', $summary, $m)) {
            $counts['commands_synced'] = (int) $m[1];
        }
        return $counts;
    }

    private function create_sync_run($attributes)
    {
        if (! $this->has_sync_runs_table()) {
            return null;
        }

        $payload = array_merge([
            'run_uuid' => $this->generate_sync_run_uuid(),
            'source' => '',
            'status' => 'queued',
            'requested_at' => null,
            'started_at' => null,
            'finished_at' => null,
            'duration_ms' => null,
            'devices_synced' => null,
            'resources_synced' => null,
            'commands_synced' => null,
            'api_requests' => null,
            'api_errors' => null,
            'rate_limit_hits' => null,
            'delta_mode' => null,
            'scope' => null,
            'summary' => null,
            'error_summary' => null,
            'requested_by' => null,
            'trigger_context' => null,
        ], $attributes);

        return Simplemdm_sync_run_model::create($payload);
    }

    private function get_sync_run_by_uuid($run_uuid)
    {
        if (! $this->has_sync_runs_table()) {
            return null;
        }
        $run_uuid = trim((string) $run_uuid);
        if ($run_uuid === '') {
            return null;
        }
        return Simplemdm_sync_run_model::where('run_uuid', $run_uuid)->first();
    }

    private function get_queued_sync_run()
    {
        if (! $this->has_sync_runs_table()) {
            return null;
        }
        return Simplemdm_sync_run_model::where('status', 'queued')
            ->orderBy('requested_at', 'asc')
            ->orderBy('id', 'asc')
            ->first();
    }

    private function expire_stale_running_sync_runs($maxAgeSeconds = 7200)
    {
        if (! $this->has_sync_runs_table()) {
            return;
        }

        $maxAgeSeconds = (int) $maxAgeSeconds;
        if ($maxAgeSeconds < 60) {
            $maxAgeSeconds = 60;
        }

        $cutoff = date('Y-m-d H:i:s', time() - $maxAgeSeconds);
        $staleRuns = Simplemdm_sync_run_model::where('status', 'running')
            ->where(function ($query) use ($cutoff) {
                $query->whereNotNull('started_at')
                    ->where('started_at', '<', $cutoff)
                    ->orWhere(function ($nested) use ($cutoff) {
                        $nested->whereNull('started_at')
                            ->whereNotNull('requested_at')
                            ->where('requested_at', '<', $cutoff);
                    });
            })
            ->get();

        foreach ($staleRuns as $run) {
            $startedAt = $this->format_sync_datetime($run->started_at);
            $requestedAt = $this->format_sync_datetime($run->requested_at);
            $reference = $startedAt !== '' ? $startedAt : $requestedAt;
            $run->status = 'failed';
            $run->finished_at = date('Y-m-d H:i:s');
            $run->error_summary = trim(
                'Stale running sync auto-cleared after timeout'
                . ($reference !== '' ? ' (started/requested at ' . $reference . ')' : '')
            );
            $run->save();
        }
    }

    private function get_running_sync_run()
    {
        if (! $this->has_sync_runs_table()) {
            return null;
        }
        $this->expire_stale_running_sync_runs();
        return Simplemdm_sync_run_model::where('status', 'running')
            ->orderBy('started_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    private function get_latest_completed_sync_run()
    {
        if (! $this->has_sync_runs_table()) {
            return null;
        }
        return Simplemdm_sync_run_model::whereIn('status', ['success', 'failed', 'skipped'])
            ->orderBy('finished_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    private function get_recent_sync_runs($limit = 8)
    {
        if (! $this->has_sync_runs_table()) {
            return [];
        }
        return Simplemdm_sync_run_model::orderBy('id', 'desc')
            ->limit((int) $limit)
            ->get();
    }

    private function serialize_sync_run($run)
    {
        if (! $run) {
            return null;
        }
        return [
            'run_uuid' => (string) $run->run_uuid,
            'source' => (string) $run->source,
            'status' => (string) $run->status,
            'status_label' => $this->format_run_status_label($run->status),
            'requested_at' => $this->format_sync_datetime($run->requested_at),
            'started_at' => $this->format_sync_datetime($run->started_at),
            'finished_at' => $this->format_sync_datetime($run->finished_at),
            'duration_ms' => $run->duration_ms !== null ? (int) $run->duration_ms : null,
            'devices_synced' => $run->devices_synced !== null ? (int) $run->devices_synced : null,
            'resources_synced' => $run->resources_synced !== null ? (int) $run->resources_synced : null,
            'commands_synced' => $run->commands_synced !== null ? (int) $run->commands_synced : null,
            'api_requests' => $run->api_requests !== null ? (int) $run->api_requests : null,
            'api_errors' => $run->api_errors !== null ? (int) $run->api_errors : null,
            'rate_limit_hits' => $run->rate_limit_hits !== null ? (int) $run->rate_limit_hits : null,
            'delta_mode' => $run->delta_mode !== null ? ((int) $run->delta_mode ? '1' : '0') : '',
            'scope' => (string) $run->scope,
            'summary' => (string) $run->summary,
            'error_summary' => (string) $run->error_summary,
            'requested_by' => (string) $run->requested_by,
            'trigger_context' => (string) $run->trigger_context,
        ];
    }

    private function derive_sync_state_from_runs()
    {
        if (! $this->has_sync_runs_table()) {
            return [];
        }

        $queued = $this->get_queued_sync_run();
        $running = $this->get_running_sync_run();
        $completed = $this->get_latest_completed_sync_run();
        $recent = [];
        foreach ($this->get_recent_sync_runs() as $run) {
            $recent[] = $this->serialize_sync_run($run);
        }

        $state = $running ? 'running' : ($queued ? 'queued' : 'idle');
        $last_completed_status = $completed ? $this->format_run_status_label($completed->status) : '';
        $last_completed_time = $completed ? (string) $completed->summary : '';
        $last_completed_source = $completed ? (string) $completed->source : '';

        return [
            'sync_request_state' => $state,
            'sync_requested_at' => $running
                ? $this->format_sync_datetime($running->requested_at)
                : ($queued ? $this->format_sync_datetime($queued->requested_at) : ''),
            'sync_started_at' => $running ? $this->format_sync_datetime($running->started_at) : '',
            'sync_request_source' => $running ? (string) $running->source : $last_completed_source,
            'last_sync_status' => $running ? 'Running' : $last_completed_status,
            'last_sync_time' => $running ? ((string) $running->summary ?: $this->format_sync_datetime($running->started_at)) : $last_completed_time,
            'last_completed_sync_status' => $last_completed_status,
            'last_completed_sync_time' => $last_completed_time,
            'last_completed_sync_source' => $last_completed_source,
            'active_sync_run_uuid' => $running ? (string) $running->run_uuid : '',
            'active_sync_source' => $running ? (string) $running->source : '',
            'pending_sync_run_uuid' => $queued ? (string) $queued->run_uuid : '',
            'pending_sync_source' => $queued ? (string) $queued->source : '',
            'sync_recent_runs' => $recent,
        ];
    }

    private function refresh_legacy_sync_config_from_runs()
    {
        $state = $this->derive_sync_state_from_runs();
        if (! $state) {
            return;
        }

        $this->set_config_value('sync_request_state', $state['sync_request_state']);
        $this->set_config_value('sync_requested_at', $state['sync_requested_at']);
        $this->set_config_value('sync_started_at', $state['sync_started_at']);
        $this->set_config_value('sync_request_source', $state['sync_request_source']);
        $this->set_config_value('sync_pending_source', $state['pending_sync_source']);
        $this->set_config_value('last_sync_status', $state['last_sync_status']);
        $this->set_config_value('last_sync_time', $state['last_sync_time']);
    }

    private function is_valid_sync_token()
    {
        $provided = '';
        if (isset($_SERVER['HTTP_X_SIMPLEMDM_API_KEY'])) {
            $provided = trim((string)$_SERVER['HTTP_X_SIMPLEMDM_API_KEY']);
        }

        $stored = $this->get_stored_api_key();
        return $provided !== '' && $stored !== '' && hash_equals($stored, $provided);
    }

    private function is_valid_webhook_secret()
    {
        $provided = '';
        if (isset($_SERVER['HTTP_X_SIMPLEMDM_WEBHOOK_SECRET'])) {
            $provided = trim((string)$_SERVER['HTTP_X_SIMPLEMDM_WEBHOOK_SECRET']);
        } elseif (isset($_SERVER['HTTP_X_WEBHOOK_SECRET'])) {
            $provided = trim((string)$_SERVER['HTTP_X_WEBHOOK_SECRET']);
        }

        $setting = Simplemdm_config_model::where('name', 'webhook_secret')->first();
        $stored = $setting ? trim((string)$setting->value) : '';
        if ($stored === '') {
            return false;
        }
        return $provided !== '' && hash_equals($stored, $provided);
    }

    private function is_valid_client_reporter_secret()
    {
        $provided = '';
        foreach ([
            'HTTP_X_SIMPLEMDM_CLIENT_SECRET',
            'HTTP_X_CLIENT_SECRET',
            'HTTP_X_SIMPLEMDM_CLIENT_REPORTER_SECRET',
        ] as $key) {
            if (isset($_SERVER[$key]) && trim((string) $_SERVER[$key]) !== '') {
                $provided = trim((string) $_SERVER[$key]);
                break;
            }
        }

        $stored = trim($this->get_config_value('client_reporter_secret', ''));
        return $provided !== '' && $stored !== '' && hash_equals($stored, $provided);
    }

    private function client_reporter_signature_header()
    {
        foreach ([
            'HTTP_X_SIMPLEMDM_CLIENT_SIGNATURE',
            'HTTP_X_CLIENT_SIGNATURE',
        ] as $key) {
            if (isset($_SERVER[$key]) && trim((string) $_SERVER[$key]) !== '') {
                $value = trim((string) $_SERVER[$key]);
                if (stripos($value, 'sha256=') === 0) {
                    $value = substr($value, 7);
                }
                return strtolower($value);
            }
        }

        return '';
    }

    private function client_reporter_timestamp_header()
    {
        foreach ([
            'HTTP_X_SIMPLEMDM_CLIENT_TIMESTAMP',
            'HTTP_X_CLIENT_TIMESTAMP',
        ] as $key) {
            if (isset($_SERVER[$key]) && trim((string) $_SERVER[$key]) !== '') {
                return trim((string) $_SERVER[$key]);
            }
        }

        return '';
    }

    private function client_reporter_nonce_header()
    {
        foreach ([
            'HTTP_X_SIMPLEMDM_CLIENT_NONCE',
            'HTTP_X_CLIENT_NONCE',
        ] as $key) {
            if (isset($_SERVER[$key]) && trim((string) $_SERVER[$key]) !== '') {
                return trim((string) $_SERVER[$key]);
            }
        }

        return '';
    }

    private function client_reporter_token_header()
    {
        foreach ([
            'HTTP_X_SIMPLEMDM_CLIENT_TOKEN',
            'HTTP_X_CLIENT_TOKEN',
        ] as $key) {
            if (isset($_SERVER[$key]) && trim((string) $_SERVER[$key]) !== '') {
                return trim((string) $_SERVER[$key]);
            }
        }

        return '';
    }

    private function ip_matches_rule($ip, $rule)
    {
        $ip = trim((string) $ip);
        $rule = trim((string) $rule);
        if ($ip === '' || $rule === '') {
            return false;
        }

        if (strpos($rule, '/') === false) {
            return $ip === $rule;
        }

        [$subnet, $maskBits] = explode('/', $rule, 2);
        if (! is_numeric($maskBits)) {
            return false;
        }

        $ipLong = @inet_pton($ip);
        $subnetLong = @inet_pton($subnet);
        if ($ipLong === false || $subnetLong === false || strlen($ipLong) !== strlen($subnetLong)) {
            return false;
        }

        $maskBits = (int) $maskBits;
        $bytes = strlen($ipLong);
        $maxBits = $bytes * 8;
        if ($maskBits < 0 || $maskBits > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($maskBits, 8);
        $remainingBits = $maskBits % 8;

        if ($fullBytes > 0 && substr($ipLong, 0, $fullBytes) !== substr($subnetLong, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
        return (ord($ipLong[$fullBytes]) & $mask) === (ord($subnetLong[$fullBytes]) & $mask);
    }

    private function ip_matches_any_rule($ip, $rules)
    {
        foreach ((array) $rules as $rule) {
            if ($this->ip_matches_rule($ip, $rule)) {
                return true;
            }
        }

        return false;
    }

    private function resolve_client_reporter_ip()
    {
        $remote_ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $trusted_proxy_rules = $this->client_reporter_ip_rules('client_reporter_trusted_proxy_ips');
        $proxy_only = $this->client_reporter_proxy_only_enabled();
        $via_trusted_proxy = $remote_ip !== '' && $trusted_proxy_rules && $this->ip_matches_any_rule($remote_ip, $trusted_proxy_rules);

        $forwarded_ip = '';
        if ($via_trusted_proxy) {
            $forwarded_for = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
            if ($forwarded_for !== '') {
                $parts = array_map('trim', explode(',', $forwarded_for));
                $forwarded_ip = trim((string) ($parts[0] ?? ''));
            }
            if ($forwarded_ip === '' && isset($_SERVER['HTTP_X_REAL_IP'])) {
                $forwarded_ip = trim((string) $_SERVER['HTTP_X_REAL_IP']);
            }
        }

        if ($proxy_only) {
            if (! $via_trusted_proxy || $forwarded_ip === '') {
                return [
                    'ok' => false,
                    'message' => 'Client reporter ingest requires a trusted proxy with forwarded client IP headers.',
                    'client_ip' => '',
                    'remote_ip' => $remote_ip,
                    'via_proxy' => false,
                ];
            }
        }

        $client_ip = $forwarded_ip !== '' ? $forwarded_ip : $remote_ip;
        $allowlist = $this->client_reporter_ip_rules('client_reporter_ip_allowlist');
        if ($client_ip === '') {
            return [
                'ok' => false,
                'message' => 'Unable to determine client IP for client reporter ingest.',
                'client_ip' => '',
                'remote_ip' => $remote_ip,
                'via_proxy' => $forwarded_ip !== '',
            ];
        }

        if ($allowlist && ! $this->ip_matches_any_rule($client_ip, $allowlist)) {
            return [
                'ok' => false,
                'message' => 'Client reporter ingest is not allowed from this IP.',
                'client_ip' => $client_ip,
                'remote_ip' => $remote_ip,
                'via_proxy' => $forwarded_ip !== '',
            ];
        }

        return [
            'ok' => true,
            'message' => '',
            'client_ip' => $client_ip,
            'remote_ip' => $remote_ip,
            'via_proxy' => $forwarded_ip !== '',
        ];
    }

    private function validate_client_reporter_timestamp_and_hmac($payload)
    {
        $timestamp = $this->client_reporter_timestamp_header();
        $hmac_enabled = $this->client_reporter_hmac_enabled();
        $replay_enabled = $this->client_reporter_replay_protection_enabled();
        if (! $hmac_enabled && ! $replay_enabled) {
            return ['ok' => true, 'timestamp' => '', 'nonce' => ''];
        }

        if ($timestamp === '') {
            return ['ok' => false, 'status' => 401, 'message' => 'Missing client reporter timestamp header.'];
        }

        $timestamp_value = ctype_digit($timestamp) ? (int) $timestamp : strtotime($timestamp);
        if (! $timestamp_value) {
            return ['ok' => false, 'status' => 401, 'message' => 'Invalid client reporter timestamp header.'];
        }

        if (abs(time() - $timestamp_value) > $this->client_reporter_max_time_skew_seconds()) {
            return ['ok' => false, 'status' => 401, 'message' => 'Client reporter timestamp is outside the allowed time skew.'];
        }

        $nonce = $this->client_reporter_nonce_header();
        if ($replay_enabled) {
            if (strlen($nonce) < 8 || strlen($nonce) > 255) {
                return ['ok' => false, 'status' => 401, 'message' => 'Missing or invalid client reporter nonce header.'];
            }
        }

        if ($hmac_enabled) {
            $signature = $this->client_reporter_signature_header();
            if ($signature === '' || ! preg_match('/^[a-f0-9]{64}$/', $signature)) {
                return ['ok' => false, 'status' => 401, 'message' => 'Missing or invalid client reporter HMAC signature.'];
            }

            $secret = trim($this->get_config_value('client_reporter_secret', ''));
            if ($secret === '') {
                return ['ok' => false, 'status' => 409, 'message' => 'Client reporter secret is not configured.'];
            }

            $expected = hash_hmac('sha256', $timestamp . "\n" . $nonce . "\n" . (string) $payload, $secret);
            if (! hash_equals($expected, $signature)) {
                return ['ok' => false, 'status' => 401, 'message' => 'Invalid client reporter HMAC signature.'];
            }
        }

        return [
            'ok' => true,
            'timestamp' => date('Y-m-d H:i:s', $timestamp_value),
            'timestamp_unix' => $timestamp_value,
            'nonce' => $nonce,
        ];
    }

    private function cleanup_client_reporter_nonces()
    {
        if (! $this->has_client_reporter_nonce_table()) {
            return;
        }

        $seconds = max($this->client_reporter_max_time_skew_seconds() * 2, 3600);
        $cutoff = date('Y-m-d H:i:s', time() - $seconds);
        Simplemdm_client_reporter_nonce_model::where('observed_at', '<', $cutoff)->delete();
    }

    private function claim_client_reporter_nonce($nonce, $serial_number, $request_ip, $observed_at)
    {
        if (! $this->client_reporter_replay_protection_enabled()) {
            return ['ok' => true];
        }

        if (! $this->has_client_reporter_nonce_table()) {
            return ['ok' => false, 'status' => 409, 'message' => 'Client reporter nonce table is unavailable. Run migrations first.'];
        }

        $this->cleanup_client_reporter_nonces();

        $nonce_hash = hash('sha256', (string) $nonce);
        if (Simplemdm_client_reporter_nonce_model::where('nonce_hash', $nonce_hash)->exists()) {
            return ['ok' => false, 'status' => 409, 'message' => 'Client reporter nonce has already been used.'];
        }

        $now = date('Y-m-d H:i:s');
        Simplemdm_client_reporter_nonce_model::create([
            'nonce_hash' => $nonce_hash,
            'serial_number' => $serial_number,
            'request_ip' => $request_ip,
            'observed_at' => $observed_at,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return ['ok' => true];
    }

    private function validate_client_reporter_device_token($serial_number)
    {
        if (! $this->client_reporter_per_device_tokens_enabled()) {
            return ['ok' => true];
        }

        if (! $this->has_client_reporter_token_table()) {
            return ['ok' => false, 'status' => 409, 'message' => 'Client reporter token table is unavailable. Run migrations first.'];
        }

        $token = $this->client_reporter_token_header();
        if ($token === '') {
            return ['ok' => false, 'status' => 401, 'message' => 'Missing client reporter device token header.'];
        }

        $token_hash = hash('sha256', $token);
        $record = Simplemdm_client_reporter_token_model::where('serial_number', strtoupper(trim((string) $serial_number)))
            ->where('token_hash', $token_hash)
            ->where('enabled', 1)
            ->first();
        if (! $record) {
            return ['ok' => false, 'status' => 401, 'message' => 'Invalid client reporter device token.'];
        }

        $record->last_used_at = date('Y-m-d H:i:s');
        $record->updated_at = date('Y-m-d H:i:s');
        $record->save();

        return ['ok' => true];
    }

    /**
     * Validate the action secret used for mutating device passthrough routes.
     *
     * @param string $provided
     * @return bool
     **/
    private function is_valid_action_secret($provided = '')
    {
        $provided = trim((string)$provided);
        if ($provided === '') {
            return false;
        }
        $setting = Simplemdm_config_model::where('name', 'action_api_secret')->first();
        $stored = $setting ? trim((string)$setting->value) : '';
        if ($stored === '') {
            return false;
        }
        return hash_equals($stored, $provided);
    }

    /**
     * Return action secret from request headers or payload.
     *
     * @param string $raw_input
     * @param string $content_type
     * @return string
     **/
    private function extract_action_secret_from_request($raw_input = '', $content_type = '')
    {
        $header_keys = [
            'HTTP_X_SIMPLEMDM_ACTION_SECRET',
            'HTTP_X_ACTION_SECRET',
            'HTTP_X_SIMPLEMDM_API_ACTION_SECRET',
        ];
        foreach ($header_keys as $key) {
            if (isset($_SERVER[$key]) && trim((string)$_SERVER[$key]) !== '') {
                return trim((string)$_SERVER[$key]);
            }
        }

        if (isset($_POST['action_secret']) && trim((string)$_POST['action_secret']) !== '') {
            return trim((string)$_POST['action_secret']);
        }
        if (isset($_GET['action_secret']) && trim((string)$_GET['action_secret']) !== '') {
            return trim((string)$_GET['action_secret']);
        }

        if (stripos((string)$content_type, 'application/json') !== false && trim((string)$raw_input) !== '') {
            $decoded = json_decode((string)$raw_input, true);
            if (is_array($decoded) && isset($decoded['action_secret']) && trim((string)$decoded['action_secret']) !== '') {
                return trim((string)$decoded['action_secret']);
            }
        }

        return '';
    }

    /**
     * Remove action_secret from passthrough query/body before sending to SimpleMDM.
     *
     * @param array $query
     * @param string $raw_input
     * @param string $content_type
     * @return array
     **/
    private function sanitize_passthrough_payload($query, $raw_input, $content_type)
    {
        if (! is_array($query)) {
            $query = [];
        }
        unset($query['op'], $query['action_secret']);

        $body = (string)$raw_input;
        if (trim($body) === '') {
            return [$query, $body, $content_type];
        }

        if (stripos((string)$content_type, 'application/json') !== false) {
            $decoded = json_decode($body, true);
            if (is_array($decoded) && array_key_exists('action_secret', $decoded)) {
                unset($decoded['action_secret']);
                $body = json_encode($decoded);
            }
            return [$query, $body, $content_type];
        }

        if (stripos((string)$content_type, 'application/x-www-form-urlencoded') !== false || strpos($body, '=') !== false) {
            $parsed = [];
            parse_str($body, $parsed);
            if (is_array($parsed) && array_key_exists('action_secret', $parsed)) {
                unset($parsed['action_secret']);
                $body = http_build_query($parsed);
            }
            if ($content_type === '') {
                $content_type = 'application/x-www-form-urlencoded';
            }
            return [$query, $body, $content_type];
        }

        return [$query, $body, $content_type];
    }

    /**
     * Ensure current user has global admin authorization.
     *
     * @return bool
     **/
    private function require_global_authorized()
    {
        if (! $this->authorized('global')) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return false;
        }
        return true;
    }

    private function get_script_runner_config()
    {
        $derived_url = rtrim((string) conf('webhost', ''), '/');
        $subdirectory = trim((string) conf('subdirectory', ''), '/');
        if ($derived_url !== '' && $subdirectory !== '') {
            $derived_url .= '/' . $subdirectory;
        }
        $detected_request_url = '';
        if (PHP_SAPI !== 'cli' && isset($_SERVER['HTTP_HOST'])) {
            $detected_request_url = rtrim((SslRequest() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'], '/');
            if ($subdirectory !== '') {
                $detected_request_url .= '/' . $subdirectory;
            }
        }
        $fallback_url = $derived_url;
        $placeholder_hosts = [
            'munkireport.domain.com',
            'example.com',
            'localhost.localdomain',
        ];
        $should_use_request_fallback = false;
        if ($detected_request_url !== '') {
            if ($fallback_url === '') {
                $should_use_request_fallback = true;
            } else {
                $fallback_host = parse_url($fallback_url, PHP_URL_HOST) ?: '';
                $request_host = parse_url($detected_request_url, PHP_URL_HOST) ?: '';
                if ($fallback_host && in_array(strtolower($fallback_host), $placeholder_hosts, true)) {
                    $should_use_request_fallback = true;
                }
                if ($request_host && preg_match('/^(localhost|127\.0\.0\.1)$/i', $request_host)) {
                    $should_use_request_fallback = true;
                }
            }
        }
        if ($should_use_request_fallback) {
            $fallback_url = $detected_request_url;
        }

        $config = [
            'allow_module_script_execution' => $this->get_config_value('allow_module_script_execution', '0'),
            'script_runner_munkireport_url' => $this->get_config_value('script_runner_munkireport_url', $fallback_url),
            'script_runner_python_bin' => $this->get_config_value('script_runner_python_bin', '/usr/bin/python3'),
            'script_runner_schedule' => $this->get_config_value('script_runner_schedule', '* * * * *'),
            'script_runner_log_path' => $this->get_config_value('script_runner_log_path', '/var/log/simplemdm_sync.log'),
            'script_runner_max_parent_resources' => $this->get_config_value('script_runner_max_parent_resources', '25'),
        ];

        if (trim((string) $config['script_runner_python_bin']) === '') {
            $config['script_runner_python_bin'] = '/usr/bin/python3';
        }
        if (trim((string) $config['script_runner_munkireport_url']) === '') {
            $config['script_runner_munkireport_url'] = $fallback_url;
        }
        if (trim((string) $config['script_runner_schedule']) === '') {
            $config['script_runner_schedule'] = '* * * * *';
        }
        if (trim((string) $config['script_runner_log_path']) === '') {
            $config['script_runner_log_path'] = '/var/log/simplemdm_sync.log';
        }

        $max_parent_resources = (int) $config['script_runner_max_parent_resources'];
        if ($max_parent_resources < 0) {
            $max_parent_resources = 0;
        }
        $config['script_runner_max_parent_resources'] = (string) $max_parent_resources;

        return $config;
    }

    private function scripts_dir()
    {
        return $this->module_path . '/scripts';
    }

    private function get_downloadable_script_path($name)
    {
        $name = trim((string) $name);
        if (! in_array($name, $this->downloadable_scripts, true)) {
            return '';
        }

        $path = $this->scripts_dir() . '/' . $name;
        if (! is_file($path)) {
            return '';
        }

        return $path;
    }

    private function build_script_catalog()
    {
        $config = $this->get_script_runner_config();
        $base = rtrim((string) url('module/simplemdm', true), '/');
        $sync_script = $this->scripts_dir() . '/simplemdm_sync.py';
        $install_script = $this->scripts_dir() . '/install_cron.sh';
        $remove_script = $this->scripts_dir() . '/remove_cron.sh';

        $sync_command = sprintf(
            "%s %s --api-key 'YOUR_SIMPLEMDM_API_KEY' --munkireport-url %s --run-source manual_host --force-run --max-parent-resources %s --verbose",
            escapeshellarg($config['script_runner_python_bin']),
            escapeshellarg($sync_script),
            escapeshellarg($config['script_runner_munkireport_url']),
            escapeshellarg($config['script_runner_max_parent_resources'])
        );

        $cron_print_command = sprintf(
            "%s --munkireport-url %s --api-key 'YOUR_SIMPLEMDM_API_KEY' --python-bin %s --schedule %s --log-path %s --max-parent-resources %s --print-only",
            escapeshellarg($install_script),
            escapeshellarg($config['script_runner_munkireport_url']),
            escapeshellarg($config['script_runner_python_bin']),
            escapeshellarg($config['script_runner_schedule']),
            escapeshellarg($config['script_runner_log_path']),
            escapeshellarg($config['script_runner_max_parent_resources'])
        );

        $cron_install_command = preg_replace('/ --print-only$/', ' --install', $cron_print_command);

        return [
            'execution_enabled' => $config['allow_module_script_execution'] === '1',
            'runner_config' => $config,
            'module_download_url' => $base . '/download_module',
            'scripts' => [
                [
                    'id' => 'simplemdm_sync',
                    'name' => 'simplemdm_sync.py',
                    'type' => 'python',
                    'download_url' => $base . '/download_script/simplemdm_sync.py',
                    'external_command' => $sync_command,
                    'run_action' => 'sync_now',
                    'description' => 'Run an immediate sync against this MunkiReport instance.',
                ],
                [
                    'id' => 'install_cron',
                    'name' => 'install_cron.sh',
                    'type' => 'shell',
                    'download_url' => $base . '/download_script/install_cron.sh',
                    'external_command' => $cron_print_command,
                    'run_action' => 'print_cron',
                    'description' => 'Print the cron entry for host-side installation.',
                ],
                [
                    'id' => 'install_cron_apply',
                    'name' => 'install_cron.sh --install',
                    'type' => 'shell',
                    'download_url' => $base . '/download_script/install_cron.sh',
                    'external_command' => $cron_install_command,
                    'run_action' => 'install_cron',
                    'description' => 'Install or update the current user crontab entry.',
                ],
                [
                    'id' => 'remove_cron',
                    'name' => 'remove_cron.sh',
                    'type' => 'shell',
                    'download_url' => $base . '/download_script/remove_cron.sh',
                    'external_command' => escapeshellarg($remove_script),
                    'run_action' => 'remove_cron',
                    'description' => 'Remove cron entries that match the module sync job.',
                ],
            ],
        ];
    }

    private function run_local_script_command($command, $cwd)
    {
        $descriptor_spec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptor_spec, $pipes, $cwd);
        if (! is_resource($process)) {
            return [
                'ok' => false,
                'exit_code' => 1,
                'stdout' => '',
                'stderr' => 'Unable to start process.',
            ];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exit_code = proc_close($process);

        return [
            'ok' => $exit_code === 0,
            'exit_code' => $exit_code,
            'stdout' => (string) $stdout,
            'stderr' => (string) $stderr,
        ];
    }

    private function inspect_cron_state()
    {
        $result = [
            'available' => false,
            'installed' => false,
            'managed_in_module' => $this->get_script_runner_config()['allow_module_script_execution'] === '1',
            'mode' => 'manual_required',
            'message' => 'Cron inspection unavailable.',
        ];

        if (! function_exists('proc_open')) {
            $result['message'] = 'PHP proc_open is disabled, so cron cannot be inspected from the module.';
            return $result;
        }

        $inspect = $this->run_local_script_command('crontab -l', $this->scripts_dir());
        if ($inspect['exit_code'] !== 0 && strpos((string) $inspect['stderr'], 'no crontab for') === false) {
            $stderr = trim((string) $inspect['stderr']);
            if (stripos($stderr, 'crontab: not found') !== false) {
                $result['message'] = 'The munkireport runtime does not include the crontab command, so the module cannot inspect or manage cron inside this container/server. Use host/manual cron or add crontab support to the runtime.';
            } else {
                $result['message'] = 'Unable to inspect current crontab from the module. The runtime may be missing crontab support or permission to read the active crontab.'
                    . ($stderr !== '' ? ' Detail: ' . $stderr : '');
            }
            return $result;
        }

        $result['available'] = true;
        $cron_text = trim((string) $inspect['stdout']);
        $installed = strpos($cron_text, 'simplemdm_sync.py') !== false;
        $result['installed'] = $installed;

        if ($result['managed_in_module']) {
            $result['mode'] = $installed ? 'module_managed_installed' : 'module_managed_not_installed';
            $result['message'] = $installed
                ? 'Cron entry detected and can be managed by the module.'
                : 'In-module execution is enabled, but no cron entry is currently installed.';
        } else {
            $result['mode'] = $installed ? 'manual_installed' : 'manual_required';
            $result['message'] = $installed
                ? 'Cron entry detected. Recurring sync appears to be managed outside the module.'
                : 'Recurring sync requires a manual cron install outside the module.';
        }

        return $result;
    }

    private function inspect_module_runtime()
    {
        $runner = $this->get_script_runner_config();
        $python_binary = trim((string) $runner['script_runner_python_bin']);
        $result = [
            'proc_open_available' => function_exists('proc_open'),
            'python_binary' => $python_binary,
            'python_available' => false,
            'python_path' => '',
            'message' => '',
        ];

        if (! $result['proc_open_available']) {
            $result['message'] = 'PHP proc_open is disabled, so the module cannot inspect or execute Python locally.';
            return $result;
        }

        if ($python_binary === '') {
            $result['message'] = 'No Python binary is configured for module-side execution.';
            return $result;
        }

        $command = sprintf(
            'if command -v %1$s >/dev/null 2>&1; then command -v %1$s; elif test -x %1$s; then printf %%s %1$s; fi',
            escapeshellarg($python_binary)
        );
        $inspect = $this->run_local_script_command('sh -lc ' . escapeshellarg($command), $this->scripts_dir());
        $path = trim((string) $inspect['stdout']);

        if ($inspect['ok'] && $path !== '') {
            $result['python_available'] = true;
            $result['python_path'] = $path;
            $result['message'] = 'Python is available in the module runtime.';
            return $result;
        }

        $result['message'] = 'Python is not available in the module runtime. Use the host/manual workflow unless Python is added to the app container/server.';
        return $result;
    }

    private function create_module_archive()
    {
        if (! class_exists('ZipArchive')) {
            throw new \RuntimeException('ZipArchive is not available on this server.');
        }

        $tmp_path = tempnam(sys_get_temp_dir(), 'simplemdm-module-');
        if ($tmp_path === false) {
            throw new \RuntimeException('Unable to create temporary archive.');
        }
        @unlink($tmp_path);
        $zip_path = $tmp_path . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to open archive for writing.');
        }

        $root_name = 'simplemdm';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->module_path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $full_path = $item->getPathname();
            $relative_path = substr($full_path, strlen($this->module_path) + 1);
            if (! $relative_path) {
                continue;
            }
            if ($relative_path === '.git' || strpos($relative_path, '.git/') === 0) {
                continue;
            }

            $archive_path = $root_name . '/' . str_replace('\\', '/', $relative_path);
            if ($item->isDir()) {
                $zip->addEmptyDir($archive_path);
            } elseif ($item->isFile()) {
                $zip->addFile($full_path, $archive_path);
            }
        }

        $zip->close();
        return $zip_path;
    }

    /**
     * Proxy an HTTP request to SimpleMDM API.
     *
     * @param string $endpoint API endpoint relative to /api/v1
     * @param string $method HTTP method
     * @param array $query query parameters
     * @param string $body request body
     * @param string $content_type request content type
     * @return array
     **/
    private function simplemdm_api_proxy_request($endpoint, $method = 'GET', $query = [], $body = '', $content_type = '')
    {
        $api_key = $this->get_stored_api_key();
        if ($api_key === '') {
            return ['ok' => false, 'status' => 400, 'body' => json_encode(['status' => 'error', 'message' => 'SimpleMDM API key is not configured'])];
        }

        $base = 'https://a.simplemdm.com/api/v1/';
        $endpoint = ltrim((string)$endpoint, '/');
        $url = $base . $endpoint;
        if (is_array($query) && ! empty($query)) {
            $qs = http_build_query($query);
            if ($qs !== '') {
                $url .= (strpos($url, '?') !== false ? '&' : '?') . $qs;
            }
        }

        $method = strtoupper(trim((string)$method));
        if ($method === '') {
            $method = 'GET';
        }

        $headers = [
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($api_key . ':'),
        ];
        if ($content_type !== '') {
            $headers[] = 'Content-Type: ' . $content_type;
        }

        $opts = [
            'http' => [
                'method' => $method,
                'ignore_errors' => true,
                'timeout' => 45,
                'header' => implode("\r\n", $headers),
            ],
        ];
        if ($body !== '' && ! in_array($method, ['GET', 'HEAD'], true)) {
            $opts['http']['content'] = $body;
        }

        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);

        $status = 500;
        $response_headers = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : [];
        foreach ($response_headers as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#i', (string)$line, $m)) {
                $status = (int)$m[1];
                break;
            }
        }

        if ($response === false) {
            $response = '';
        }

        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'body' => $response,
            'headers' => $response_headers,
        ];
    }

    /**
     * Return whitelist rules for /devices passthrough.
     *
     * @return array
     **/
    private function simplemdm_device_passthrough_rules()
    {
        return [
            '' => ['GET', 'POST'],
            ':id' => ['GET', 'PATCH', 'DELETE'],
            'profiles' => ['GET'],
            'installed_apps' => ['GET'],
            'users' => ['GET'],
            'users/:id' => ['DELETE'],
            'push_apps' => ['POST'],
            'refresh' => ['POST'],
            'restart' => ['POST'],
            'shutdown' => ['POST'],
            'lock' => ['POST'],
            'clear_passcode' => ['POST'],
            'clear_firmware_password' => ['POST'],
            'rotate_firmware_password' => ['POST'],
            'clear_recovery_lock_password' => ['POST'],
            'clear_restrictions_password' => ['POST'],
            'rotate_recovery_lock_password' => ['POST'],
            'rotate_filevault_key' => ['POST'],
            'set_admin_password' => ['POST'],
            'rotate_admin_password' => ['POST'],
            'wipe' => ['POST'],
            'update_os' => ['POST'],
            'remote_desktop' => ['POST', 'DELETE'],
            'bluetooth' => ['POST', 'DELETE'],
            'set_time_zone' => ['POST'],
            'unenroll' => ['POST'],
        ];
    }

    /**
     * Build allowed method list for a /devices request pattern.
     *
     * @param string $device_id
     * @param string $subpath
     * @param string $subpath_id
     * @return array
     **/
    private function simplemdm_allowed_methods_for_device_path($device_id, $subpath, $subpath_id)
    {
        $rules = $this->simplemdm_device_passthrough_rules();
        $device_id = trim((string)$device_id);
        $subpath = trim((string)$subpath);
        $subpath_id = trim((string)$subpath_id);

        if ($device_id === '') {
            return isset($rules['']) ? $rules[''] : [];
        }
        if ($subpath === '') {
            return isset($rules[':id']) ? $rules[':id'] : [];
        }
        if ($subpath === 'users' && $subpath_id !== '') {
            return isset($rules['users/:id']) ? $rules['users/:id'] : [];
        }
        return isset($rules[$subpath]) ? $rules[$subpath] : [];
    }

    /**
     * Default method
     *
     **/
    function index()
    {
        $op = isset($_GET['op']) ? trim((string)$_GET['op']) : '';
        if ($op && in_array($op, $this->sync_actions, true)) {
            if ($op === 'get_config') {
                $this->get_config();
                return;
            }
            if ($op === 'ingest') {
                $this->ingest();
                return;
            }
            if ($op === 'ingest_resources') {
                $this->ingest_resources();
                return;
            }
            if ($op === 'update_sync_status') {
                $this->update_sync_status();
                return;
            }
            if ($op === 'begin_sync_run') {
                $this->begin_sync_run();
                return;
            }
            if ($op === 'ingest_commands') {
                $this->ingest_commands();
                return;
            }
            if ($op === 'ingest_client_facts') {
                $this->ingest_client_facts();
                return;
            }
            if ($op === 'webhook') {
                $this->webhook();
                return;
            }
        }

        echo "You've loaded the SimpleMDM module!";
    }

    /**
     * Retrieve enrollment status statistics in JSON format.
     * Returns counts of enrolled vs unenrolled devices.
     *
     * @return void
     **/
    public function get_enrollment_stats()
    {
        jsonView(
            Simplemdm_model::selectRaw("COUNT(CASE WHEN `status` = 'enrolled' THEN 1 END) AS 'enrolled'")
                ->selectRaw("COUNT(CASE WHEN `status` != 'enrolled' OR `status` IS NULL THEN 1 END) AS 'unenrolled'")
                ->first()
                ->toLabelCount()
        );
    }

    /**
     * Retrieve DEP enrollment statistics in JSON format.
     *
     * @return void
     **/
    public function get_dep_stats()
    {
        jsonView(
            Simplemdm_model::selectRaw("COUNT(CASE WHEN `is_dep_enrollment` = 1 THEN 1 END) AS 'dep_enrolled'")
                ->selectRaw("COUNT(CASE WHEN `is_dep_enrollment` = 0 OR `is_dep_enrollment` IS NULL THEN 1 END) AS 'not_dep'")
                ->first()
                ->toLabelCount()
        );
    }

    /**
     * Retrieve FileVault statistics in JSON format.
     *
     * @return void
     **/
    public function get_filevault_stats()
    {
        jsonView(
            Simplemdm_model::selectRaw("COUNT(CASE WHEN `filevault_enabled` = 1 THEN 1 END) AS 'enabled'")
                ->selectRaw("COUNT(CASE WHEN `filevault_enabled` = 0 OR `filevault_enabled` IS NULL THEN 1 END) AS 'disabled'")
                ->first()
                ->toLabelCount()
        );
    }

    /**
     * Retrieve supervised device statistics in JSON format.
     *
     * @return void
     **/
    public function get_supervised_stats()
    {
        jsonView(
            Simplemdm_model::selectRaw("COUNT(CASE WHEN `is_supervised` = 1 THEN 1 END) AS 'supervised'")
                ->selectRaw("COUNT(CASE WHEN `is_supervised` = 0 OR `is_supervised` IS NULL THEN 1 END) AS 'unsupervised'")
                ->first()
                ->toLabelCount()
        );
    }

    /**
     * Retrieve all SimpleMDM data for a specific device.
     *
     * @param string $serial_number The device serial number
     * @return void
     **/
    public function get_simplemdm_data($serial_number = '')
    {
        jsonView(
            Simplemdm_model::select()
                ->where('simplemdm.serial_number', $serial_number)
                ->get()
                ->toArray()
        );
    }

    public function get_supplemental_data($serial_number = '')
    {
        $this->connectDB();

        $serial_number = trim((string) $serial_number);
        if ($serial_number === '') {
            jsonView(['status' => 'error', 'message' => 'Missing serial number'], 400);
            return;
        }

        $device = Simplemdm_model::where('serial_number', $serial_number)->first();
        if (! $device) {
            jsonView(['status' => 'error', 'message' => 'Device not found'], 404);
            return;
        }

        if (! $this->supplemental_enabled()) {
            jsonView([
                'status' => 'success',
                'serial_number' => $serial_number,
                'enabled' => false,
                'detected_sources' => array_values($this->get_detected_supplemental_sources()),
                'summary' => null,
                'sources' => [],
            ]);
            return;
        }

        $summary_row = $this->maybe_refresh_supplemental_summary_for_serial($serial_number);
        $summary = $this->serialize_supplemental_summary_row($summary_row);
        $summary_refresh = $summary['last_refresh'] ?? null;
        $refresh_status = $summary['last_refresh_status'] ?? 'success';
        $sources = [];

        foreach (array_keys($this->supplemental_registry()) as $source_id) {
            $sources[] = $this->build_supplemental_source_payload($source_id, $serial_number, $summary_refresh, $refresh_status);
        }
        $sources[] = $this->build_client_reporter_payload($serial_number);

        jsonView([
            'status' => 'success',
            'serial_number' => $serial_number,
            'enabled' => true,
            'stale_after_minutes' => $this->supplemental_stale_after_minutes(),
            'detected_sources' => array_values($this->get_detected_supplemental_sources()),
            'summary' => $summary,
            'sources' => $sources,
        ]);
    }

    public function get_supplemental_status()
    {
        $this->connectDB();
        if (! $this->require_global_authorized()) {
            return;
        }

        $detected_sources = array_values($this->get_detected_supplemental_sources());
        $detected_sources[] = [
            'source_id' => 'client_reporter',
            'label' => 'Client Reporter',
            'table' => 'simplemdm_client_fact',
            'detected' => $this->has_client_fact_table(),
            'enabled' => $this->client_reporter_enabled(),
            'reason' => $this->client_reporter_enabled() ? ($this->has_client_fact_table() ? 'ok' : 'table_missing') : 'client_reporter_disabled',
            'has_data' => $this->has_client_fact_table() ? (Simplemdm_client_fact_model::limit(1)->count() > 0) : false,
        ];
        $summary_count = $this->has_supplemental_summary_table()
            ? (int) Simplemdm_supplemental_summary_model::count()
            : 0;
        $last_row = $this->has_supplemental_summary_table()
            ? Simplemdm_supplemental_summary_model::orderBy('last_refresh', 'desc')->first()
            : null;
        $freshness_counts = [
            'fresh' => 0,
            'stale' => 0,
            'missing' => 0,
            'module_not_detected' => 0,
            'refresh_failed' => 0,
        ];
        $source_health = [];

        if ($this->has_supplemental_summary_table()) {
            foreach (Simplemdm_supplemental_summary_model::select('source_freshness_json', 'last_refresh_status')->get() as $row) {
                $source_freshness = json_decode((string) $row->source_freshness_json, true);
                if (! is_array($source_freshness)) {
                    continue;
                }
                foreach ($source_freshness as $source_id => $meta) {
                    $state = isset($meta['state']) ? (string) $meta['state'] : 'missing';
                    if (! isset($freshness_counts[$state])) {
                        $freshness_counts[$state] = 0;
                    }
                    $freshness_counts[$state]++;

                    if (! isset($source_health[$source_id])) {
                        $source_health[$source_id] = [
                            'fresh' => 0,
                            'stale' => 0,
                            'missing' => 0,
                            'module_not_detected' => 0,
                            'refresh_failed' => 0,
                        ];
                    }
                    if (! isset($source_health[$source_id][$state])) {
                        $source_health[$source_id][$state] = 0;
                    }
                    $source_health[$source_id][$state]++;
                }
            }
        }

        jsonView([
            'status' => 'success',
            'enabled' => $this->supplemental_enabled(),
            'client_reporter_enabled' => $this->client_reporter_enabled(),
            'disabled_source_ids' => $this->supplemental_disabled_source_ids(),
            'stale_after_minutes' => $this->supplemental_stale_after_minutes(),
            'detected_sources' => $detected_sources,
            'summary_row_count' => $summary_count,
            'last_summary_refresh' => $last_row ? $this->format_sync_datetime($last_row->last_refresh) : '',
            'last_summary_status' => $last_row ? (string) $last_row->last_refresh_status : '',
            'freshness_counts' => $freshness_counts,
            'source_health' => $source_health,
            'client_fact_count' => $this->has_client_fact_table() ? (int) Simplemdm_client_fact_model::count() : 0,
            'client_fact_history_count' => $this->has_client_fact_history_table() ? (int) Simplemdm_client_fact_history_model::count() : 0,
        ]);
    }

    public function get_client_facts($serial_number = '')
    {
        $this->connectDB();
        if (! $this->require_global_authorized()) {
            return;
        }

        if (! $this->has_client_fact_table()) {
            jsonView([
                'status' => 'success',
                'serial_number' => trim((string) $serial_number),
                'facts' => [],
                'history_count' => 0,
            ]);
            return;
        }

        $serial_number = trim((string) $serial_number);
        $query = Simplemdm_client_fact_model::query()->orderBy('serial_number', 'asc')->orderBy('fact_key', 'asc');
        if ($serial_number !== '') {
            $query->where('serial_number', $serial_number);
        }

        $facts = [];
        foreach ($query->get() as $row) {
            $facts[] = [
                'serial_number' => (string) $row->serial_number,
                'fact_type' => (string) $row->fact_type,
                'fact_key' => (string) $row->fact_key,
                'fact_value' => $this->format_client_fact_value_for_output($row),
                'reported_at' => $this->format_sync_datetime($row->reported_at),
                'source' => (string) $row->source,
                'client_version' => (string) $row->client_version,
            ];
        }

        $history_count = 0;
        if ($this->has_client_fact_history_table()) {
            $history_query = Simplemdm_client_fact_history_model::query();
            if ($serial_number !== '') {
                $history_query->where('serial_number', $serial_number);
            }
            $history_count = (int) $history_query->count();
        }

        jsonView([
            'status' => 'success',
            'serial_number' => $serial_number,
            'facts' => $facts,
            'history_count' => $history_count,
        ]);
    }

    public function refresh_supplemental_summary($serial_number = '')
    {
        $this->connectDB();
        if (! $this->require_global_authorized()) {
            return;
        }

        if (! $this->has_supplemental_summary_table()) {
            jsonView(['status' => 'error', 'message' => 'Supplemental summary table is not available. Run migrations first.'], 409);
            return;
        }

        $serial_number = trim((string) $serial_number);
        $query = Simplemdm_model::query();
        if ($serial_number !== '') {
            $query->where('serial_number', $serial_number);
        }

        $serials = $query->pluck('serial_number')->toArray();
        $count = 0;
        foreach ($serials as $serial) {
            if ($this->refresh_supplemental_summary_for_serial($serial)) {
                $count++;
            }
        }

        jsonView([
            'status' => 'success',
            'refreshed' => $count,
            'serial_number' => $serial_number,
            'detected_sources' => array_values($this->get_detected_supplemental_sources()),
        ]);
    }


    /**
     * Show the admin page.
     *
     * @return void
     **/
    public function admin()
    {
        $obj = new View();
        $obj->view('simplemdm_admin', [], $this->module_path . '/views/');
    }

    /**
     * Show SimpleMDM-only device detail page (works even when no MunkiReport client exists).
     *
     * @param string $serial_number
     * @return void
     **/
    public function device($serial_number = '')
    {
        $obj = new View();
        $obj->view('simplemdm_device', ['serial_number' => $serial_number], $this->module_path . '/views/');
    }

    /**
     * Retrieve configuration in JSON format.
     *
     * @return void
     **/
    public function get_config()
    {
        $is_sync_auth = $this->is_valid_sync_token();
        $config = [];
        $is_global = $this->authorized('global');
        if (! $is_global && ! $is_sync_auth) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }
        $settings = Simplemdm_config_model::all();
        foreach ($settings as $setting) {
            if ($setting->name === 'api_key') {
                $config['api_key_set'] = trim((string)$setting->value) !== '' ? '1' : '0';
                if (! $is_global) {
                    continue;
                }
            }
            if ($setting->name === 'webhook_secret' && ! $is_global) {
                $config['webhook_secret_set'] = trim((string)$setting->value) !== '' ? '1' : '0';
                continue;
            }
            if ($setting->name === 'action_api_secret' && ! $is_global) {
                $config['action_api_secret_set'] = trim((string)$setting->value) !== '' ? '1' : '0';
                continue;
            }
            if ($setting->name === 'client_reporter_secret' && ! $is_global) {
                $config['client_reporter_secret_set'] = trim((string)$setting->value) !== '' ? '1' : '0';
                continue;
            }
            $config[$setting->name] = $setting->value;
        }

        if (! isset($config['supplemental_enabled'])) {
            $config['supplemental_enabled'] = '1';
        }
        if (! isset($config['supplemental_disabled_sources_json'])) {
            $config['supplemental_disabled_sources_json'] = '[]';
        }
        if (! isset($config['supplemental_default_stale_after_minutes'])) {
            $config['supplemental_default_stale_after_minutes'] = '1440';
        }
        foreach ([
            'client_reporter_enabled' => '0',
            'client_reporter_secret' => '',
            'client_reporter_history_enabled' => '1',
            'client_reporter_max_payload_bytes' => '16384',
            'client_reporter_allowed_fact_keys_json' => json_encode($this->client_reporter_allowed_fact_keys()),
            'client_reporter_hmac_enabled' => '0',
            'client_reporter_replay_protection_enabled' => '0',
            'client_reporter_per_device_tokens_enabled' => '0',
            'client_reporter_ip_allowlist' => '',
            'client_reporter_proxy_only_enabled' => '0',
            'client_reporter_trusted_proxy_ips' => '',
            'client_reporter_max_time_skew_seconds' => '300',
        ] as $key => $value) {
            if (! isset($config[$key])) {
                $config[$key] = $value;
            }
        }
        if (! isset($config['event_stale_threshold_hours'])) {
            $config['event_stale_threshold_hours'] = '168';
        }
        if (! isset($config['event_builtin_settings_json'])) {
            $config['event_builtin_settings_json'] = json_encode($this->built_in_event_settings());
        }
        if (! isset($config['custom_event_rules_json'])) {
            $config['custom_event_rules_json'] = json_encode($this->custom_event_rules());
        }
        $config['event_builtin_catalog_json'] = json_encode($this->built_in_event_catalog());
        $config['custom_event_field_catalog_json'] = json_encode($this->custom_event_field_catalog());
        $config['client_reporter_device_tokens_json'] = '';
        $config['client_reporter_device_token_metadata_json'] = $is_global
            ? json_encode($this->client_reporter_token_metadata())
            : '[]';

        // Normalize runner settings so blank stored values still use derived defaults.
        $runner_config = $this->get_script_runner_config();
        foreach ($runner_config as $key => $value) {
            if (! isset($config[$key]) || trim((string) $config[$key]) === '') {
                $config[$key] = $value;
            }
        }

        foreach ($this->get_widget_config_keys() as $key) {
            if (! isset($config[$key])) {
                $config[$key] = '1';
            }
        }

        $run_state = $this->derive_sync_state_from_runs();
        foreach ($run_state as $key => $value) {
            $config[$key] = $value;
        }

        jsonView($config);
    }

    public function get_script_catalog()
    {
        if (! $this->require_global_authorized()) {
            return;
        }

        jsonView($this->build_script_catalog());
    }

    public function get_runner_status()
    {
        if (! $this->require_global_authorized()) {
            return;
        }

        $runner = $this->get_script_runner_config();
        $cron = $this->inspect_cron_state();
        $runtime = $this->inspect_module_runtime();

        jsonView([
            'runner' => $runner,
            'cron' => $cron,
            'runtime' => $runtime,
        ]);
    }

    /**
     * Save configuration.
     *
     * @return void
     **/
    public function save_config()
    {
        $this->connectDB();
        $is_sync_auth = $this->is_valid_sync_token();
        if (! $is_sync_auth && ! $this->authorized('global')) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        $post = $_POST;
        $updated = false;

        if (array_key_exists('api_key', $post)) {
            Simplemdm_config_model::updateOrCreate(
                ['name' => 'api_key'],
                ['value' => $post['api_key']]
            );
            $updated = true;
        }

        $config_keys = [
            'webhook_secret',
            'action_api_secret',
            'compliance_min_os',
            'enable_scheduled_sync',
            'sync_interval_minutes',
            'sync_delta_enabled',
            'sync_commands_enabled',
            'sync_device_subresources_enabled',
            'device_subresource_limit',
            'last_sync_cursor',
            'sync_last_duration_ms',
            'sync_last_api_requests',
            'sync_last_api_errors',
            'sync_last_api_error_details',
            'sync_last_rate_limit_hits',
            'sync_last_delta_mode',
            'sync_last_scope',
            'allow_module_script_execution',
            'script_runner_munkireport_url',
            'script_runner_python_bin',
            'script_runner_schedule',
            'script_runner_log_path',
            'script_runner_max_parent_resources',
            'supplemental_enabled',
            'supplemental_disabled_sources_json',
            'supplemental_default_stale_after_minutes',
            'supplemental_registry_json',
            'client_reporter_enabled',
            'client_reporter_secret',
            'client_reporter_history_enabled',
            'client_reporter_max_payload_bytes',
            'client_reporter_allowed_fact_keys_json',
            'client_reporter_hmac_enabled',
            'client_reporter_replay_protection_enabled',
            'client_reporter_per_device_tokens_enabled',
            'client_reporter_ip_allowlist',
            'client_reporter_proxy_only_enabled',
            'client_reporter_trusted_proxy_ips',
            'client_reporter_max_time_skew_seconds',
            'event_stale_threshold_hours',
            'event_builtin_settings_json',
            'custom_event_rules_json',
        ];
        foreach ($config_keys as $key) {
            if (array_key_exists($key, $post)) {
                $value = (string)$post[$key];
                if (
                    $key === 'sync_delta_enabled'
                    || $key === 'sync_last_delta_mode'
                    || $key === 'sync_commands_enabled'
                    || $key === 'enable_scheduled_sync'
                    || $key === 'sync_device_subresources_enabled'
                    || $key === 'allow_module_script_execution'
                    || $key === 'supplemental_enabled'
                    || $key === 'client_reporter_enabled'
                    || $key === 'client_reporter_history_enabled'
                    || $key === 'client_reporter_hmac_enabled'
                    || $key === 'client_reporter_replay_protection_enabled'
                    || $key === 'client_reporter_per_device_tokens_enabled'
                    || $key === 'client_reporter_proxy_only_enabled'
                ) {
                    $value = $value === '1' ? '1' : '0';
                } elseif ($key === 'device_subresource_limit') {
                    $v = (int)$value;
                    if ($v < 0) {
                        $v = 0;
                    }
                    $value = (string)$v;
                } elseif ($key === 'sync_interval_minutes') {
                    $v = (int)$value;
                    if ($v < 1) {
                        $v = 1;
                    }
                    $value = (string)$v;
                } elseif ($key === 'script_runner_max_parent_resources') {
                    $v = (int)$value;
                    if ($v < 0) {
                        $v = 0;
                    }
                    $value = (string)$v;
                } elseif ($key === 'supplemental_default_stale_after_minutes') {
                    $v = (int)$value;
                    if ($v < 1) {
                        $v = 1;
                    }
                    $value = (string)$v;
                } elseif ($key === 'supplemental_registry_json') {
                    $value = trim($value);
                    if ($value !== '') {
                        $decoded = json_decode($value, true);
                        if (! is_array($decoded)) {
                            jsonView(['status' => 'error', 'message' => 'Supplemental registry overrides must be valid JSON object syntax.'], 400);
                            return;
                        }
                    }
                } elseif ($key === 'supplemental_disabled_sources_json') {
                    $value = trim($value);
                    if ($value === '') {
                        $value = '[]';
                    }
                    $decoded = json_decode($value, true);
                    if (! is_array($decoded)) {
                        jsonView(['status' => 'error', 'message' => 'Supplemental disabled sources must be a JSON array of source ids.'], 400);
                        return;
                    }
                    $normalized = [];
                    foreach ($decoded as $source_id) {
                        $source_id = trim((string) $source_id);
                        if ($source_id !== '') {
                            $normalized[$source_id] = $source_id;
                        }
                    }
                    $value = json_encode(array_values($normalized));
                } elseif ($key === 'client_reporter_max_payload_bytes') {
                    $v = (int) $value;
                    if ($v < 1024) {
                        $v = 1024;
                    }
                    $value = (string) $v;
                } elseif ($key === 'client_reporter_max_time_skew_seconds') {
                    $v = (int) $value;
                    if ($v < 30) {
                        $v = 30;
                    }
                    $value = (string) $v;
                } elseif ($key === 'event_stale_threshold_hours') {
                    $v = (int) $value;
                    if ($v < 1) {
                        $v = 168;
                    }
                    $value = (string) $v;
                } elseif ($key === 'event_builtin_settings_json') {
                    $decoded = json_decode(trim($value) === '' ? '{}' : $value, true);
                    if (! is_array($decoded)) {
                        jsonView(['status' => 'error', 'message' => 'Built-in event settings must be a JSON object.'], 400);
                        return;
                    }
                    $normalized = [];
                    foreach ($this->built_in_event_catalog() as $suffix => $_meta) {
                        $normalized[$suffix] = array_key_exists($suffix, $decoded) && (string) $decoded[$suffix] === '0' ? '0' : '1';
                    }
                    $value = json_encode($normalized);
                } elseif ($key === 'custom_event_rules_json') {
                    try {
                        $value = json_encode($this->normalize_custom_event_rules($value));
                    } catch (\RuntimeException $e) {
                        jsonView(['status' => 'error', 'message' => $e->getMessage()], 400);
                        return;
                    }
                } elseif ($key === 'client_reporter_allowed_fact_keys_json') {
                    $value = trim($value);
                    if ($value === '') {
                        $value = json_encode($this->client_reporter_allowed_fact_keys());
                    } else {
                        $decoded = json_decode($value, true);
                        if (! is_array($decoded)) {
                            jsonView(['status' => 'error', 'message' => 'Client reporter allowlist must be a JSON array of fact keys.'], 400);
                            return;
                        }
                    }
                } elseif ($key === 'client_reporter_ip_allowlist' || $key === 'client_reporter_trusted_proxy_ips') {
                    $value = trim($value);
                }
                Simplemdm_config_model::updateOrCreate(
                    ['name' => $key],
                    ['value' => $value]
                );
                $updated = true;
            }
        }

        $token_count = null;
        if (array_key_exists('client_reporter_device_tokens_json', $post)) {
            if (! $this->authorized('global')) {
                jsonView(['status' => 'error', 'message' => 'Only global admins can manage client reporter device tokens.'], 403);
                return;
            }
            try {
                $token_count = $this->sync_client_reporter_tokens((string) $post['client_reporter_device_tokens_json']);
                if ($token_count !== null) {
                    $updated = true;
                }
            } catch (\RuntimeException $e) {
                jsonView(['status' => 'error', 'message' => $e->getMessage()], 400);
                return;
            }
        }

        $widget_keys = $this->get_widget_config_keys();
        foreach ($widget_keys as $key) {
            if (array_key_exists($key, $post)) {
                $value = (string)$post[$key] === '1' ? '1' : '0';
                Simplemdm_config_model::updateOrCreate(
                    ['name' => $key],
                    ['value' => $value]
                );
                $updated = true;
            }
        }

        if (isset($post['last_sync_status']) || isset($post['last_sync_time'])) {
            if (isset($post['last_sync_status'])) {
                Simplemdm_config_model::updateOrCreate(
                    ['name' => 'last_sync_status'],
                    ['value' => $post['last_sync_status']]
                );
            }
            if (isset($post['last_sync_time'])) {
                Simplemdm_config_model::updateOrCreate(
                    ['name' => 'last_sync_time'],
                    ['value' => $post['last_sync_time']]
                );
            }
            $updated = true;
        }

        if ($updated) {
            jsonView(['status' => 'success']);
            return;
        }

        jsonView(['status' => 'error', 'message' => 'Invalid data']);
    }

    public function download_script($name = '')
    {
        if (! $this->require_global_authorized()) {
            return;
        }

        $path = $this->get_downloadable_script_path($name);
        if ($path === '') {
            jsonView(['status' => 'error', 'message' => 'Unknown script'], 404);
            return;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        readfile($path);
        exit;
    }

    public function download_module()
    {
        if (! $this->require_global_authorized()) {
            return;
        }

        try {
            $zip_path = $this->create_module_archive();
        } catch (\Throwable $e) {
            jsonView(['status' => 'error', 'message' => $e->getMessage()], 500);
            return;
        }

        header('Content-Type: application/zip');
        header('Content-Length: ' . filesize($zip_path));
        header('Content-Disposition: attachment; filename="simplemdm-module.zip"');
        readfile($zip_path);
        @unlink($zip_path);
        exit;
    }

    public function run_script()
    {
        $this->connectDB();
        if (! $this->require_global_authorized()) {
            return;
        }

        $runner = $this->get_script_runner_config();
        if ($runner['allow_module_script_execution'] !== '1') {
            jsonView([
                'status' => 'error',
                'message' => 'Module-side script execution is disabled. Enable it in Script Runner settings first.',
            ], 403);
            return;
        }

        $action = trim((string) post('action'));
        $cwd = $this->scripts_dir();
        $python = escapeshellarg($runner['script_runner_python_bin']);
        $sync_script = escapeshellarg($cwd . '/simplemdm_sync.py');
        $install_script = escapeshellarg($cwd . '/install_cron.sh');
        $remove_script = escapeshellarg($cwd . '/remove_cron.sh');
        $mr_url = escapeshellarg($runner['script_runner_munkireport_url']);
        $api_key = escapeshellarg($this->get_stored_api_key());
        $schedule = escapeshellarg($runner['script_runner_schedule']);
        $log_path = escapeshellarg($runner['script_runner_log_path']);
        $max_parent_resources = escapeshellarg($runner['script_runner_max_parent_resources']);
        $runtime = $this->inspect_module_runtime();

        $missing = [];
        if ($this->get_stored_api_key() === '') {
            $missing[] = 'SimpleMDM API key';
        }
        if (trim((string) $runner['script_runner_munkireport_url']) === '') {
            $missing[] = 'Runner MunkiReport URL';
        }
        if (trim((string) $runner['script_runner_python_bin']) === '') {
            $missing[] = 'Configured Python Path';
        }
        if (trim((string) $runner['script_runner_max_parent_resources']) === '') {
            $missing[] = 'Max Parent Resources';
        }
        if (in_array($action, ['print_cron', 'install_cron'], true)) {
            if (trim((string) $runner['script_runner_schedule']) === '') {
                $missing[] = 'Schedule';
            }
            if (trim((string) $runner['script_runner_log_path']) === '') {
                $missing[] = 'Cron Log Path';
            }
        }
        if (in_array($action, ['sync_now', 'install_cron'], true) && ! $runtime['python_available']) {
            $missing[] = 'Module Python';
        }
        if ($missing) {
            $message = 'Cannot run this action until the required runner settings are available: ' . implode(', ', array_unique($missing)) . '.';
            if (in_array('Module Python', $missing, true) && trim((string) $runtime['message']) !== '') {
                $message .= ' ' . trim((string) $runtime['message']);
            }
            jsonView([
                'status' => 'error',
                'message' => $message,
            ], 400);
            return;
        }

        $commands = [
            'sync_now' => sprintf(
                "%s %s --api-key %s --munkireport-url %s --run-source in_module_immediate --force-run --max-parent-resources %s --verbose",
                $python,
                $sync_script,
                $api_key,
                $mr_url,
                $max_parent_resources
            ),
            'print_cron' => sprintf(
                "%s --munkireport-url %s --api-key %s --python-bin %s --schedule %s --log-path %s --max-parent-resources %s --print-only",
                $install_script,
                $mr_url,
                $api_key,
                $python,
                $schedule,
                $log_path,
                $max_parent_resources
            ),
            'install_cron' => sprintf(
                "%s --munkireport-url %s --api-key %s --python-bin %s --schedule %s --log-path %s --max-parent-resources %s --install",
                $install_script,
                $mr_url,
                $api_key,
                $python,
                $schedule,
                $log_path,
                $max_parent_resources
            ),
            'remove_cron' => $remove_script,
        ];

        if (! isset($commands[$action])) {
            jsonView(['status' => 'error', 'message' => 'Unsupported script action'], 400);
            return;
        }

        $result = $this->run_local_script_command($commands[$action], $cwd);
        if ($action === 'sync_now') {
            $this->refresh_legacy_sync_config_from_runs();
        }
        jsonView([
            'status' => $result['ok'] ? 'success' : 'error',
            'action' => $action,
            'command' => $commands[$action],
            'exit_code' => $result['exit_code'],
            'stdout' => $result['stdout'],
            'stderr' => $result['stderr'],
        ], $result['ok'] ? 200 : 500);
    }

    /**
     * Queue a sync request from the admin UI.
     *
     * @return void
     **/
    public function request_sync()
    {
        $this->connectDB();
        if (! $this->require_global_authorized()) {
            return;
        }

        $api_key = $this->get_config_value('api_key');
        if (trim($api_key) === '') {
            jsonView(['status' => 'error', 'message' => 'Configure the SimpleMDM API key first.'], 400);
            return;
        }

        $state = strtolower(trim($this->get_config_value('sync_request_state', 'idle')));
        if ($this->get_running_sync_run() || $state === 'running') {
            jsonView(['status' => 'success', 'message' => 'A sync is already running.', 'sync_request_state' => 'running']);
            return;
        }

        $queued = $this->get_queued_sync_run();
        if ($queued) {
            $this->refresh_legacy_sync_config_from_runs();
            jsonView([
                'status' => 'success',
                'message' => 'A queued sync request already exists and is waiting for pickup.',
                'sync_request_state' => 'queued',
                'sync_requested_at' => $this->format_sync_datetime($queued->requested_at),
            ]);
            return;
        }

        $requested_at = date('c');
        $run = $this->create_sync_run([
            'source' => 'queued_admin',
            'status' => 'queued',
            'requested_at' => $this->normalize_sync_datetime($requested_at) ?: date('Y-m-d H:i:s'),
            'requested_by' => $this->get_current_actor_label(),
            'trigger_context' => 'admin_ui',
        ]);
        if (! $run) {
            $this->set_config_value('sync_request_state', 'queued');
            $this->set_config_value('sync_requested_at', $requested_at);
            $this->set_config_value('sync_pending_source', 'queued_admin');
        }
        $this->refresh_legacy_sync_config_from_runs();

        jsonView([
            'status' => 'success',
            'message' => 'Sync queued. The next cron or manual runner execution will pick it up.',
            'sync_request_state' => 'queued',
            'sync_requested_at' => $requested_at,
            'run_uuid' => $run ? (string) $run->run_uuid : '',
        ]);
    }

    public function clear_sync_runs()
    {
        $this->connectDB();
        if (! $this->require_global_authorized()) {
            return;
        }

        if ($this->get_running_sync_run() || $this->get_queued_sync_run()) {
            jsonView([
                'status' => 'error',
                'message' => 'Cannot clear run history while a sync is queued or running.',
            ], 409);
            return;
        }

        if ($this->has_sync_runs_table()) {
            Simplemdm_sync_run_model::query()->delete();
        }

        foreach ([
            'sync_request_state' => 'idle',
            'sync_requested_at' => '',
            'sync_started_at' => '',
            'sync_request_source' => '',
            'sync_pending_source' => '',
            'last_sync_status' => '',
            'last_sync_time' => '',
            'sync_last_duration_ms' => '0',
            'sync_last_api_requests' => '0',
            'sync_last_api_errors' => '0',
            'sync_last_rate_limit_hits' => '0',
            'sync_last_delta_mode' => '0',
            'sync_last_scope' => '',
        ] as $key => $value) {
            $this->set_config_value($key, $value);
        }

        jsonView([
            'status' => 'success',
            'message' => 'Run history cleared.',
        ]);
    }

    /**
     * Claim a queued or scheduled sync run for the Python worker.
     *
     * @return void
     **/
    public function begin_sync_run()
    {
        $this->connectDB();
        if (! $this->is_valid_sync_token()) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        $state = strtolower(trim($this->get_config_value('sync_request_state', 'idle')));
        if ($state === 'running') {
            jsonView(['status' => 'busy', 'message' => 'A sync is already running.'], 409);
            return;
        }

        $started_at = date('c');
        $run_uuid = '';
        if ($this->has_sync_runs_table()) {
            $run_source = strtolower(trim((string) post('run_source')));
            $run = $this->get_queued_sync_run();
            if ($run) {
                $run->status = 'running';
                $run->started_at = $this->normalize_sync_datetime($started_at) ?: date('Y-m-d H:i:s');
                if (! trim((string) $run->source)) {
                    $run->source = 'queued_admin';
                }
            } else {
                if ($run_source === '') {
                    $run_source = 'scheduled';
                }
                $run = $this->create_sync_run([
                    'source' => $run_source,
                    'status' => 'running',
                    'requested_at' => $this->normalize_sync_datetime($started_at) ?: date('Y-m-d H:i:s'),
                    'started_at' => $this->normalize_sync_datetime($started_at) ?: date('Y-m-d H:i:s'),
                    'trigger_context' => 'worker',
                ]);
            }
            if ($run) {
                $run->summary = $started_at . ' - sync started';
                $run->save();
                $run_uuid = (string) $run->run_uuid;
            }
        } else {
            if ($state === 'queued') {
                $pending_source = strtolower(trim($this->get_config_value('sync_pending_source', 'queued_admin')));
                $this->set_config_value('sync_request_source', $pending_source ?: 'queued_admin');
                $this->set_config_value('sync_pending_source', '');
            } else {
                $this->set_config_value('sync_request_source', strtolower(trim((string) post('run_source'))) ?: 'scheduled');
            }
            $this->set_config_value('sync_request_state', 'running');
            $this->set_config_value('sync_started_at', $started_at);
        }
        $this->refresh_legacy_sync_config_from_runs();

        jsonView([
            'status' => 'success',
            'sync_request_state' => 'running',
            'sync_started_at' => $started_at,
            'run_uuid' => $run_uuid,
            'sync_request_source' => $this->get_config_value('sync_request_source', ''),
        ]);
    }

    /**
     * Ingest JSON payload from server-side sync script.
     *
     * @return void
     **/
    public function ingest()
    {
        $this->connectDB();
        if (! $this->is_valid_sync_token()) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        require_once $this->module_path . '/simplemdm_processor.php';
        $processor = new Simplemdm_processor('simplemdm', '');
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        $records = [];
        if (is_array($data)) {
            $records = isset($data[0]) ? $data : [$data];
        }

        try {
            $processor->run($payload);
            $this->upsert_device_edges_from_payload($payload);
            $this->record_device_daily_history();
            $this->emit_device_regression_events_from_records($records);
            jsonView(['status' => 'success']);
        } catch (\Throwable $e) {
            jsonView(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Ingest generic SimpleMDM resource payload from server-side sync script.
     *
     * @return void
     **/
    public function ingest_resources()
    {
        $this->connectDB();
        if (! $this->is_valid_sync_token()) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        if (! is_array($data)) {
            jsonView(['status' => 'error', 'message' => 'Invalid JSON data'], 400);
            return;
        }

        // Accept one record or array of records.
        $records = isset($data[0]) ? $data : [$data];
        $count = 0;
        $now = gmdate('c');

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }
            $resource_type = isset($record['resource_type']) ? (string)$record['resource_type'] : '';
            $resource_id = isset($record['resource_id']) ? (string)$record['resource_id'] : '';
            if (! $resource_type || ! $resource_id) {
                continue;
            }

            $source_endpoint = isset($record['source_endpoint']) ? (string)$record['source_endpoint'] : '';
            $upsert = [
                'source_endpoint' => $source_endpoint,
                'name' => isset($record['name']) ? (string)$record['name'] : null,
                'synced_at' => isset($record['synced_at']) ? (string)$record['synced_at'] : $now,
            ];

            foreach (['attributes_json', 'relationships_json', 'data_json'] as $json_field) {
                if (isset($record[$json_field])) {
                    $upsert[$json_field] = is_array($record[$json_field])
                        ? json_encode($record[$json_field])
                        : (string)$record[$json_field];
                }
            }

            Simplemdm_resource_model::updateOrCreate(
                [
                    'resource_type' => $resource_type,
                    'resource_id' => $resource_id,
                    'source_endpoint' => $source_endpoint,
                ],
                $upsert
            );
            $count++;

            $this->upsert_resource_relationship_edges($record);
        }

        jsonView(['status' => 'success', 'count' => $count]);
    }

    /**
     * Ingest command status records from sync script.
     *
     * @return void
     **/
    public function ingest_commands()
    {
        $this->connectDB();
        if (! $this->is_valid_sync_token()) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        if (! is_array($data)) {
            jsonView(['status' => 'error', 'message' => 'Invalid JSON data'], 400);
            return;
        }

        $records = isset($data[0]) ? $data : [$data];
        $count = 0;
        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $command_uuid = isset($record['command_uuid']) ? trim((string)$record['command_uuid']) : '';
            $device_id = isset($record['device_id']) ? trim((string)$record['device_id']) : '';
            if ($command_uuid === '' && $device_id === '') {
                continue;
            }

            $serial_number = isset($record['serial_number']) ? trim((string)$record['serial_number']) : '';
            if ($serial_number === '' && $device_id !== '') {
                $d = Simplemdm_model::where('simplemdm_id', $device_id)->first();
                if ($d) {
                    $serial_number = (string)$d->serial_number;
                }
            }

            $identity = $command_uuid !== ''
                ? ['command_uuid' => $command_uuid]
                : [
                    'command_uuid' => 'device:' . $device_id . ':type:' . (isset($record['command_type']) ? (string)$record['command_type'] : 'unknown'),
                ];

            $before = Simplemdm_command_model::where('command_uuid', $identity['command_uuid'])->first();

            Simplemdm_command_model::updateOrCreate(
                $identity,
                [
                    'command_type' => isset($record['command_type']) ? (string)$record['command_type'] : null,
                    'status' => isset($record['status']) ? (string)$record['status'] : null,
                    'device_id' => $device_id !== '' ? $device_id : null,
                    'serial_number' => $serial_number !== '' ? $serial_number : null,
                    'resource_id' => isset($record['resource_id']) ? (string)$record['resource_id'] : null,
                    'error_message' => isset($record['error_message']) ? (string)$record['error_message'] : null,
                    'issued_at' => isset($record['issued_at']) ? (string)$record['issued_at'] : null,
                    'completed_at' => isset($record['completed_at']) ? (string)$record['completed_at'] : null,
                    'updated_at' => isset($record['updated_at']) ? (string)$record['updated_at'] : gmdate('c'),
                    'raw_json' => json_encode($record),
                ]
            );
            $after = Simplemdm_command_model::where('command_uuid', $identity['command_uuid'])->first();
            $this->emit_command_failure_event($before, $after);
            $count++;
        }

        jsonView(['status' => 'success', 'count' => $count]);
    }

    public function ingest_client_facts()
    {
        $this->connectDB();
        $payload = file_get_contents('php://input');
        if (strlen((string) $payload) > $this->client_reporter_max_payload_bytes()) {
            jsonView(['status' => 'error', 'message' => 'Payload exceeds configured client reporter size limit'], 413);
            return;
        }

        if (! $this->is_valid_client_reporter_secret()) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }
        if (! $this->client_reporter_enabled()) {
            jsonView(['status' => 'error', 'message' => 'Client reporter ingestion is disabled'], 403);
            return;
        }
        if (! $this->has_client_fact_table()) {
            jsonView(['status' => 'error', 'message' => 'Client fact table is unavailable. Run migrations first.'], 409);
            return;
        }

        $ip_resolution = $this->resolve_client_reporter_ip();
        if (! $ip_resolution['ok']) {
            jsonView(['status' => 'error', 'message' => $ip_resolution['message']], 403);
            return;
        }

        $hmac = $this->validate_client_reporter_timestamp_and_hmac($payload);
        if (! $hmac['ok']) {
            jsonView(['status' => 'error', 'message' => $hmac['message']], (int) ($hmac['status'] ?? 401));
            return;
        }

        $data = json_decode($payload, true);
        if (! is_array($data)) {
            jsonView(['status' => 'error', 'message' => 'Invalid JSON data'], 400);
            return;
        }

        $serial_number = trim((string) ($data['serial_number'] ?? ''));
        if ($serial_number === '') {
            jsonView(['status' => 'error', 'message' => 'Missing serial_number'], 400);
            return;
        }
        $serial_number = strtoupper($serial_number);

        $facts = isset($data['facts']) && is_array($data['facts']) ? $data['facts'] : null;
        if (! $facts) {
            jsonView(['status' => 'error', 'message' => 'Missing facts object'], 400);
            return;
        }

        $token = $this->validate_client_reporter_device_token($serial_number);
        if (! $token['ok']) {
            jsonView(['status' => 'error', 'message' => $token['message']], (int) ($token['status'] ?? 401));
            return;
        }

        $nonce = $this->claim_client_reporter_nonce(
            (string) ($hmac['nonce'] ?? ''),
            $serial_number,
            (string) ($ip_resolution['client_ip'] ?? ''),
            (string) ($hmac['timestamp'] ?? date('Y-m-d H:i:s'))
        );
        if (! $nonce['ok']) {
            jsonView(['status' => 'error', 'message' => $nonce['message']], (int) ($nonce['status'] ?? 409));
            return;
        }

        $reported_at = $this->normalize_sync_datetime($data['reported_at'] ?? '') ?: date('Y-m-d H:i:s');
        $client_version = trim((string) ($data['client_version'] ?? ''));
        $source = trim((string) ($data['source'] ?? 'client_reporter'));
        $registry = $this->client_fact_registry();
        $allowed = array_flip($this->client_reporter_allowed_fact_keys());
        $accepted = 0;
        $rejected = [];

        foreach ($facts as $fact_key => $fact_value) {
            $fact_key = trim((string) $fact_key);
            if ($fact_key === '' || ! isset($allowed[$fact_key]) || ! isset($registry[$fact_key])) {
                $rejected[] = $fact_key;
                continue;
            }

            $definition = $registry[$fact_key];
            $record = [
                'serial_number' => $serial_number,
                'fact_type' => (string) $definition['fact_type'],
                'fact_key' => $fact_key,
                'fact_value_string' => null,
                'fact_value_int' => null,
                'fact_value_bool' => null,
                'fact_value_json' => null,
                'reported_at' => $reported_at,
                'source' => $source ?: 'client_reporter',
                'client_version' => $client_version,
                'raw_json' => json_encode([$fact_key => $fact_value]),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            switch ((string) $definition['type']) {
                case 'bool':
                    if (! is_bool($fact_value) && ! in_array($fact_value, [0, 1, '0', '1'], true)) {
                        $rejected[] = $fact_key;
                        continue 2;
                    }
                    $record['fact_value_bool'] = (int) ((bool) $fact_value);
                    break;
                case 'int':
                    if (! is_int($fact_value) && ! ctype_digit((string) $fact_value)) {
                        $rejected[] = $fact_key;
                        continue 2;
                    }
                    $record['fact_value_int'] = (int) $fact_value;
                    break;
                case 'json':
                    $record['fact_value_json'] = json_encode($fact_value);
                    break;
                case 'string':
                default:
                    if (is_array($fact_value) || is_object($fact_value)) {
                        $record['fact_value_json'] = json_encode($fact_value);
                    } else {
                        $record['fact_value_string'] = trim((string) $fact_value);
                    }
                    break;
            }

            Simplemdm_client_fact_model::updateOrCreate(
                ['serial_number' => $serial_number, 'fact_key' => $fact_key],
                $record
            );

            if ($this->client_reporter_history_enabled() && $this->has_client_fact_history_table()) {
                $history = $record;
                unset($history['updated_at']);
                Simplemdm_client_fact_history_model::create($history);
            }
            $accepted++;
        }

        jsonView([
            'status' => 'success',
            'serial_number' => $serial_number,
            'accepted' => $accepted,
            'request_ip' => (string) ($ip_resolution['client_ip'] ?? ''),
            'rejected' => array_values(array_filter($rejected, function ($item) {
                return trim((string) $item) !== '';
            })),
        ]);
    }

    /**
     * Ingest webhook payloads from SimpleMDM and optionally upsert records.
     *
     * @return void
     **/
    public function webhook()
    {
        $this->connectDB();
        if (! $this->is_valid_webhook_secret() && ! $this->is_valid_sync_token()) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        if (! is_array($data)) {
            jsonView(['status' => 'error', 'message' => 'Invalid JSON data'], 400);
            return;
        }

        $event_id = '';
        foreach (['id', 'event_id', 'uuid'] as $key) {
            if (isset($data[$key]) && trim((string)$data[$key]) !== '') {
                $event_id = trim((string)$data[$key]);
                break;
            }
        }

        $event_type = '';
        foreach (['type', 'event_type', 'event'] as $key) {
            if (isset($data[$key]) && trim((string)$data[$key]) !== '') {
                $event_type = trim((string)$data[$key]);
                break;
            }
        }

        Simplemdm_webhook_event_model::updateOrCreate(
            ['event_id' => $event_id !== '' ? $event_id : 'anonymous:' . sha1($payload)],
            [
                'event_type' => $event_type !== '' ? $event_type : null,
                'status' => 'received',
                'received_at' => date('Y-m-d H:i:s'),
                'source_ip' => isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null,
                'payload_json' => $payload,
            ]
        );

        // Best-effort parsing for command/device updates in webhook payload.
        $event_data = isset($data['data']) && is_array($data['data']) ? $data['data'] : $data;
        if (isset($event_data['attributes']) && is_array($event_data['attributes'])) {
            $attrs = $event_data['attributes'];
            if (isset($attrs['serial_number']) || isset($attrs['device_name']) || isset($attrs['status'])) {
                require_once $this->module_path . '/simplemdm_processor.php';
                $processor = new Simplemdm_processor('simplemdm', '');
                $record = [
                    'serial_number' => isset($attrs['serial_number']) ? (string)$attrs['serial_number'] : '',
                    'simplemdm_id' => isset($event_data['id']) ? (string)$event_data['id'] : (isset($attrs['id']) ? (string)$attrs['id'] : null),
                    'attributes_json' => $attrs,
                    'relationships_json' => isset($event_data['relationships']) ? $event_data['relationships'] : [],
                ];
                foreach ([
                    'device_name',
                    'status',
                    'last_seen_at',
                    'os_version',
                    'is_supervised',
                    'is_dep_enrollment',
                    'filevault_enabled',
                    'firewall_enabled',
                    'sip_enabled',
                    'activation_lock_enabled',
                    'passcode_compliant',
                ] as $field) {
                    if (array_key_exists($field, $attrs)) {
                        $record[$field] = $attrs[$field];
                    }
                }
                $before = $this->get_device_snapshot(
                    isset($record['serial_number']) ? $record['serial_number'] : '',
                    isset($record['simplemdm_id']) ? $record['simplemdm_id'] : ''
                );
                try {
                    $processor->run(json_encode($record));
                    $after = $this->get_device_snapshot(
                        isset($record['serial_number']) ? $record['serial_number'] : '',
                        isset($record['simplemdm_id']) ? $record['simplemdm_id'] : ''
                    );
                    $this->evaluate_device_regression_events($before, $after, $record);
                } catch (\Throwable $e) {
                    // Keep webhook ack successful even if partial parse fails.
                }
            }
        }

        if (strpos(strtolower($event_type), 'command') !== false || isset($event_data['command_uuid'])) {
            $this->upsert_webhook_command($event_data);
        }

        jsonView(['status' => 'success']);
    }

    /**
     * Update sync status from server-side sync script.
     *
     * @return void
     **/
    public function update_sync_status()
    {
        $this->connectDB();
        if (! $this->is_valid_sync_token()) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        $post = $_POST;
        if (! isset($post['last_sync_status']) && ! isset($post['last_sync_time'])) {
            jsonView(['status' => 'error', 'message' => 'Invalid data'], 400);
            return;
        }

        if (isset($post['last_sync_status'])) {
            Simplemdm_config_model::updateOrCreate(
                ['name' => 'last_sync_status'],
                ['value' => $post['last_sync_status']]
            );
        }
        if (isset($post['last_sync_time'])) {
            Simplemdm_config_model::updateOrCreate(
                ['name' => 'last_sync_time'],
                ['value' => $post['last_sync_time']]
            );
        }

        $state_value = 'idle';
        if (isset($post['sync_request_state'])) {
            $candidate = strtolower(trim((string)$post['sync_request_state']));
            if (in_array($candidate, ['idle', 'queued', 'running'], true)) {
                $state_value = $candidate;
            }
        }
        $run = null;
        if ($this->has_sync_runs_table()) {
            $run = $this->get_sync_run_by_uuid(isset($post['run_uuid']) ? $post['run_uuid'] : '');
            if (! $run && $state_value === 'running') {
                $run = $this->get_running_sync_run();
            }
            if (! $run) {
                $run = $this->get_running_sync_run();
            }
            if (! $run && in_array($state_value, ['idle', 'queued'], true)) {
                $run = $this->get_latest_completed_sync_run();
            }
            if (! $run && (isset($post['last_sync_status']) || isset($post['last_sync_time']))) {
                $run = $this->create_sync_run([
                    'source' => trim((string) ($post['sync_request_source'] ?? $this->get_config_value('sync_request_source', 'legacy'))) ?: 'legacy',
                    'status' => $this->normalize_run_status(isset($post['last_sync_status']) ? $post['last_sync_status'] : $state_value) ?: 'success',
                    'requested_at' => $this->normalize_sync_datetime(isset($post['last_sync_time']) ? $post['last_sync_time'] : date('c')) ?: date('Y-m-d H:i:s'),
                    'trigger_context' => 'status_update',
                ]);
            }
            if ($run) {
                $normalized_status = $this->normalize_run_status(isset($post['last_sync_status']) ? $post['last_sync_status'] : $state_value);
                if ($normalized_status !== '') {
                    $run->status = $normalized_status;
                } elseif ($state_value !== '') {
                    $run->status = $state_value;
                }
                if (isset($post['sync_request_source']) && trim((string) $post['sync_request_source']) !== '') {
                    $run->source = trim((string) $post['sync_request_source']);
                }
                if ($run->requested_at === null) {
                    $run->requested_at = $this->normalize_sync_datetime(isset($post['sync_requested_at']) ? $post['sync_requested_at'] : '') ?: date('Y-m-d H:i:s');
                }
                if ($state_value === 'running') {
                    $run->started_at = $this->normalize_sync_datetime(isset($post['last_sync_time']) ? $post['last_sync_time'] : '') ?: ($run->started_at ?: date('Y-m-d H:i:s'));
                }
                if (isset($post['last_sync_time'])) {
                    $run->summary = (string) $post['last_sync_time'];
                    foreach ($this->extract_sync_counts($post['last_sync_time']) as $field => $count) {
                        if ($count !== null) {
                            $run->$field = $count;
                        }
                    }
                }
                if (isset($post['sync_last_duration_ms'])) {
                    $run->duration_ms = (int) $post['sync_last_duration_ms'];
                }
                if (isset($post['sync_last_api_requests'])) {
                    $run->api_requests = (int) $post['sync_last_api_requests'];
                }
                if (isset($post['sync_last_api_errors'])) {
                    $run->api_errors = (int) $post['sync_last_api_errors'];
                }
                if (isset($post['sync_last_api_error_details'])) {
                    $run->error_summary = trim((string) $post['sync_last_api_error_details']);
                }
                if (isset($post['sync_last_rate_limit_hits'])) {
                    $run->rate_limit_hits = (int) $post['sync_last_rate_limit_hits'];
                }
                if (isset($post['sync_last_delta_mode'])) {
                    $run->delta_mode = ((string) $post['sync_last_delta_mode']) === '1' ? 1 : 0;
                }
                if (isset($post['sync_last_scope'])) {
                    $run->scope = (string) $post['sync_last_scope'];
                }
                if ($state_value !== 'running') {
                    $run->finished_at = $this->normalize_sync_datetime(isset($post['last_sync_time']) ? $post['last_sync_time'] : '') ?: date('Y-m-d H:i:s');
                    if ($run->status !== 'success') {
                        $run->error_summary = isset($post['last_sync_time']) ? (string) $post['last_sync_time'] : (string) ($post['last_sync_status'] ?? '');
                    }
                }
                $run->save();
            }
        }

        $this->set_config_value('sync_request_state', $state_value);
        if ($state_value !== 'running') {
            $this->set_config_value('sync_started_at', '');
        }
        if ($state_value !== 'queued') {
            $this->set_config_value('sync_pending_source', '');
        }

        $telemetry_keys = [
            'sync_last_duration_ms',
            'sync_last_api_requests',
            'sync_last_api_errors',
            'sync_last_api_error_details',
            'sync_last_rate_limit_hits',
            'sync_last_delta_mode',
            'sync_last_scope',
            'last_sync_cursor',
            'sync_requested_at',
            'sync_request_source',
        ];
        foreach ($telemetry_keys as $key) {
            if (isset($post[$key])) {
                $value = (string)$post[$key];
                if ($key === 'sync_last_delta_mode') {
                    $value = $value === '1' ? '1' : '0';
                }
                Simplemdm_config_model::updateOrCreate(
                    ['name' => $key],
                    ['value' => $value]
                );
            }
        }
        $this->refresh_legacy_sync_config_from_runs();

        if (
            isset($post['last_sync_status'])
            && strtolower(trim((string)$post['last_sync_status'])) === 'success'
        ) {
            $this->record_dashboard_snapshot();
            $this->record_device_daily_history();
        }

        jsonView(['status' => 'success']);
    }

    /**
     * Persist a point-in-time dashboard snapshot for trend charts.
     *
     * @return void
     **/
    private function record_dashboard_snapshot()
    {
        $device_total = (int) Simplemdm_model::count();
        $enrolled_total = (int) Simplemdm_model::where('status', 'enrolled')->count();
        $unenrolled_total = max(0, $device_total - $enrolled_total);
        $supervised_total = (int) Simplemdm_model::where('is_supervised', 1)->count();
        $filevault_enabled_total = (int) Simplemdm_model::where('filevault_enabled', 1)->count();
        $dep_enrolled_total = (int) Simplemdm_model::where('is_dep_enrollment', 1)->count();
        $resource_total = (int) Simplemdm_resource_model::count();

        Simplemdm_dashboard_snapshot_model::create([
            'snapshot_time' => date('Y-m-d H:i:s'),
            'device_total' => $device_total,
            'enrolled_total' => $enrolled_total,
            'unenrolled_total' => $unenrolled_total,
            'supervised_total' => $supervised_total,
            'filevault_enabled_total' => $filevault_enabled_total,
            'dep_enrolled_total' => $dep_enrolled_total,
            'resource_total' => $resource_total,
        ]);
    }

    /**
     * Retrieve listing data in JSON format.
     *
     * @return void
     **/
    public function get_data()
    {
        $query = Simplemdm_model::select(
            'simplemdm.serial_number',
            'simplemdm.simplemdm_id',
            'simplemdm.device_name',
            'simplemdm.status',
            'simplemdm.os_version',
            'simplemdm.model_name',
            'simplemdm.is_supervised',
            'simplemdm.is_dep_enrollment',
            'simplemdm.filevault_enabled',
            'simplemdm.last_seen_at',
            'simplemdm.assignment_group'
        )
            ->selectRaw("CASE WHEN reportdata.serial_number IS NULL THEN 0 ELSE 1 END AS has_reportdata")
            ->leftJoin('reportdata', 'reportdata.serial_number', '=', 'simplemdm.serial_number');

        if ($this->has_supplemental_summary_table()) {
            $query
                ->leftJoin('simplemdm_supplemental_summary as supp', 'supp.serial_number', '=', 'simplemdm.serial_number')
                ->addSelect(
                    'supp.last_refresh as supplemental_last_refresh',
                    'supp.last_refresh_status as supplemental_last_refresh_status',
                    'supp.source_modules_json as supplemental_source_modules_json',
                    'supp.filevault_enabled as supplemental_filevault_enabled',
                    'supp.applecare_coverage_end as supplemental_applecare_coverage_end',
                    'supp.applecare_coverage_status as supplemental_applecare_coverage_status',
                    'supp.profile_count as supplemental_profile_count',
                    'supp.managedinstalls_warning_count as supplemental_managedinstalls_warning_count',
                    'supp.managedinstalls_error_count as supplemental_managedinstalls_error_count'
                );
        }

        jsonView($query->get()->toArray());
    }

    /**
     * Retrieve assignment group statistics in JSON format.
     *
     * @return void
     **/
    public function get_assignment_group_stats()
    {
        $sql = "
            SELECT
                COALESCE(NULLIF(TRIM(assignment_group), ''), 'No Assignment Group') AS label,
                COUNT(*) AS count
            FROM simplemdm
            GROUP BY COALESCE(NULLIF(TRIM(assignment_group), ''), 'No Assignment Group')
            ORDER BY count DESC
        ";

        jsonView(getdbh()->query($sql)->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function get_assignment_group_app_stats()
    {
        $this->connectDB();

        $app_rows = Simplemdm_resource_model::select('resource_id', 'resource_type', 'name', 'attributes_json', 'data_json')
            ->where('resource_type', 'app')
            ->get();
        $app_lookup = [];
        foreach ($app_rows as $app_row) {
            $app_id = trim((string) $app_row->resource_id);
            if ($app_id === '') {
                continue;
            }
            $app_lookup[$app_id] = [
                'resource_type' => 'app',
                'resource_id' => $app_id,
                'name' => $this->derive_resource_name_from_row($app_row),
            ];
        }

        $group_rows = Simplemdm_resource_model::select('resource_id', 'name', 'attributes_json', 'relationships_json', 'data_json')
            ->where('resource_type', 'assignment_group')
            ->orderBy('name', 'asc')
            ->get();

        if ($group_rows->isEmpty()) {
            jsonView([
                'status' => 'success',
                'groups' => [],
                'global_unique_app_count' => 0,
                'has_assignment_groups' => false,
                'source' => 'assignment_group_relationships',
            ]);
            return;
        }

        $result = [];
        $global_app_ids = [];
        $missing_metadata_count = 0;

        foreach ($group_rows as $group_row) {
            $rels = [];
            if ($group_row->relationships_json) {
                $rels = json_decode((string) $group_row->relationships_json, true);
                if (! is_array($rels)) {
                    $rels = [];
                }
            }
            if (! $rels && $group_row->data_json) {
                $data = json_decode((string) $group_row->data_json, true);
                if (is_array($data) && isset($data['relationships']) && is_array($data['relationships'])) {
                    $rels = $data['relationships'];
                }
            }

            $apps_data = isset($rels['apps']['data']) && is_array($rels['apps']['data']) ? $rels['apps']['data'] : [];
            $devices_data = isset($rels['devices']['data']) && is_array($rels['devices']['data']) ? $rels['devices']['data'] : [];

            $apps = [];
            foreach ($apps_data as $app_ref) {
                if (! is_array($app_ref)) {
                    continue;
                }
                $app_id = trim((string) ($app_ref['id'] ?? ''));
                if ($app_id === '') {
                    continue;
                }
                $app = isset($app_lookup[$app_id]) ? $app_lookup[$app_id] : [
                    'resource_type' => trim((string) ($app_ref['type'] ?? 'app')) !== '' ? (string) $app_ref['type'] : 'app',
                    'resource_id' => $app_id,
                    'name' => 'Missing app metadata (ID ' . $app_id . ')',
                    'is_resolved' => false,
                ];
                if (! array_key_exists('is_resolved', $app)) {
                    $app['is_resolved'] = ! $this->is_unresolved_resource_name((string) ($app['name'] ?? ''));
                }
                if (! $app['is_resolved']) {
                    $missing_metadata_count++;
                }
                $apps[] = $app;
                $global_app_ids[$app_id] = true;
            }

            usort($apps, function ($a, $b) {
                return strcmp(
                    strtolower((string) ($a['name'] ?? '')),
                    strtolower((string) ($b['name'] ?? ''))
                );
            });

            $result[] = [
                'label' => $this->derive_resource_name_from_row($group_row),
                'resource_id' => (string) $group_row->resource_id,
                'device_count' => count($devices_data),
                'unique_app_count' => count($apps),
                'app_record_count' => count($apps),
                'apps' => $apps,
            ];
        }

        usort($result, function ($a, $b) {
            $count_compare = (int) ($b['unique_app_count'] ?? 0) <=> (int) ($a['unique_app_count'] ?? 0);
            if ($count_compare !== 0) {
                return $count_compare;
            }
            return strcmp(
                strtolower((string) ($a['label'] ?? '')),
                strtolower((string) ($b['label'] ?? ''))
            );
        });

        jsonView([
            'status' => 'success',
            'groups' => $result,
            'global_unique_app_count' => count($global_app_ids),
            'missing_metadata_count' => $missing_metadata_count,
            'has_assignment_groups' => true,
            'source' => 'assignment_group_relationships',
        ]);
    }

    public function get_assignment_group_app_debug()
    {
        $this->connectDB();

        $device_count = (int) Simplemdm_model::whereNotNull('simplemdm_id')->count();
        $assignment_group_rows = (int) Simplemdm_resource_model::where('resource_type', 'assignment_group')->count();
        $app_rows = (int) Simplemdm_resource_model::where('resource_type', 'app')->count();
        $assignment_group_types = Simplemdm_resource_model::where('resource_type', 'assignment_group')
            ->select('resource_type')
            ->distinct()
            ->orderBy('resource_type')
            ->pluck('resource_type')
            ->toArray();

        jsonView([
            'status' => 'success',
            'device_count_with_ids' => $device_count,
            'assignment_group_row_count' => $assignment_group_rows,
            'app_row_count' => $app_rows,
            'assignment_group_resource_types' => array_values($assignment_group_types),
            'source' => 'assignment_group_relationships',
        ]);
    }

    public function get_assignment_group_app_name_debug($resource_id = '')
    {
        $this->connectDB();

        $resource_id = trim((string) $resource_id);
        if ($resource_id === '') {
            jsonView(['status' => 'error', 'message' => 'Missing resource id'], 400);
            return;
        }

        $serialize_row = function ($row) {
            if (! $row) {
                return null;
            }
            return [
                'resource_type' => isset($row->resource_type) ? (string) $row->resource_type : '',
                'resource_id' => isset($row->resource_id) ? (string) $row->resource_id : '',
                'name' => isset($row->name) ? (string) $row->name : '',
                'source_endpoint' => isset($row->source_endpoint) ? (string) $row->source_endpoint : '',
                'derived_name' => $this->derive_resource_name_from_row($row),
                'attributes_json' => isset($row->attributes_json) ? json_decode((string) $row->attributes_json, true) : null,
                'data_json' => isset($row->data_json) ? json_decode((string) $row->data_json, true) : null,
            ];
        };

        $app_row = Simplemdm_resource_model::select('resource_type', 'resource_id', 'name', 'source_endpoint', 'attributes_json', 'data_json')
            ->where('resource_type', 'app')
            ->where('resource_id', $resource_id)
            ->first();

        $installed_rows = Simplemdm_resource_model::select('resource_type', 'resource_id', 'name', 'source_endpoint', 'attributes_json', 'data_json')
            ->where('resource_type', 'installed_app')
            ->where('resource_id', $resource_id)
            ->orderBy('source_endpoint', 'asc')
            ->limit(25)
            ->get();

        $final_name = '';
        if ($app_row) {
            $final_name = $this->derive_resource_name_from_row($app_row);
        }
        if ($this->is_unresolved_resource_name($final_name)) {
            foreach ($installed_rows as $row) {
                $candidate = $this->derive_resource_name_from_row($row);
                if (! $this->is_unresolved_resource_name($candidate)) {
                    $final_name = $candidate;
                    break;
                }
            }
        }
        if ($this->is_unresolved_resource_name($final_name)) {
            $final_name = 'Missing app metadata (ID ' . $resource_id . ')';
        }

        jsonView([
            'status' => 'success',
            'resource_id' => $resource_id,
            'final_name' => $final_name !== '' ? $final_name : ('App #' . $resource_id),
            'app_row' => $serialize_row($app_row),
            'installed_app_rows' => array_values(array_map($serialize_row, $installed_rows->all())),
        ]);
    }

    public function get_supplemental_overview_stats()
    {
        $this->connectDB();
        if (! $this->has_supplemental_summary_table()) {
            jsonView([
                'total' => 0,
                'with_summary' => 0,
                'filevault_off' => 0,
                'applecare_expiring_30' => 0,
                'profiles_present' => 0,
                'managedinstalls_errors' => 0,
            ]);
            return;
        }

        $rows = Simplemdm_supplemental_summary_model::all();
        $stats = [
            'total' => (int) Simplemdm_model::count(),
            'with_summary' => $rows->count(),
            'filevault_off' => 0,
            'applecare_expiring_30' => 0,
            'profiles_present' => 0,
            'managedinstalls_errors' => 0,
        ];

        $today = strtotime(date('Y-m-d'));
        $threshold = strtotime('+30 days', $today);
        foreach ($rows as $row) {
            if ($row->filevault_enabled !== null && (int) $row->filevault_enabled === 0) {
                $stats['filevault_off']++;
            }
            if ($row->profile_count !== null && (int) $row->profile_count > 0) {
                $stats['profiles_present']++;
            }
            if ($row->managedinstalls_error_count !== null && (int) $row->managedinstalls_error_count > 0) {
                $stats['managedinstalls_errors']++;
            }
            $coverage_end = trim((string) $row->applecare_coverage_end);
            if ($coverage_end !== '') {
                $end_ts = strtotime($coverage_end);
                if ($end_ts !== false && $end_ts >= $today && $end_ts <= $threshold) {
                    $stats['applecare_expiring_30']++;
                }
            }
        }

        jsonView($stats);
    }

    public function get_supplemental_applecare_stats()
    {
        $this->connectDB();
        if (! $this->has_supplemental_summary_table()) {
            jsonView([]);
            return;
        }

        $rows = Simplemdm_supplemental_summary_model::select(
            'serial_number',
            'applecare_present',
            'applecare_coverage_end',
            'applecare_coverage_status'
        )->get();

        $bands = [
            'expired' => 0,
            'expiring_30' => 0,
            'expiring_90' => 0,
            'covered' => 0,
            'missing' => 0,
        ];

        $today = strtotime(date('Y-m-d'));
        $days30 = strtotime('+30 days', $today);
        $days90 = strtotime('+90 days', $today);

        foreach ($rows as $row) {
            if ((int) $row->applecare_present !== 1 || trim((string) $row->applecare_coverage_end) === '') {
                $bands['missing']++;
                continue;
            }
            $end_ts = strtotime((string) $row->applecare_coverage_end);
            if ($end_ts === false) {
                $bands['missing']++;
                continue;
            }
            if ($end_ts < $today) {
                $bands['expired']++;
            } elseif ($end_ts <= $days30) {
                $bands['expiring_30']++;
            } elseif ($end_ts <= $days90) {
                $bands['expiring_90']++;
            } else {
                $bands['covered']++;
            }
        }

        $result = [];
        foreach ($bands as $label => $count) {
            $result[] = [
                'label' => $label,
                'count' => $count,
            ];
        }

        jsonView($result);
    }

    /**
     * Show generic API resources listing.
     *
     * @return void
     **/
    public function resources()
    {
        $obj = new View();
        $obj->view('simplemdm_resources_listing', [], $this->module_path . '/views/');
    }

    /**
     * Retrieve generic API resources listing data in JSON format.
     *
     * @return void
     **/
    public function get_resources_data()
    {
        $draw = isset($_GET['draw']) ? (int) $_GET['draw'] : 0;
        $start = isset($_GET['start']) ? max(0, (int) $_GET['start']) : 0;
        $length = isset($_GET['length']) ? (int) $_GET['length'] : 10;
        if ($length < 1) {
            $length = 10;
        }
        if ($length > 250) {
            $length = 250;
        }

        $type = '';
        $resource_id = '';
        $endpoint = '';
        $endpoint_like = '';
        $search = '';
        if (isset($_GET['type'])) {
            $type = trim((string)$_GET['type']);
        }
        if (isset($_GET['resource_id'])) {
            $resource_id = trim((string)$_GET['resource_id']);
        }
        if (isset($_GET['endpoint'])) {
            $endpoint = trim((string)$_GET['endpoint']);
        }
        if (isset($_GET['endpoint_like'])) {
            $endpoint_like = trim((string)$_GET['endpoint_like']);
        }
        if (isset($_GET['search']) && is_array($_GET['search']) && isset($_GET['search']['value'])) {
            $search = trim((string) $_GET['search']['value']);
        }

        if (! $this->schema_has_columns('simplemdm_resource', ['resource_type', 'resource_id'])) {
            jsonView([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
            return;
        }

        $select = ['resource_type', 'resource_id'];
        foreach (['name', 'source_endpoint', 'synced_at', 'attributes_json', 'data_json'] as $column) {
            if ($this->schema_has_columns('simplemdm_resource', [$column])) {
                $select[] = $column;
            }
        }

        $base_query = Simplemdm_resource_model::query();

        $apply_filters = function ($query) use ($type, $resource_id, $endpoint, $endpoint_like, $search, $select) {
            if ($type !== '') {
                $query->where('resource_type', $type);
            }
            if ($resource_id !== '') {
                $query->where('resource_id', $resource_id);
            }
            if ($endpoint !== '' && in_array('source_endpoint', $select, true)) {
                $query->where('source_endpoint', $endpoint);
            }
            if ($endpoint_like !== '' && in_array('source_endpoint', $select, true)) {
                $query->where('source_endpoint', 'like', '%' . $endpoint_like . '%');
            }
            if ($search !== '') {
                $query->where(function ($inner) use ($search, $select) {
                    $like = '%' . $search . '%';
                    $inner->where('resource_type', 'like', $like)
                        ->orWhere('resource_id', 'like', $like);
                    if (in_array('name', $select, true)) {
                        $inner->orWhere('name', 'like', $like);
                    }
                    if (in_array('source_endpoint', $select, true)) {
                        $inner->orWhere('source_endpoint', 'like', $like);
                    }
                    if (in_array('synced_at', $select, true)) {
                        $inner->orWhere('synced_at', 'like', $like);
                    }
                });
            }
        };

        $records_total = (int) $base_query->count();

        $filtered_query = Simplemdm_resource_model::query();
        $apply_filters($filtered_query);
        $records_filtered = (int) $filtered_query->count();

        $query = Simplemdm_resource_model::select($select);
        $apply_filters($query);

        $orderable_columns = [
            0 => 'resource_type',
            1 => 'resource_id',
            2 => in_array('name', $select, true) ? 'name' : 'resource_id',
            3 => in_array('source_endpoint', $select, true) ? 'source_endpoint' : 'resource_type',
            4 => in_array('synced_at', $select, true) ? 'synced_at' : 'resource_id',
        ];
        $order_column = 0;
        $order_dir = 'asc';
        if (isset($_GET['order'][0]['column'])) {
            $candidate = (int) $_GET['order'][0]['column'];
            if (isset($orderable_columns[$candidate])) {
                $order_column = $candidate;
            }
        }
        if (isset($_GET['order'][0]['dir']) && strtolower((string) $_GET['order'][0]['dir']) === 'desc') {
            $order_dir = 'desc';
        }

        $rows = $query->orderBy($orderable_columns[$order_column], $order_dir)
            ->orderBy('resource_type')
            ->orderBy('resource_id')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'resource_type' => (string)$row->resource_type,
                'resource_id' => (string)$row->resource_id,
                'name' => $this->derive_resource_name_from_row($row),
                'source_endpoint' => isset($row->source_endpoint) ? (string)$row->source_endpoint : '',
                'synced_at' => isset($row->synced_at) ? (string)$row->synced_at : '',
            ];
        }

        jsonView([
            'draw' => $draw,
            'recordsTotal' => $records_total,
            'recordsFiltered' => $records_filtered,
            'data' => $data,
        ]);
    }

    /**
     * Retrieve distinct filter values for the resource listing.
     *
     * @return void
     **/
    public function get_resource_filter_options()
    {
        if (! $this->schema_has_columns('simplemdm_resource', ['resource_type', 'resource_id'])) {
            jsonView(['types' => [], 'endpoints' => []]);
            return;
        }

        $types = Simplemdm_resource_model::query()
            ->select('resource_type')
            ->distinct()
            ->orderBy('resource_type')
            ->pluck('resource_type')
            ->toArray();

        $endpoints = [];
        if ($this->schema_has_columns('simplemdm_resource', ['source_endpoint'])) {
            $endpoints = Simplemdm_resource_model::query()
                ->whereNotNull('source_endpoint')
                ->where('source_endpoint', '!=', '')
                ->select('source_endpoint')
                ->distinct()
                ->orderBy('source_endpoint')
                ->pluck('source_endpoint')
                ->toArray();
        }

        jsonView([
            'types' => array_values($types),
            'endpoints' => array_values($endpoints),
        ]);
    }

    /**
     * Recursively detect whether a value mentions a specific device.
     *
     * @param mixed $value
     * @param string $device_id
     * @param string $serial
     * @param string $udid
     * @return bool
     **/
    private function value_mentions_device($value, $device_id, $serial, $udid = '')
    {
        if (is_array($value)) {
            if (isset($value['type'], $value['id']) && strtolower((string)$value['type']) === 'device') {
                if ((string)$value['id'] === (string)$device_id) {
                    return true;
                }
            }
            if (isset($value['serial_number']) && (string)$value['serial_number'] === (string)$serial) {
                return true;
            }
            if ($udid && isset($value['unique_identifier']) && (string)$value['unique_identifier'] === (string)$udid) {
                return true;
            }
            foreach ($value as $child) {
                if ($this->value_mentions_device($child, $device_id, $serial, $udid)) {
                    return true;
                }
            }
            return false;
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value === (string)$device_id;
        }

        if (is_string($value)) {
            if ((string)$value === (string)$serial || (string)$value === (string)$device_id) {
                return true;
            }
            if ($udid && (string)$value === (string)$udid) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recursively extract relationship references with type/id from a structure.
     *
     * @param mixed $value
     * @param array $refs
     * @return void
     **/
    private function collect_relationship_refs($value, &$refs)
    {
        if (! is_array($value)) {
            return;
        }

        if (isset($value['type'], $value['id'])) {
            $type = strtolower(trim((string)$value['type']));
            $id = trim((string)$value['id']);
            if ($type !== '' && $id !== '') {
                $key = $type . '|' . $id;
                if (! isset($refs[$key])) {
                    $refs[$key] = [
                        'type' => $type,
                        'id' => $id,
                        'name' => isset($value['name']) ? trim((string)$value['name']) : '',
                    ];
                }
            }
        }

        foreach ($value as $child) {
            $this->collect_relationship_refs($child, $refs);
        }
    }

    private function upsert_device_edges_from_payload($payload_json)
    {
        $data = json_decode((string)$payload_json, true);
        if (! is_array($data)) {
            return;
        }
        $records = isset($data[0]) ? $data : [$data];
        $now = date('Y-m-d H:i:s');

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }
            $serial = isset($record['serial_number']) ? trim((string)$record['serial_number']) : '';
            if ($serial === '') {
                continue;
            }

            $device_id = isset($record['simplemdm_id']) ? trim((string)$record['simplemdm_id']) : '';
            $refs = [];
            $rels = isset($record['relationships_json']) ? $record['relationships_json'] : [];
            if (is_string($rels)) {
                $tmp = json_decode($rels, true);
                $rels = is_array($tmp) ? $tmp : [];
            }
            $this->collect_relationship_refs($rels, $refs);
            foreach ($refs as $ref) {
                $type = isset($ref['type']) ? trim((string)$ref['type']) : '';
                $id = isset($ref['id']) ? trim((string)$ref['id']) : '';
                if ($type === '' || $id === '') {
                    continue;
                }
                if ($type === 'device' && $device_id !== '' && $id === $device_id) {
                    continue;
                }
                Simplemdm_relationship_edge_model::updateOrCreate(
                    [
                        'serial_number' => $serial,
                        'source_kind' => 'device',
                        'source_type' => 'device',
                        'source_id' => $device_id !== '' ? $device_id : $serial,
                        'target_type' => $type,
                        'target_id' => $id,
                    ],
                    [
                        'source_endpoint' => 'device_relationships',
                        'last_seen_at' => $now,
                    ]
                );
            }
        }
    }

    private function upsert_resource_relationship_edges($record)
    {
        if (! is_array($record)) {
            return;
        }
        $source_type = isset($record['resource_type']) ? trim((string)$record['resource_type']) : '';
        $source_id = isset($record['resource_id']) ? trim((string)$record['resource_id']) : '';
        if ($source_type === '' || $source_id === '') {
            return;
        }
        $rels = isset($record['relationships_json']) ? $record['relationships_json'] : [];
        if (is_string($rels)) {
            $tmp = json_decode($rels, true);
            $rels = is_array($tmp) ? $tmp : [];
        }
        if (! is_array($rels)) {
            $rels = [];
        }

        $refs = [];
        $this->collect_relationship_refs($rels, $refs);
        $now = date('Y-m-d H:i:s');
        $endpoint = isset($record['source_endpoint']) ? (string)$record['source_endpoint'] : '';

        foreach ($refs as $ref) {
            $type = isset($ref['type']) ? trim((string)$ref['type']) : '';
            $id = isset($ref['id']) ? trim((string)$ref['id']) : '';
            if ($type === '' || $id === '') {
                continue;
            }

            $serial = null;
            if ($type === 'device') {
                $device = Simplemdm_model::where('simplemdm_id', $id)->first();
                if ($device) {
                    $serial = (string)$device->serial_number;
                }
            }

            Simplemdm_relationship_edge_model::updateOrCreate(
                [
                    'serial_number' => $serial,
                    'source_kind' => 'resource',
                    'source_type' => $source_type,
                    'source_id' => $source_id,
                    'target_type' => $type,
                    'target_id' => $id,
                ],
                [
                    'source_endpoint' => $endpoint,
                    'last_seen_at' => $now,
                ]
            );
        }
    }

    private function upsert_webhook_command($event_data)
    {
        if (! is_array($event_data)) {
            return;
        }
        $attrs = isset($event_data['attributes']) && is_array($event_data['attributes']) ? $event_data['attributes'] : $event_data;
        $command_uuid = '';
        foreach (['command_uuid', 'uuid', 'id'] as $key) {
            if (isset($attrs[$key]) && trim((string)$attrs[$key]) !== '') {
                $command_uuid = trim((string)$attrs[$key]);
                break;
            }
        }
        if ($command_uuid === '') {
            return;
        }

        $device_id = isset($attrs['device_id']) ? trim((string)$attrs['device_id']) : '';
        $serial = '';
        if ($device_id !== '') {
            $d = Simplemdm_model::where('simplemdm_id', $device_id)->first();
            if ($d) {
                $serial = (string)$d->serial_number;
            }
        }

        $before = Simplemdm_command_model::where('command_uuid', $command_uuid)->first();
        Simplemdm_command_model::updateOrCreate(
            ['command_uuid' => $command_uuid],
            [
                'command_type' => isset($attrs['command_type']) ? (string)$attrs['command_type'] : (isset($attrs['type']) ? (string)$attrs['type'] : null),
                'status' => isset($attrs['status']) ? (string)$attrs['status'] : 'received',
                'device_id' => $device_id !== '' ? $device_id : null,
                'serial_number' => $serial !== '' ? $serial : null,
                'resource_id' => isset($attrs['resource_id']) ? (string)$attrs['resource_id'] : null,
                'error_message' => isset($attrs['error']) ? (string)$attrs['error'] : null,
                'issued_at' => isset($attrs['created_at']) ? (string)$attrs['created_at'] : null,
                'completed_at' => isset($attrs['completed_at']) ? (string)$attrs['completed_at'] : null,
                'updated_at' => isset($attrs['updated_at']) ? (string)$attrs['updated_at'] : gmdate('c'),
                'raw_json' => json_encode($event_data),
            ]
        );
        $after = Simplemdm_command_model::where('command_uuid', $command_uuid)->first();
        $this->emit_command_failure_event($before, $after);
    }

    private function record_device_daily_history()
    {
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $devices = Simplemdm_model::select(
            'serial_number',
            'status',
            'os_version',
            'assignment_group',
            'is_supervised',
            'is_dep_enrollment',
            'filevault_enabled'
        )->get();

        foreach ($devices as $device) {
            $serial = trim((string)$device->serial_number);
            if ($serial === '') {
                continue;
            }
            Simplemdm_device_history_model::updateOrCreate(
                [
                    'serial_number' => $serial,
                    'snapshot_date' => $today,
                ],
                [
                    'status' => isset($device->status) ? (string)$device->status : null,
                    'os_version' => isset($device->os_version) ? (string)$device->os_version : null,
                    'assignment_group' => isset($device->assignment_group) ? (string)$device->assignment_group : null,
                    'is_supervised' => isset($device->is_supervised) ? (int)$device->is_supervised : null,
                    'is_dep_enrollment' => isset($device->is_dep_enrollment) ? (int)$device->is_dep_enrollment : null,
                    'filevault_enabled' => isset($device->filevault_enabled) ? (int)$device->filevault_enabled : null,
                    'updated_at' => $now,
                ]
            );
        }
    }

    /**
     * Normalize API relationship type to local resource_type values.
     *
     * @param string $type
     * @return array
     **/
    private function normalize_related_resource_types($type)
    {
        $type = strtolower(trim((string)$type));
        if ($type === '') {
            return [];
        }

        if ($type === 'group') {
            return ['assignment_group', 'group'];
        }

        return [$type];
    }

    /**
     * Map resource_type to collection endpoint base.
     *
     * @param string $resource_type
     * @return string
     **/
    private function resource_collection_for_type($resource_type)
    {
        $resource_type = strtolower(trim((string)$resource_type));
        $map = [
            'app' => 'apps',
            'assignment_group' => 'assignment_groups',
            'custom_configuration_profile' => 'custom_configuration_profiles',
            'device' => 'devices',
            'device_group' => 'device_groups',
            'enrollment' => 'enrollments',
            'group' => 'groups',
            'managed_software_update' => 'managed_software_updates',
            'privacy_preference' => 'privacy_preferences',
            'restriction' => 'restrictions',
            'script' => 'scripts',
        ];

        if (isset($map[$resource_type])) {
            return $map[$resource_type];
        }

        return $resource_type !== '' ? $resource_type . 's' : '';
    }

    /**
     * Return configurable widget keys for this module from provides.yml.
     *
     * @return array
     **/
    private function get_widget_config_keys()
    {
        $path = APP_ROOT . 'local/modules/simplemdm/provides.yml';
        if (! is_readable($path)) {
            return [];
        }

        try {
            $provides = \Symfony\Component\Yaml\Yaml::parseFile($path);
        } catch (\Throwable $e) {
            return [];
        }

        if (! isset($provides['widgets']) || ! is_array($provides['widgets'])) {
            return [];
        }

        $keys = [];
        foreach (array_keys($provides['widgets']) as $widget_id) {
            $widget_id = trim((string)$widget_id);
            if ($widget_id === '') {
                continue;
            }
            $keys[] = 'widget_' . $widget_id;
        }

        return $keys;
    }

    /**
     * Parse "collection/id/subpath" endpoints.
     *
     * @param string $endpoint
     * @return array
     **/
    private function parse_nested_endpoint($endpoint)
    {
        $parts = explode('/', trim((string)$endpoint, '/'));
        if (count($parts) < 3) {
            return [];
        }

        return [
            'collection' => $parts[0],
            'parent_id' => $parts[1],
            'subpath' => $parts[2],
        ];
    }

    /**
     * Find one parent resource row by collection and id.
     *
     * @param string $collection
     * @param string $parent_id
     * @return array
     **/
    private function lookup_parent_resource($collection, $parent_id)
    {
        $row = Simplemdm_resource_model::where('source_endpoint', $collection)
            ->where('resource_id', (string)$parent_id)
            ->first();

        if (! $row) {
            return [];
        }

        return [
            'type' => (string)$row->resource_type,
            'id' => (string)$row->resource_id,
            'name' => $this->derive_resource_name_from_row($row),
            'endpoint' => (string)$row->source_endpoint,
        ];
    }

    private function derive_resource_name($resource_type, $resource_id, $stored_name = '', $attributes = [], $data = [])
    {
        $stored_name = trim((string)$stored_name);
        if ($stored_name !== '') {
            return $stored_name;
        }

        if (is_string($attributes)) {
            $tmp = json_decode($attributes, true);
            $attributes = is_array($tmp) ? $tmp : [];
        }
        if (is_string($data)) {
            $tmp = json_decode($data, true);
            $data = is_array($tmp) ? $tmp : [];
        }
        if (! is_array($attributes)) {
            $attributes = [];
        }
        if (! is_array($data)) {
            $data = [];
        }

        $preferred_keys = [
            'name', 'display_name', 'title', 'device_name', 'serial_number',
            'username', 'email', 'bundle_identifier', 'profile_name', 'label', 'topic',
        ];
        foreach ($preferred_keys as $key) {
            if (isset($attributes[$key]) && trim((string)$attributes[$key]) !== '') {
                return trim((string)$attributes[$key]);
            }
        }

        if (isset($data['attributes']) && is_array($data['attributes'])) {
            foreach ($preferred_keys as $key) {
                if (isset($data['attributes'][$key]) && trim((string)$data['attributes'][$key]) !== '') {
                    return trim((string)$data['attributes'][$key]);
                }
            }
        }

        if ($resource_type === 'app') {
            $app_keys = [
                'app_name', 'localized_name', 'itunes_store_name', 'bundle_id',
                'identifier', 'package_name', 'file_name', 'filename', 'sku',
            ];
            foreach ($app_keys as $key) {
                if (isset($attributes[$key]) && trim((string)$attributes[$key]) !== '') {
                    return trim((string)$attributes[$key]);
                }
            }
            if (isset($data['attributes']) && is_array($data['attributes'])) {
                foreach ($app_keys as $key) {
                    if (isset($data['attributes'][$key]) && trim((string)$data['attributes'][$key]) !== '') {
                        return trim((string)$data['attributes'][$key]);
                    }
                }
            }

            $nested_sources = [];
            foreach (['metadata', 'package', 'app', 'itunes_store_app'] as $nested_key) {
                if (isset($attributes[$nested_key]) && is_array($attributes[$nested_key])) {
                    $nested_sources[] = $attributes[$nested_key];
                }
                if (isset($data['attributes'][$nested_key]) && is_array($data['attributes'][$nested_key])) {
                    $nested_sources[] = $data['attributes'][$nested_key];
                }
            }

            foreach ($nested_sources as $nested) {
                foreach (array_merge($preferred_keys, $app_keys) as $key) {
                    if (isset($nested[$key]) && trim((string)$nested[$key]) !== '') {
                        return trim((string)$nested[$key]);
                    }
                }
            }
        }

        if ($resource_type === 'enrollment') {
            $parts = [];

            if (isset($data['relationships']['device_group']['data']['id'])) {
                $group_id = (string)$data['relationships']['device_group']['data']['id'];
                $group_name = $this->lookup_resource_name('device_group', $group_id);
                if ($group_name !== '') {
                    $parts[] = $group_name;
                } else {
                    $parts[] = 'Device Group #' . $group_id;
                }
            }

            if (array_key_exists('user_enrollment', $attributes)) {
                $parts[] = $attributes['user_enrollment'] ? 'User Enrollment' : 'Device Enrollment';
            }
            if (array_key_exists('authentication', $attributes)) {
                $parts[] = $attributes['authentication'] ? 'Authenticated' : 'No Auth';
            }
            if (array_key_exists('welcome_screen', $attributes)) {
                $parts[] = $attributes['welcome_screen'] ? 'Welcome Screen' : 'No Welcome Screen';
            }

            if ($parts) {
                return implode(' | ', $parts);
            }
        }

        return ucfirst(str_replace('_', ' ', (string)$resource_type)) . ' #' . (string)$resource_id;
    }

    private function derive_resource_name_from_row($row)
    {
        return $this->derive_resource_name(
            isset($row->resource_type) ? (string)$row->resource_type : '',
            isset($row->resource_id) ? (string)$row->resource_id : '',
            isset($row->name) ? (string)$row->name : '',
            isset($row->attributes_json) ? $row->attributes_json : [],
            isset($row->data_json) ? $row->data_json : []
        );
    }

    private function is_unresolved_resource_name($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return true;
        }

        return (bool) preg_match('/^(App\s+#|Missing app metadata \(ID )/i', $name);
    }

    private function lookup_resource_name($resource_type, $resource_id)
    {
        static $cache = [];

        $key = (string)$resource_type . ':' . (string)$resource_id;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $row = Simplemdm_resource_model::where('resource_type', (string)$resource_type)
            ->where('resource_id', (string)$resource_id)
            ->orderBy('source_endpoint')
            ->first();

        if (! $row) {
            $cache[$key] = '';
            return '';
        }

        $name = $this->derive_resource_name_from_row($row);
        $cache[$key] = (string)$name;
        return $cache[$key];
    }

    /**
     * Retrieve all resources connected to a specific device serial.
     *
     * @param string $serial_number
     * @return void
     **/
    public function get_device_resources($serial_number = '')
    {
        if (! $serial_number) {
            jsonView(['status' => 'error', 'message' => 'No serial number provided'], 400);
            return;
        }

        $device = Simplemdm_model::where('serial_number', $serial_number)->first();
        if (! $device || ! $device->simplemdm_id) {
            jsonView([
                'status' => 'success',
                'device' => null,
                'summary' => [],
                'connections' => [],
            ]);
            return;
        }

        $device_id = (string)$device->simplemdm_id;
        $serial = (string)$device->serial_number;
        $udid = isset($device->unique_identifier) ? (string)$device->unique_identifier : '';
        $connections = [];
        $seen = [];

        $append_connection = function ($conn) use (&$connections, &$seen) {
            $key = implode('|', [
                isset($conn['type']) ? (string)$conn['type'] : '',
                isset($conn['id']) ? (string)$conn['id'] : '',
                isset($conn['endpoint']) ? (string)$conn['endpoint'] : '',
                isset($conn['reason']) ? (string)$conn['reason'] : '',
            ]);
            if (isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;
            $connections[] = $conn;
        };

        // 1) Device membership rows from nested ".../{parent_id}/devices" endpoints.
        $device_memberships = Simplemdm_resource_model::where('resource_type', 'device')
            ->where('resource_id', $device_id)
            ->where('source_endpoint', 'like', '%/devices')
            ->get();

        foreach ($device_memberships as $row) {
            $endpoint = (string)$row->source_endpoint;
            $parsed = $this->parse_nested_endpoint($endpoint);
            if (! $parsed || $parsed['subpath'] !== 'devices') {
                continue;
            }
            $parent = $this->lookup_parent_resource($parsed['collection'], (string)$parsed['parent_id']);
            if ($parent) {
                $parent['reason'] = 'member_of_' . $parsed['collection'];
                $parent['matched_via'] = $endpoint;
                $append_connection($parent);
            }
        }

        // 1b) Traverse device relationships and include linked parent + nested resources.
        $device_relationships = [];
        if (! empty($device->relationships_json)) {
            $device_relationships = json_decode($device->relationships_json, true);
            if (! is_array($device_relationships)) {
                $device_relationships = [];
            }
        }

        $relationship_refs = [];
        $this->collect_relationship_refs($device_relationships, $relationship_refs);
        foreach ($relationship_refs as $ref) {
            $related_types = $this->normalize_related_resource_types(isset($ref['type']) ? $ref['type'] : '');
            if (! $related_types) {
                continue;
            }
            $ref_id = isset($ref['id']) ? (string)$ref['id'] : '';
            if ($ref_id === '' || $ref_id === $device_id) {
                continue;
            }

            // Direct parent resources referenced by the device relationships.
            $parent_rows = Simplemdm_resource_model::where('resource_id', $ref_id)
                ->whereIn('resource_type', $related_types)
                ->get();

            foreach ($parent_rows as $row) {
                $append_connection([
                    'type' => (string)$row->resource_type,
                    'id' => (string)$row->resource_id,
                    'name' => $this->derive_resource_name_from_row($row),
                    'endpoint' => (string)$row->source_endpoint,
                    'reason' => 'device_relationship_' . (string)$ref['type'],
                    'matched_via' => 'device_relationships_json',
                ]);
            }

            // Nested children under each referenced parent (apps/profiles/devices/etc).
            foreach ($related_types as $related_type) {
                $collection = $this->resource_collection_for_type($related_type);
                if ($collection === '') {
                    continue;
                }

                $nested_rows = Simplemdm_resource_model::where('source_endpoint', 'like', $collection . '/' . $ref_id . '/%')
                    ->get();

                foreach ($nested_rows as $row) {
                    $append_connection([
                        'type' => (string)$row->resource_type,
                        'id' => (string)$row->resource_id,
                        'name' => $this->derive_resource_name_from_row($row),
                        'endpoint' => (string)$row->source_endpoint,
                        'reason' => 'via_' . $related_type,
                        'matched_via' => $collection . '/' . $ref_id,
                    ]);
                }
            }
        }

        // 2) Targeted scan for rows that likely reference this device.
        // Match only rows mentioning this device's id/serial/udid in SQL;
        // hydrating broad classes of rows (e.g. every installed_app) exhausts
        // PHP memory on large fleets. value_mentions_device() re-verifies below.
        $candidates = Simplemdm_resource_model::where(function ($q) use ($device_id, $serial, $udid) {
            $q->where(function ($sub) use ($device_id) {
                $sub->where('resource_type', 'device')
                    ->where('resource_id', $device_id);
            })
                ->orWhere('source_endpoint', 'like', '%/' . $device_id . '/%')
                ->orWhere('data_json', 'like', '%"id":"' . $device_id . '"%')
                ->orWhere('data_json', 'like', '%"id":' . $device_id . '%')
                ->orWhere('relationships_json', 'like', '%"id":"' . $device_id . '"%')
                ->orWhere('relationships_json', 'like', '%"id":' . $device_id . '%')
                ->orWhere('attributes_json', 'like', '%"id":"' . $device_id . '"%')
                ->orWhere('attributes_json', 'like', '%"id":' . $device_id . '%')
                ->orWhere('data_json', 'like', '%"serial_number":"' . $serial . '"%')
                ->orWhere('relationships_json', 'like', '%"serial_number":"' . $serial . '"%')
                ->orWhere('attributes_json', 'like', '%"serial_number":"' . $serial . '"%');

            if ($udid !== '') {
                $q->orWhere('data_json', 'like', '%"unique_identifier":"' . $udid . '"%')
                    ->orWhere('relationships_json', 'like', '%"unique_identifier":"' . $udid . '"%')
                    ->orWhere('attributes_json', 'like', '%"unique_identifier":"' . $udid . '"%');
            }
        })->get();

        foreach ($candidates as $row) {
            $attrs = [];
            $rels = [];
            $data = [];
            if ($row->attributes_json) {
                $attrs = json_decode($row->attributes_json, true);
                if (! is_array($attrs)) {
                    $attrs = [];
                }
            }
            if ($row->relationships_json) {
                $rels = json_decode($row->relationships_json, true);
                if (! is_array($rels)) {
                    $rels = [];
                }
            }
            if ($row->data_json) {
                $data = json_decode($row->data_json, true);
                if (! is_array($data)) {
                    $data = [];
                }
            }

            $is_match = $this->value_mentions_device($data, $device_id, $serial, $udid)
                || $this->value_mentions_device($rels, $device_id, $serial, $udid)
                || $this->value_mentions_device($attrs, $device_id, $serial, $udid);

            if (! $is_match) {
                continue;
            }

            $reason = 'reference';
            $endpoint = (string)$row->source_endpoint;
            $parsed = $this->parse_nested_endpoint($endpoint);
            if ($parsed) {
                if ($parsed['subpath'] === 'installs') {
                    $reason = 'app_install';
                    $parent = $this->lookup_parent_resource($parsed['collection'], (string)$parsed['parent_id']);
                    if ($parent) {
                        $parent['reason'] = 'installed_app';
                        $parent['matched_via'] = $endpoint;
                        $append_connection($parent);
                    }
                } elseif ($parsed['subpath'] === 'profiles') {
                    $reason = 'assigned_profile';
                } elseif ($parsed['subpath'] === 'apps') {
                    $reason = 'assigned_app';
                } elseif ($parsed['subpath'] === 'devices') {
                    $reason = 'member_of';
                }
            }

            $append_connection([
                'type' => (string)$row->resource_type,
                'id' => (string)$row->resource_id,
                'name' => $this->derive_resource_name_from_row($row),
                'endpoint' => $endpoint,
                'reason' => $reason,
                'matched_via' => 'data_or_relationships',
            ]);
        }

        // Sort by type then name for stable output.
        usort($connections, function ($a, $b) {
            $ta = strtolower(isset($a['type']) ? (string)$a['type'] : '');
            $tb = strtolower(isset($b['type']) ? (string)$b['type'] : '');
            if ($ta === $tb) {
                $na = strtolower(isset($a['name']) ? (string)$a['name'] : '');
                $nb = strtolower(isset($b['name']) ? (string)$b['name'] : '');
                return strcmp($na, $nb);
            }
            return strcmp($ta, $tb);
        });

        $summary = [];
        foreach ($connections as $conn) {
            $type = isset($conn['type']) ? (string)$conn['type'] : 'unknown';
            if (! isset($summary[$type])) {
                $summary[$type] = 0;
            }
            $summary[$type]++;
        }
        ksort($summary);

        $summary_rows = [];
        foreach ($summary as $type => $count) {
            $summary_rows[] = ['type' => $type, 'count' => $count];
        }

        jsonView([
            'status' => 'success',
            'device' => [
                'serial_number' => $serial,
                'simplemdm_id' => $device_id,
                'device_name' => (string)$device->device_name,
            ],
            'summary' => $summary_rows,
            'connections' => $connections,
        ]);
    }

    /**
     * Retrieve per-device nested subresources synced under /devices/{id}/...
     *
     * @param string $serial_number
     * @return void
     **/
    public function get_device_subresources($serial_number = '')
    {
        $serial_number = trim((string)$serial_number);
        if ($serial_number === '') {
            jsonView(['status' => 'error', 'message' => 'No serial number provided'], 400);
            return;
        }

        $device = Simplemdm_model::where('serial_number', $serial_number)->first();
        if (! $device || ! $device->simplemdm_id) {
            jsonView([
                'status' => 'success',
                'device_id' => '',
                'installed_apps' => [],
                'users' => [],
                'profiles' => [],
            ]);
            return;
        }

        $device_id = (string)$device->simplemdm_id;
        $fetch = function ($suffix) use ($device_id) {
            return Simplemdm_resource_model::where('source_endpoint', 'devices/' . $device_id . '/' . $suffix)
                ->orderBy('name', 'asc')
                ->get();
        };

        $map_rows = function ($rows) {
            $out = [];
            foreach ($rows as $row) {
                $attrs = [];
                if ($row->attributes_json) {
                    $attrs = json_decode($row->attributes_json, true);
                    if (! is_array($attrs)) {
                        $attrs = [];
                    }
                }
                $out[] = [
                    'type' => (string)$row->resource_type,
                    'id' => (string)$row->resource_id,
                    'name' => $this->derive_resource_name_from_row($row),
                    'attributes' => $attrs,
                ];
            }
            return $out;
        };

        $installed_apps = $map_rows($fetch('installed_apps'));
        $users = $map_rows($fetch('users'));
        $profiles = $map_rows($fetch('profiles'));

        jsonView([
            'status' => 'success',
            'device_id' => $device_id,
            'installed_apps' => $installed_apps,
            'users' => $users,
            'profiles' => $profiles,
        ]);
    }

    /**
     * Retrieve resource type counts for widget/listing filters.
     *
     * @return void
     **/
    public function get_resource_type_stats()
    {
        jsonView(
            Simplemdm_resource_model::selectRaw('resource_type, COUNT(*) AS count')
                ->groupBy('resource_type')
                ->orderBy('resource_type')
                ->get()
                ->toArray()
        );
    }

    /**
     * Retrieve count for a single resource type.
     *
     * @param string $resource_type
     * @return void
     **/
    public function get_resource_type_count($resource_type = '')
    {
        $resource_type = trim((string)$resource_type);
        if ($resource_type === '') {
            jsonView(['resource_type' => '', 'count' => 0]);
            return;
        }

        $count = (int) Simplemdm_resource_model::where('resource_type', $resource_type)->count();
        jsonView(['resource_type' => $resource_type, 'count' => $count]);
    }

    /**
     * Retrieve command status distribution.
     *
     * @return void
     **/
    /**
     * Current SimpleMDM alert/regression events (the 13 built-in event types
     * plus custom rules) from the shared MunkiReport events table. These are
     * written by store_simplemdm_event() but previously had no read route —
     * consumed by the SimpleMDM-MCP get_munkireport_alerts tool.
     * GET /module/simplemdm/get_events[/serial]?limit=100&type=danger|warning|info
     *
     * @return void
     **/
    /**
     * Ingest findings computed by an external MCP server (SOFA CVE posture,
     * audit deltas, stale/compliance findings — data neither SimpleMDM nor
     * MunkiReport produces alone). Sync-token authenticated like the other
     * ingest endpoints. POST JSON:
     *   { "source": "sofa_audit", "replace": true,
     *     "findings": [ { "serial_number"?, "finding_type", "severity",
     *                     "message", "data"? } ] }
     * replace=true (default) swaps out all previous findings from the same
     * source, so repeated pushes reflect current state instead of piling up.
     *
     * @return void
     **/
    public function ingest_mcp_findings()
    {
        $this->connectDB();
        if (! $this->is_valid_sync_token()) {
            jsonView(['status' => 'error', 'message' => 'Unauthorized'], 401);
            return;
        }

        $payload = file_get_contents('php://input');
        if (strlen($payload) > 2097152) {
            jsonView(['status' => 'error', 'message' => 'Payload too large (2 MB cap)'], 413);
            return;
        }
        $data = json_decode($payload, true);
        if (! is_array($data)) {
            jsonView(['status' => 'error', 'message' => 'Invalid JSON data'], 400);
            return;
        }

        $source = isset($data['source']) ? strtolower(trim((string) $data['source'])) : '';
        if ($source === '' || ! preg_match('/^[a-z0-9_\-]{1,64}$/', $source)) {
            jsonView(['status' => 'error', 'message' => 'source is required (a-z, 0-9, _, -)'], 400);
            return;
        }

        $findings = isset($data['findings']) && is_array($data['findings']) ? $data['findings'] : null;
        if ($findings === null) {
            jsonView(['status' => 'error', 'message' => 'findings array is required'], 400);
            return;
        }
        if (count($findings) > 2000) {
            jsonView(['status' => 'error', 'message' => 'Too many findings (2000 cap per push)'], 413);
            return;
        }

        $valid_severities = ['danger', 'warning', 'info'];
        $now = gmdate('c');
        $rows = [];
        $skipped = 0;
        foreach ($findings as $finding) {
            if (! is_array($finding)) {
                $skipped++;
                continue;
            }
            $type = isset($finding['finding_type']) ? trim((string) $finding['finding_type']) : '';
            $message = isset($finding['message']) ? trim((string) $finding['message']) : '';
            if ($type === '' || $message === '') {
                $skipped++;
                continue;
            }
            $severity = isset($finding['severity']) ? strtolower(trim((string) $finding['severity'])) : 'info';
            if (! in_array($severity, $valid_severities, true)) {
                $severity = 'info';
            }
            $extra = '';
            if (isset($finding['data']) && $finding['data'] !== null && $finding['data'] !== '') {
                $extra = is_string($finding['data']) ? $finding['data'] : json_encode($finding['data']);
                if ($extra === false) {
                    $extra = '';
                }
                if (strlen($extra) > 4096) {
                    $extra = substr($extra, 0, 4096);
                }
            }
            $rows[] = [
                'serial_number' => isset($finding['serial_number']) ? substr(trim((string) $finding['serial_number']), 0, 64) : null,
                'source'        => $source,
                'finding_type'  => substr($type, 0, 128),
                'severity'      => $severity,
                'message'       => substr($message, 0, 1000),
                'data'          => $extra,
                'reported_at'   => $now,
            ];
        }

        $replace = ! isset($data['replace']) || $data['replace'] !== false;
        if ($replace) {
            Simplemdm_mcp_finding_model::where('source', $source)->delete();
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            Simplemdm_mcp_finding_model::insert($chunk);
        }

        jsonView([
            'status'   => 'success',
            'source'   => $source,
            'stored'   => count($rows),
            'skipped'  => $skipped,
            'replaced' => $replace,
        ]);
    }

    /**
     * Read back MCP-pushed findings (widget + MCP consumer).
     * GET /module/simplemdm/get_mcp_findings[/serial]?severity=&source=&limit=
     *
     * @return void
     **/
    public function get_mcp_findings($serial_number = '')
    {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
        if ($limit < 1) {
            $limit = 100;
        }
        if ($limit > 500) {
            $limit = 500;
        }

        $query = Simplemdm_mcp_finding_model::orderBy('id', 'desc')->limit($limit);

        $serial_number = trim((string) $serial_number);
        if ($serial_number !== '') {
            $query->where('serial_number', $serial_number);
        }
        $severity = isset($_GET['severity']) ? strtolower(trim((string) $_GET['severity'])) : '';
        if ($severity !== '') {
            $query->where('severity', $severity);
        }
        $source = isset($_GET['source']) ? strtolower(trim((string) $_GET['source'])) : '';
        if ($source !== '') {
            $query->where('source', $source);
        }

        $rows = [];
        foreach ($query->get() as $row) {
            $rows[] = $row->toArray();
        }

        $totals = [
            'danger'  => (int) Simplemdm_mcp_finding_model::where('severity', 'danger')->count(),
            'warning' => (int) Simplemdm_mcp_finding_model::where('severity', 'warning')->count(),
            'info'    => (int) Simplemdm_mcp_finding_model::where('severity', 'info')->count(),
        ];

        jsonView([
            'count'    => count($rows),
            'totals'   => $totals,
            'findings' => $rows,
        ]);
    }

    public function get_events($serial_number = '')
    {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
        if ($limit < 1) {
            $limit = 100;
        }
        if ($limit > 500) {
            $limit = 500;
        }

        $query = Event_model::where('module', 'like', 'simplemdm%')
            ->orderBy('id', 'desc')
            ->limit($limit);

        $serial_number = trim((string) $serial_number);
        if ($serial_number !== '') {
            $query->where('serial_number', $serial_number);
        }

        $type = isset($_GET['type']) ? trim((string) $_GET['type']) : '';
        if ($type !== '') {
            $query->where('type', $type);
        }

        $rows = [];
        foreach ($query->get() as $row) {
            $rows[] = $row->toArray();
        }

        jsonView([
            'count' => count($rows),
            'events' => $rows,
        ]);
    }

    public function get_command_status_stats()
    {
        $rows = Simplemdm_command_model::selectRaw("COALESCE(NULLIF(TRIM(status), ''), 'unknown') AS label, COUNT(*) AS count")
            ->groupBy('label')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
        jsonView($rows);
    }

    /**
     * Retrieve compliance summary (enrolled + supervised + FileVault + optional minimum OS).
     *
     * @return void
     **/
    public function get_compliance_stats()
    {
        $min_os = '';
        $cfg = Simplemdm_config_model::where('name', 'compliance_min_os')->first();
        if ($cfg) {
            $min_os = trim((string)$cfg->value);
        }

        $rows = Simplemdm_model::select(
            'serial_number',
            'status',
            'is_supervised',
            'filevault_enabled',
            'os_version'
        )->get();

        $total = 0;
        $compliant = 0;
        $noncompliant = 0;
        $reasons = [
            'not_enrolled' => 0,
            'not_supervised' => 0,
            'filevault_off' => 0,
            'os_below_min' => 0,
        ];

        foreach ($rows as $row) {
            $total++;
            $ok = true;
            if ((string)$row->status !== 'enrolled') {
                $ok = false;
                $reasons['not_enrolled']++;
            }
            if ((int)$row->is_supervised !== 1) {
                $ok = false;
                $reasons['not_supervised']++;
            }
            if ((int)$row->filevault_enabled !== 1) {
                $ok = false;
                $reasons['filevault_off']++;
            }
            if ($min_os !== '') {
                $os = trim((string)$row->os_version);
                if ($os === '' || version_compare($os, $min_os, '<')) {
                    $ok = false;
                    $reasons['os_below_min']++;
                }
            }

            if ($ok) {
                $compliant++;
            } else {
                $noncompliant++;
            }
        }

        jsonView([
            'total' => $total,
            'compliant' => $compliant,
            'noncompliant' => $noncompliant,
            'min_os' => $min_os,
            'reasons' => $reasons,
        ]);
    }

    /**
     * Retrieve last sync telemetry fields stored in config.
     *
     * @return void
     **/
    public function get_sync_telemetry()
    {
        $run_state = $this->derive_sync_state_from_runs();
        $keys = [
            'sync_request_state',
            'sync_requested_at',
            'sync_started_at',
            'sync_request_source',
            'last_sync_status',
            'last_sync_time',
            'last_completed_sync_status',
            'last_completed_sync_time',
            'last_completed_sync_source',
            'last_sync_cursor',
            'sync_last_duration_ms',
            'sync_last_api_requests',
            'sync_last_api_errors',
            'sync_last_api_error_details',
            'sync_last_rate_limit_hits',
            'sync_last_delta_mode',
            'sync_last_scope',
        ];
        $out = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $run_state)) {
                $out[$key] = $run_state[$key];
                continue;
            }
            $row = Simplemdm_config_model::where('name', $key)->first();
            $out[$key] = $row ? (string)$row->value : '';
        }
        $out['sync_recent_runs'] = isset($run_state['sync_recent_runs']) ? $run_state['sync_recent_runs'] : [];
        jsonView($out);
    }

    /**
     * Retrieve historical dashboard trend rows from snapshots.
     *
     * @return void
     **/
    public function get_dashboard_trend()
    {
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        if ($days < 1) {
            $days = 30;
        }
        if ($days > 180) {
            $days = 180;
        }

        $since = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
        $rows = Simplemdm_dashboard_snapshot_model::where('snapshot_time', '>=', $since)
            ->orderBy('snapshot_time', 'asc')
            ->get();

        $by_day = [];
        foreach ($rows as $row) {
            $day = substr((string)$row->snapshot_time, 0, 10);
            // Keep the latest snapshot for each day.
            $by_day[$day] = [
                'date' => $day,
                'snapshot_time' => (string)$row->snapshot_time,
                'device_total' => (int)$row->device_total,
                'enrolled_total' => (int)$row->enrolled_total,
                'unenrolled_total' => (int)$row->unenrolled_total,
                'supervised_total' => (int)$row->supervised_total,
                'filevault_enabled_total' => (int)$row->filevault_enabled_total,
                'dep_enrolled_total' => (int)$row->dep_enrolled_total,
                'resource_total' => (int)$row->resource_total,
            ];
        }

        $series = array_values($by_day);
        usort($series, function ($a, $b) {
            return strcmp((string)$a['date'], (string)$b['date']);
        });

        if (empty($series)) {
            $device_total = (int) Simplemdm_model::count();
            $enrolled_total = (int) Simplemdm_model::where('status', 'enrolled')->count();
            $series[] = [
                'date' => date('Y-m-d'),
                'snapshot_time' => date('Y-m-d H:i:s'),
                'device_total' => $device_total,
                'enrolled_total' => $enrolled_total,
                'unenrolled_total' => max(0, $device_total - $enrolled_total),
                'supervised_total' => (int) Simplemdm_model::where('is_supervised', 1)->count(),
                'filevault_enabled_total' => (int) Simplemdm_model::where('filevault_enabled', 1)->count(),
                'dep_enrolled_total' => (int) Simplemdm_model::where('is_dep_enrollment', 1)->count(),
                'resource_total' => (int) Simplemdm_resource_model::count(),
            ];
        }

        jsonView([
            'days' => $days,
            'has_history' => count($series) > 1,
            'series' => $series,
        ]);
    }

    /**
     * Retrieve stacked security/compliance metrics by OS version.
     *
     * @return void
     **/
    public function get_os_security_stats()
    {
        $sql = "
            SELECT
                COALESCE(NULLIF(TRIM(os_version), ''), 'Unknown') AS os_version,
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'enrolled' THEN 1 ELSE 0 END) AS enrolled_total,
                SUM(CASE WHEN is_supervised = 1 THEN 1 ELSE 0 END) AS supervised_total,
                SUM(CASE WHEN filevault_enabled = 1 THEN 1 ELSE 0 END) AS filevault_total
            FROM simplemdm
            GROUP BY COALESCE(NULLIF(TRIM(os_version), ''), 'Unknown')
            ORDER BY total DESC
        ";

        $rows = getdbh()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $limit = 8;
        if (count($rows) > $limit) {
            $head = array_slice($rows, 0, $limit - 1);
            $tail = array_slice($rows, $limit - 1);
            $other = [
                'os_version' => 'Other',
                'total' => 0,
                'enrolled_total' => 0,
                'supervised_total' => 0,
                'filevault_total' => 0,
            ];
            foreach ($tail as $row) {
                $other['total'] += (int)($row['total'] ?? 0);
                $other['enrolled_total'] += (int)($row['enrolled_total'] ?? 0);
                $other['supervised_total'] += (int)($row['supervised_total'] ?? 0);
                $other['filevault_total'] += (int)($row['filevault_total'] ?? 0);
            }
            $rows = $head;
            if ($other['total'] > 0) {
                $rows[] = $other;
            }
        }

        jsonView($rows);
    }

    /**
     * Authenticated passthrough for SimpleMDM /devices endpoints and actions.
     *
     * Examples:
     * - GET    /module/simplemdm/api_devices
     * - GET    /module/simplemdm/api_devices/{id}
     * - POST   /module/simplemdm/api_devices/{id}/restart
     * - DELETE /module/simplemdm/api_devices/{id}/users/{user_id}
     *
     * @param string $device_id
     * @param string $subpath
     * @param string $subpath_id
     * @return void
     **/
    public function api_devices($device_id = '', $subpath = '', $subpath_id = '')
    {
        $this->connectDB();
        if (! $this->require_global_authorized()) {
            return;
        }

        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : 'GET';
        $allowed = $this->simplemdm_allowed_methods_for_device_path($device_id, $subpath, $subpath_id);
        if (empty($allowed) || ! in_array($method, $allowed, true)) {
            jsonView(
                [
                    'status' => 'error',
                    'message' => 'Method/path not allowed for device passthrough',
                    'allowed_methods' => array_values($allowed),
                ],
                405
            );
            return;
        }

        $endpoint = 'devices';
        $device_id = trim((string)$device_id);
        $subpath = trim((string)$subpath);
        $subpath_id = trim((string)$subpath_id);

        if ($device_id !== '') {
            $endpoint .= '/' . rawurlencode($device_id);
        }
        if ($subpath !== '') {
            $endpoint .= '/' . rawurlencode($subpath);
        }
        if ($subpath_id !== '') {
            $endpoint .= '/' . rawurlencode($subpath_id);
        }

        $query = $_GET;

        $input = file_get_contents('php://input');
        if ($input === false) {
            $input = '';
        }

        $content_type = '';
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $content_type = trim((string)$_SERVER['CONTENT_TYPE']);
        } elseif (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
            $content_type = trim((string)$_SERVER['HTTP_CONTENT_TYPE']);
        }
        if ($content_type !== '' && strpos($content_type, ';') !== false) {
            $content_type = trim((string)explode(';', $content_type, 2)[0]);
        }

        if ($input === '' && ! empty($_POST) && $method !== 'GET') {
            $input = http_build_query($_POST);
            if ($content_type === '') {
                $content_type = 'application/x-www-form-urlencoded';
            }
        }

        $mutating_methods = ['POST', 'PATCH', 'DELETE', 'PUT'];
        if (in_array($method, $mutating_methods, true)) {
            $provided_secret = $this->extract_action_secret_from_request($input, $content_type);
            if (! $this->is_valid_action_secret($provided_secret)) {
                jsonView(['status' => 'error', 'message' => 'Invalid or missing action secret'], 401);
                return;
            }
        }

        list($query, $input, $content_type) = $this->sanitize_passthrough_payload($query, $input, $content_type);

        $res = $this->simplemdm_api_proxy_request($endpoint, $method, $query, $input, $content_type);
        if (in_array($method, $mutating_methods, true)) {
            $device = $this->find_device_by_serial_or_id('', $device_id);
            if ($device && trim((string) ($device->serial_number ?? '')) !== '') {
                $action = $subpath !== '' ? $subpath : 'device_update';
                if ($res['ok']) {
                    $this->delete_simplemdm_event((string) $device->serial_number, 'action_failure');
                } else {
                    $message = 'SimpleMDM: admin action failed: ' . str_replace('_', ' ', $action);
                    $error_data = [
                        'source' => 'simplemdm',
                        'reason' => 'admin_action_failed',
                        'serial_number' => (string) $device->serial_number,
                        'simplemdm_id' => (string) ($device->simplemdm_id ?? $device_id),
                        'action' => $action,
                        'method' => $method,
                        'endpoint' => $endpoint,
                        'http_status' => (int) ($res['status'] ?? 0),
                    ];
                    $body = isset($res['body']) ? trim((string) $res['body']) : '';
                    if ($body !== '') {
                        $error_data['response_body'] = truncate_string($body, 500);
                    }
                    $this->store_simplemdm_event(
                        (string) $device->serial_number,
                        'action_failure',
                        'danger',
                        $message,
                        $error_data
                    );
                }

                if ($res['ok']) {
                    $this->store_simplemdm_event(
                        (string) $device->serial_number,
                        'action',
                        'info',
                        'SimpleMDM: admin action accepted: ' . str_replace('_', ' ', $action),
                        [
                            'source' => 'simplemdm',
                            'reason' => 'admin_action_accepted',
                            'serial_number' => (string) $device->serial_number,
                            'simplemdm_id' => (string) ($device->simplemdm_id ?? $device_id),
                            'action' => $action,
                            'method' => $method,
                            'endpoint' => $endpoint,
                        ]
                    );
                }
            }
        }

        http_response_code((int)$res['status']);
        header('Content-Type: application/json');

        $body = isset($res['body']) ? (string)$res['body'] : '';
        if ($body === '' || (int)$res['status'] === 204) {
            echo json_encode(['status' => $res['ok'] ? 'success' : 'error', 'http_status' => (int)$res['status']]);
            return;
        }

        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            echo $body;
            return;
        }

        echo json_encode([
            'status' => $res['ok'] ? 'success' : 'error',
            'http_status' => (int)$res['status'],
            'raw' => $body,
        ]);
    }

    /**
     * Trigger a sync for a specific device.
     *
     * @param string $serial_number
     * @return void
     **/
    public function sync_device($serial_number = '')
    {
        if (! $serial_number) {
            jsonView(['status' => 'error', 'message' => 'No serial number provided']);
            return;
        }

        // Logic here would ideally trigger the python script for just one serial
        // For now, we'll return a message that the request was received
        // In a real environment, this might use a task queue
        jsonView(['status' => 'success', 'message' => 'Sync requested for ' . $serial_number]);
    }
} // End class Simplemdm_controller

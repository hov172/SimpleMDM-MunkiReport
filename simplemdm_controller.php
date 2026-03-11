<?php

/**
 * SimpleMDM module class
 *
 * @package munkireport
 * @author simplemdm module
 **/
class Simplemdm_controller extends Module_controller
{
    private $sync_actions = ['ingest', 'ingest_resources', 'ingest_commands', 'webhook', 'update_sync_status', 'begin_sync_run', 'get_config'];
    private $downloadable_scripts = ['simplemdm_sync.py', 'install_cron.sh', 'remove_cron.sh'];

    function __construct()
    {
        // Store module path
        $this->module_path = dirname(__FILE__);
        require_once $this->module_path . '/simplemdm_config_model.php';
        require_once $this->module_path . '/simplemdm_resource_model.php';
        require_once $this->module_path . '/simplemdm_dashboard_snapshot_model.php';
        require_once $this->module_path . '/simplemdm_command_model.php';
        require_once $this->module_path . '/simplemdm_webhook_event_model.php';
        require_once $this->module_path . '/simplemdm_relationship_edge_model.php';
        require_once $this->module_path . '/simplemdm_device_history_model.php';

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
            }
        }

        if (! $is_sync_action && ! $this->authorized()) {
            die('Authenticate first.');
        }
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
            "%s %s --api-key 'YOUR_SIMPLEMDM_API_KEY' --munkireport-url %s --force-run --max-parent-resources %s --verbose",
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

        $command = 'command -v ' . escapeshellarg($python_binary) . ' 2>/dev/null || test -x ' . escapeshellarg($python_binary) . ' && printf %s ' . escapeshellarg($python_binary);
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
            $config[$setting->name] = $setting->value;
        }

        // Normalize runner settings so blank stored values still use derived defaults.
        $runner_config = $this->get_script_runner_config();
        foreach ($runner_config as $key => $value) {
            if (! isset($config[$key]) || trim((string) $config[$key]) === '') {
                $config[$key] = $value;
            }
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
            'sync_last_rate_limit_hits',
            'sync_last_delta_mode',
            'sync_last_scope',
            'allow_module_script_execution',
            'script_runner_munkireport_url',
            'script_runner_python_bin',
            'script_runner_schedule',
            'script_runner_log_path',
            'script_runner_max_parent_resources',
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
                }
                Simplemdm_config_model::updateOrCreate(
                    ['name' => $key],
                    ['value' => $value]
                );
                $updated = true;
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

        $commands = [
            'sync_now' => sprintf(
                "%s %s --api-key %s --munkireport-url %s --force-run --max-parent-resources %s --verbose",
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
        if ($state === 'running') {
            jsonView(['status' => 'success', 'message' => 'A sync is already running.', 'sync_request_state' => 'running']);
            return;
        }

        $requested_at = date('c');
        $this->set_config_value('sync_request_state', 'queued');
        $this->set_config_value('sync_requested_at', $requested_at);
        $this->set_config_value('sync_request_source', 'admin');

        jsonView([
            'status' => 'success',
            'message' => 'Sync queued. The next cron or manual runner execution will pick it up.',
            'sync_request_state' => 'queued',
            'sync_requested_at' => $requested_at,
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
        $this->set_config_value('sync_request_state', 'running');
        $this->set_config_value('sync_started_at', $started_at);

        jsonView([
            'status' => 'success',
            'sync_request_state' => 'running',
            'sync_started_at' => $started_at,
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

        try {
            $processor->run($payload);
            $this->upsert_device_edges_from_payload($payload);
            $this->record_device_daily_history();
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
            $count++;
        }

        jsonView(['status' => 'success', 'count' => $count]);
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
                    'device_name' => isset($attrs['device_name']) ? (string)$attrs['device_name'] : null,
                    'status' => isset($attrs['status']) ? (string)$attrs['status'] : null,
                    'os_version' => isset($attrs['os_version']) ? (string)$attrs['os_version'] : null,
                    'is_supervised' => isset($attrs['is_supervised']) ? $attrs['is_supervised'] : null,
                    'is_dep_enrollment' => isset($attrs['is_dep_enrollment']) ? $attrs['is_dep_enrollment'] : null,
                    'filevault_enabled' => isset($attrs['filevault_enabled']) ? $attrs['filevault_enabled'] : null,
                    'attributes_json' => $attrs,
                    'relationships_json' => isset($event_data['relationships']) ? $event_data['relationships'] : [],
                ];
                try {
                    $processor->run(json_encode($record));
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
        $this->set_config_value('sync_request_state', $state_value);
        if ($state_value !== 'running') {
            $this->set_config_value('sync_started_at', '');
        }

        $telemetry_keys = [
            'sync_last_duration_ms',
            'sync_last_api_requests',
            'sync_last_api_errors',
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
        jsonView(
            Simplemdm_model::select(
                'simplemdm.serial_number',
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
                ->leftJoin('reportdata', 'reportdata.serial_number', '=', 'simplemdm.serial_number')
                ->get()
                ->toArray()
        );
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
        $type = '';
        $resource_id = '';
        $endpoint = '';
        if (isset($_GET['type'])) {
            $type = trim((string)$_GET['type']);
        }
        if (isset($_GET['resource_id'])) {
            $resource_id = trim((string)$_GET['resource_id']);
        }
        if (isset($_GET['endpoint'])) {
            $endpoint = trim((string)$_GET['endpoint']);
        }

        $query = Simplemdm_resource_model::select(
            'resource_type',
            'resource_id',
            'name',
            'source_endpoint',
            'synced_at',
            'attributes_json',
            'data_json'
        );

        if ($type !== '') {
            $query->where('resource_type', $type);
        }
        if ($resource_id !== '') {
            $query->where('resource_id', $resource_id);
        }
        if ($endpoint !== '') {
            $query->where('source_endpoint', $endpoint);
        }

        $rows = $query->orderBy('resource_type')
            ->orderBy('name')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'resource_type' => (string)$row->resource_type,
                'resource_id' => (string)$row->resource_id,
                'name' => $this->derive_resource_name_from_row($row),
                'source_endpoint' => (string)$row->source_endpoint,
                'synced_at' => (string)$row->synced_at,
            ];
        }

        jsonView($out);
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
        $candidates = Simplemdm_resource_model::where(function ($q) use ($device_id, $serial, $udid) {
            $q->where(function ($sub) use ($device_id) {
                $sub->where('resource_type', 'device')
                    ->where('resource_id', $device_id);
            })
                ->orWhere('resource_type', 'installed_app')
                ->orWhere('source_endpoint', 'like', '%/' . $device_id . '/%')
                ->orWhere('data_json', 'like', '%"type":"device"%')
                ->orWhere('relationships_json', 'like', '%"type":"device"%')
                ->orWhere('data_json', 'like', '%"id":"' . $device_id . '"%')
                ->orWhere('data_json', 'like', '%"id":' . $device_id . '%')
                ->orWhere('relationships_json', 'like', '%"id":"' . $device_id . '"%')
                ->orWhere('relationships_json', 'like', '%"id":' . $device_id . '%')
                ->orWhere('data_json', 'like', '%"serial_number":"' . $serial . '"%')
                ->orWhere('relationships_json', 'like', '%"serial_number":"' . $serial . '"%');

            if ($udid !== '') {
                $q->orWhere('data_json', 'like', '%"unique_identifier":"' . $udid . '"%')
                    ->orWhere('relationships_json', 'like', '%"unique_identifier":"' . $udid . '"%');
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
        $keys = [
            'sync_request_state',
            'sync_requested_at',
            'sync_started_at',
            'sync_request_source',
            'last_sync_status',
            'last_sync_time',
            'last_sync_cursor',
            'sync_last_duration_ms',
            'sync_last_api_requests',
            'sync_last_api_errors',
            'sync_last_rate_limit_hits',
            'sync_last_delta_mode',
            'sync_last_scope',
        ];
        $out = [];
        foreach ($keys as $key) {
            $row = Simplemdm_config_model::where('name', $key)->first();
            $out[$key] = $row ? (string)$row->value : '';
        }
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

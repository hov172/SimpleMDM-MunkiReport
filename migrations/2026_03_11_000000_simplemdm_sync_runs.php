<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmSyncRuns extends Migration
{
    public function up()
    {
        $capsule = new Capsule();

        if (! $capsule::schema()->hasTable('simplemdm_sync_run')) {
            $capsule::schema()->create('simplemdm_sync_run', function (Blueprint $table) {
                $table->increments('id');
                $table->string('run_uuid')->unique();
                $table->string('source')->nullable();
                $table->string('status')->nullable();
                $table->dateTime('requested_at')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('finished_at')->nullable();
                $table->integer('duration_ms')->nullable();
                $table->integer('devices_synced')->nullable();
                $table->integer('resources_synced')->nullable();
                $table->integer('commands_synced')->nullable();
                $table->integer('api_requests')->nullable();
                $table->integer('api_errors')->nullable();
                $table->integer('rate_limit_hits')->nullable();
                $table->boolean('delta_mode')->nullable();
                $table->string('scope')->nullable();
                $table->text('summary')->nullable();
                $table->text('error_summary')->nullable();
                $table->string('requested_by')->nullable();
                $table->string('trigger_context')->nullable();
                $table->timestamps();

                $table->index('status');
                $table->index('source');
                $table->index('requested_at');
                $table->index('started_at');
                $table->index('finished_at');
            });
        }

        $defaults = [
            'sync_pending_source' => '',
        ];
        foreach ($defaults as $name => $value) {
            $row = $capsule::table('simplemdm_config')->where('name', $name)->first();
            if (! $row) {
                $capsule::table('simplemdm_config')->insert([
                    'name' => $name,
                    'value' => $value,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        if ((int) $capsule::table('simplemdm_sync_run')->count() > 0) {
            return;
        }

        $config_rows = $capsule::table('simplemdm_config')
            ->whereIn('name', [
                'sync_request_state',
                'sync_requested_at',
                'sync_started_at',
                'sync_request_source',
                'sync_pending_source',
                'last_sync_status',
                'last_sync_time',
                'sync_last_duration_ms',
                'sync_last_api_requests',
                'sync_last_api_errors',
                'sync_last_rate_limit_hits',
                'sync_last_delta_mode',
                'sync_last_scope',
            ])
            ->get();
        $config = [];
        foreach ($config_rows as $row) {
            $config[(string) $row->name] = (string) $row->value;
        }

        $state = strtolower(trim((string) ($config['sync_request_state'] ?? 'idle')));
        $requestedAt = $this->normalizeDate($config['sync_requested_at'] ?? '');
        $startedAt = $this->normalizeDate($config['sync_started_at'] ?? '');
        $source = trim((string) ($config['sync_request_source'] ?? ''));
        $pendingSource = trim((string) ($config['sync_pending_source'] ?? ''));
        $lastStatus = strtolower(trim((string) ($config['last_sync_status'] ?? '')));
        $lastSummary = trim((string) ($config['last_sync_time'] ?? ''));
        $lastFinishedAt = $this->normalizeDate($lastSummary);

        if (in_array($state, ['queued', 'running'], true)) {
            $capsule::table('simplemdm_sync_run')->insert([
                'run_uuid' => $this->generateUuid(),
                'source' => $state === 'queued' ? ($pendingSource !== '' ? $pendingSource : 'queued_admin') : ($source !== '' ? $source : 'scheduled'),
                'status' => $state,
                'requested_at' => $requestedAt ?: date('Y-m-d H:i:s'),
                'started_at' => $state === 'running' ? ($startedAt ?: $requestedAt ?: date('Y-m-d H:i:s')) : null,
                'finished_at' => null,
                'summary' => $state === 'running' ? $lastSummary : null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($lastStatus !== '' && ! in_array($lastStatus, ['running', 'queued'], true) && $lastSummary !== '') {
            $counts = $this->extractCounts($lastSummary);
            $capsule::table('simplemdm_sync_run')->insert([
                'run_uuid' => $this->generateUuid(),
                'source' => $source !== '' ? $source : 'legacy',
                'status' => $lastStatus,
                'requested_at' => $lastFinishedAt ?: date('Y-m-d H:i:s'),
                'started_at' => $lastFinishedAt ?: date('Y-m-d H:i:s'),
                'finished_at' => $lastFinishedAt ?: date('Y-m-d H:i:s'),
                'duration_ms' => (int) ($config['sync_last_duration_ms'] ?? 0),
                'devices_synced' => $counts['devices'],
                'resources_synced' => $counts['resources'],
                'commands_synced' => $counts['commands'],
                'api_requests' => (int) ($config['sync_last_api_requests'] ?? 0),
                'api_errors' => (int) ($config['sync_last_api_errors'] ?? 0),
                'rate_limit_hits' => (int) ($config['sync_last_rate_limit_hits'] ?? 0),
                'delta_mode' => ((string) ($config['sync_last_delta_mode'] ?? '0')) === '1' ? 1 : 0,
                'scope' => (string) ($config['sync_last_scope'] ?? ''),
                'summary' => $lastSummary,
                'error_summary' => $lastStatus === 'success' ? null : $lastSummary,
                'trigger_context' => 'legacy_backfill',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down()
    {
        $capsule = new Capsule();
        $capsule::schema()->dropIfExists('simplemdm_sync_run');
        $capsule::table('simplemdm_config')
            ->whereIn('name', ['sync_pending_source'])
            ->delete();
    }

    private function normalizeDate($value)
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
            $dt = new DateTime($raw);
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null;
        }
    }

    private function extractCounts($summary)
    {
        $counts = ['devices' => null, 'resources' => null, 'commands' => null];
        $summary = (string) $summary;
        if (preg_match('/([0-9]+)\s+devices?/i', $summary, $m)) {
            $counts['devices'] = (int) $m[1];
        }
        if (preg_match('/([0-9]+)\s+resources?/i', $summary, $m)) {
            $counts['resources'] = (int) $m[1];
        }
        if (preg_match('/([0-9]+)\s+commands?/i', $summary, $m)) {
            $counts['commands'] = (int) $m[1];
        }
        return $counts;
    }

    private function generateUuid()
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (Exception $e) {
            return uniqid('simplemdm_run_', true);
        }
    }
}

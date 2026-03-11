<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_sync_run_model extends Eloquent
{
    protected $table = 'simplemdm_sync_run';

    protected $fillable = [
        'run_uuid',
        'source',
        'status',
        'requested_at',
        'started_at',
        'finished_at',
        'duration_ms',
        'devices_synced',
        'resources_synced',
        'commands_synced',
        'api_requests',
        'api_errors',
        'rate_limit_hits',
        'delta_mode',
        'scope',
        'summary',
        'error_summary',
        'requested_by',
        'trigger_context',
    ];

    public $timestamps = true;
}

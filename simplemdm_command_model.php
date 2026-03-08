<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_command_model extends Eloquent
{
    protected $table = 'simplemdm_command';

    protected $fillable = [
        'command_uuid',
        'command_type',
        'status',
        'device_id',
        'serial_number',
        'resource_id',
        'error_message',
        'issued_at',
        'completed_at',
        'updated_at',
        'raw_json',
    ];

    public $timestamps = false;
}

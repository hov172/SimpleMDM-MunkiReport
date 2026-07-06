<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_mcp_finding_model extends Eloquent
{
    protected $table = 'simplemdm_mcp_finding';

    protected $fillable = [
        'serial_number',
        'source',
        'finding_type',
        'severity',
        'message',
        'data',
        'reported_at',
    ];

    public $timestamps = false;
}

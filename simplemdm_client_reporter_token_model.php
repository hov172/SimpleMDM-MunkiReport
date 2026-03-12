<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_client_reporter_token_model extends Eloquent
{
    protected $table = 'simplemdm_client_reporter_token';

    protected $fillable = [
        'serial_number',
        'label',
        'token_hash',
        'enabled',
        'last_used_at',
        'created_at',
        'updated_at',
    ];

    public $timestamps = false;
}

<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_client_reporter_nonce_model extends Eloquent
{
    protected $table = 'simplemdm_client_reporter_nonce';

    protected $fillable = [
        'nonce_hash',
        'serial_number',
        'request_ip',
        'observed_at',
        'created_at',
        'updated_at',
    ];

    public $timestamps = false;
}

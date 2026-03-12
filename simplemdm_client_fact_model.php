<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_client_fact_model extends Eloquent
{
    protected $table = 'simplemdm_client_fact';

    protected $fillable = [
        'serial_number',
        'fact_type',
        'fact_key',
        'fact_value_string',
        'fact_value_int',
        'fact_value_bool',
        'fact_value_json',
        'reported_at',
        'source',
        'client_version',
        'raw_json',
        'updated_at',
    ];

    public $timestamps = false;
}

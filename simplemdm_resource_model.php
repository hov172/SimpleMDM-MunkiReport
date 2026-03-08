<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_resource_model extends Eloquent
{
    protected $table = 'simplemdm_resource';

    protected $fillable = [
        'resource_type',
        'resource_id',
        'source_endpoint',
        'name',
        'attributes_json',
        'relationships_json',
        'data_json',
        'synced_at',
    ];

    public $timestamps = false;
}

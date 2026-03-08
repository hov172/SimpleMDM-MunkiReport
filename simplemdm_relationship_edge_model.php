<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_relationship_edge_model extends Eloquent
{
    protected $table = 'simplemdm_relationship_edge';

    protected $fillable = [
        'serial_number',
        'source_kind',
        'source_type',
        'source_id',
        'target_type',
        'target_id',
        'source_endpoint',
        'last_seen_at',
    ];

    public $timestamps = false;
}

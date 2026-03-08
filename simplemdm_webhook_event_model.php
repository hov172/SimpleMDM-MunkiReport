<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_webhook_event_model extends Eloquent
{
    protected $table = 'simplemdm_webhook_event';

    protected $fillable = [
        'event_id',
        'event_type',
        'status',
        'received_at',
        'source_ip',
        'payload_json',
    ];

    public $timestamps = false;
}

<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_dashboard_snapshot_model extends Eloquent
{
    protected $table = 'simplemdm_dashboard_snapshot';

    protected $fillable = [
        'snapshot_time',
        'device_total',
        'enrolled_total',
        'unenrolled_total',
        'supervised_total',
        'filevault_enabled_total',
        'dep_enrolled_total',
        'resource_total',
    ];

    public $timestamps = false;
}

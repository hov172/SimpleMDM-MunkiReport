<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_device_history_model extends Eloquent
{
    protected $table = 'simplemdm_device_history';

    protected $fillable = [
        'serial_number',
        'snapshot_date',
        'status',
        'os_version',
        'assignment_group',
        'is_supervised',
        'is_dep_enrollment',
        'filevault_enabled',
        'updated_at',
    ];

    public $timestamps = false;
}

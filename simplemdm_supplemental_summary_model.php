<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_supplemental_summary_model extends Eloquent
{
    protected $table = 'simplemdm_supplemental_summary';

    protected $fillable = [
        'serial_number',
        'source_modules_json',
        'last_refresh',
        'last_refresh_status',
        'source_freshness_json',
        'filevault_present',
        'filevault_enabled',
        'findmymac_present',
        'findmymac_enabled',
        'applecare_present',
        'applecare_coverage_end',
        'applecare_coverage_status',
        'profile_present',
        'profile_count',
        'managedinstalls_present',
        'managedinstalls_warning_count',
        'managedinstalls_error_count',
    ];

    public $timestamps = false;
}

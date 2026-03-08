<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_model extends Eloquent
{
    protected $table = 'simplemdm';

    protected $fillable = [
        'serial_number',
        'simplemdm_id',
        'device_name',
        'status',
        'enrolled_at',
        'last_seen_at',
        'last_seen_ip',
        'model_name',
        'os_version',
        'build_version',
        'is_supervised',
        'is_dep_enrollment',
        'dep_enrolled',
        'dep_assigned',
        'filevault_enabled',
        'firewall_enabled',
        'sip_enabled',
        'remote_desktop_enabled',
        'activation_lock_enabled',
        'passcode_compliant',
        'device_capacity',
        'available_device_capacity',
        'battery_level',
        'assignment_group',
        'unique_identifier',
        'imei',
        'meid',
        'iccid',
        'phone_number',
        'bluetooth_mac',
        'wifi_mac',
        'current_carrier_network',
        'personal_hotspot_enabled',
        'cellular_technology',
        'modem_firmware_version',
        'custom_attributes',
        'attributes_json',
        'relationships_json',
    ];

    public $timestamps = false;

    /**
     * Search for a specific item in the SimpleMDM table.
     * Used by MunkiReport's global search.
     *
     * @param string $item The item to search for
     * @return array
     **/
    public function search_item($item)
    {
        return $this->select('serial_number', 'device_name AS label', 'status AS sublabel')
            ->where('serial_number', 'LIKE', "%{$item}%")
            ->orWhere('device_name', 'LIKE', "%{$item}%")
            ->orWhere('unique_identifier', 'LIKE', "%{$item}%")
            ->orWhere('imei', 'LIKE', "%{$item}%")
            ->filter()
            ->get()
            ->toArray();
    }
}

<?php

use munkireport\processors\Processor;

class Simplemdm_processor extends Processor
{
    /**
     * Process data sent by the SimpleMDM sync script.
     * Expects JSON data with device attributes.
     *
     * @param string $json_data JSON encoded device data
     * @author simplemdm module
     **/
    public function run($json_data)
    {
        // Check if we have data
        if (! $json_data) {
            throw new Exception("Error Processing Request: No data received", 1);
        }

        $data = json_decode($json_data, true);

        if (! is_array($data)) {
            throw new Exception("Error Processing Request: Invalid JSON data", 1);
        }

        // If we receive a single device record
        if (isset($data['serial_number'])) {
            $this->processDevice($data);
            return;
        }

        // If we receive an array of device records
        if (isset($data[0]) && isset($data[0]['serial_number'])) {
            foreach ($data as $device) {
                $this->processDevice($device);
            }
            return;
        }

        throw new Exception("Error Processing Request: No serial_number found in data", 1);
    }

    /**
     * Process a single device record.
     * Upserts the device data based on serial_number.
     *
     * @param array $device Device data array
     **/
    private function processDevice($device)
    {
        if (empty($device['serial_number'])) {
            return;
        }

        // Get fillable fields from the model
        $fillable = array_fill_keys((new Simplemdm_model)->getFillable(), null);
        $fillable['serial_number'] = $device['serial_number'];

        // Intersect submitted data with fillable fields
        $record = array_replace($fillable, array_intersect_key($device, $fillable));

        // Cast boolean fields
        $boolFields = [
            'is_supervised', 'is_dep_enrollment', 'dep_enrolled', 'dep_assigned',
            'filevault_enabled', 'firewall_enabled', 'sip_enabled',
            'remote_desktop_enabled', 'activation_lock_enabled', 'passcode_compliant',
            'personal_hotspot_enabled',
        ];

        foreach ($boolFields as $field) {
            if (isset($record[$field])) {
                $record[$field] = $record[$field] ? 1 : 0;
            }
        }

        // Handle Custom Attributes (convert array to JSON string)
        if (isset($device['custom_attributes']) && is_array($device['custom_attributes'])) {
            $record['custom_attributes'] = json_encode($device['custom_attributes']);
        }

        // Store full payload JSON fields for complete API coverage.
        $jsonFields = ['attributes_json', 'relationships_json'];
        foreach ($jsonFields as $field) {
            if (isset($device[$field]) && is_array($device[$field])) {
                $record[$field] = json_encode($device[$field]);
            }
        }

        // Upsert: update if exists, insert if new
        Simplemdm_model::updateOrCreate(
            ['serial_number' => $record['serial_number']],
            $record
        );
    }
}

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class Simplemdm extends Migration
{
    private $tableName = 'simplemdm';
    private $tableNameV2 = 'simplemdm_orig';

    public function up()
    {
        $capsule = new Capsule();
        $connType = $capsule::connection()->getConfig('driver');

        if ($capsule::schema()->hasTable($this->tableName)) {
            // Rename existing table for backup
            $capsule::schema()->rename($this->tableName, $this->tableNameV2);
        }

        $capsule::schema()->create($this->tableName, function (Blueprint $table) {
            $table->increments('id');
            $table->string('serial_number')->unique();
            $table->integer('simplemdm_id')->nullable();
            $table->string('device_name')->nullable();
            $table->string('status')->nullable();
            $table->string('enrolled_at')->nullable();
            $table->string('last_seen_at')->nullable();
            $table->string('last_seen_ip')->nullable();
            $table->string('model_name')->nullable();
            $table->string('os_version')->nullable();
            $table->string('build_version')->nullable();
            $table->boolean('is_supervised')->nullable();
            $table->boolean('is_dep_enrollment')->nullable();
            $table->boolean('dep_enrolled')->nullable();
            $table->boolean('dep_assigned')->nullable();
            $table->boolean('filevault_enabled')->nullable();
            $table->boolean('firewall_enabled')->nullable();
            $table->boolean('sip_enabled')->nullable();
            $table->boolean('remote_desktop_enabled')->nullable();
            $table->boolean('activation_lock_enabled')->nullable();
            $table->boolean('passcode_compliant')->nullable();
            $table->float('device_capacity')->nullable();
            $table->float('available_device_capacity')->nullable();
            $table->string('battery_level')->nullable();
            $table->string('assignment_group')->nullable();
            $table->string('unique_identifier')->nullable();
            $table->string('imei')->nullable();
            $table->string('meid')->nullable();
            $table->string('iccid')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('bluetooth_mac')->nullable();
            $table->string('wifi_mac')->nullable();
            $table->string('current_carrier_network')->nullable();
            $table->boolean('personal_hotspot_enabled')->nullable();
            $table->string('cellular_technology')->nullable();
            $table->string('modem_firmware_version')->nullable();
            $table->text('custom_attributes')->nullable();

            $table->index('serial_number');
            $table->index('simplemdm_id');
            $table->index('status');
        });

        if ($capsule::schema()->hasTable($this->tableNameV2)) {
            // Migrate data from old table
            $capsule::unprepared("INSERT INTO {$this->tableName} SELECT * FROM {$this->tableNameV2}");
            $capsule::schema()->drop($this->tableNameV2);
        }
    }

    public function down()
    {
        $capsule = new Capsule();
        $capsule::schema()->dropIfExists($this->tableName);
    }
}

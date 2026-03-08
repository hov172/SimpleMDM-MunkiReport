<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmAddFields extends Migration
{
    private $tableName = 'simplemdm';

    public function up()
    {
        $capsule = new Capsule();

        if (! $capsule::schema()->hasColumn($this->tableName, 'unique_identifier')) {
            $capsule::schema()->table($this->tableName, function (Blueprint $table) {
                $table->string('unique_identifier')->nullable();
            });
        }
        if (! $capsule::schema()->hasColumn($this->tableName, 'imei')) {
            $capsule::schema()->table($this->tableName, function (Blueprint $table) {
                $table->string('imei')->nullable();
            });
        }
        if (! $capsule::schema()->hasColumn($this->tableName, 'meid')) {
            $capsule::schema()->table($this->tableName, function (Blueprint $table) {
                $table->string('meid')->nullable();
            });
        }
        if (! $capsule::schema()->hasColumn($this->tableName, 'iccid')) {
            $capsule::schema()->table($this->tableName, function (Blueprint $table) {
                $table->string('iccid')->nullable();
            });
        }
        if (! $capsule::schema()->hasColumn($this->tableName, 'phone_number')) {
            $capsule::schema()->table($this->tableName, function (Blueprint $table) {
                $table->string('phone_number')->nullable();
            });
        }
        if (! $capsule::schema()->hasColumn($this->tableName, 'bluetooth_mac')) {
            $capsule::schema()->table($this->tableName, function (Blueprint $table) {
                $table->string('bluetooth_mac')->nullable();
            });
        }
        if (! $capsule::schema()->hasColumn($this->tableName, 'wifi_mac')) {
            $capsule::schema()->table($this->tableName, function (Blueprint $table) {
                $table->string('wifi_mac')->nullable();
            });
        }
        if (! $capsule::schema()->hasColumn($this->tableName, 'current_carrier_network')) {
            $capsule::schema()->table($this->tableName, function (Blueprint $table) {
                $table->string('current_carrier_network')->nullable();
            });
        }
        if (! $capsule::schema()->hasColumn($this->tableName, 'personal_hotspot_enabled')) {
            $capsule::schema()->table($this->tableName, function (Blueprint $table) {
                $table->boolean('personal_hotspot_enabled')->nullable();
            });
        }
        if (! $capsule::schema()->hasColumn($this->tableName, 'cellular_technology')) {
            $capsule::schema()->table($this->tableName, function (Blueprint $table) {
                $table->string('cellular_technology')->nullable();
            });
        }
        if (! $capsule::schema()->hasColumn($this->tableName, 'modem_firmware_version')) {
            $capsule::schema()->table($this->tableName, function (Blueprint $table) {
                $table->string('modem_firmware_version')->nullable();
            });
        }
    }

    public function down()
    {
        $capsule = new Capsule();
        $capsule::schema()->table($this->tableName, function (Blueprint $table) {
            $table->dropColumn([
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
                'modem_firmware_version'
            ]);
        });
    }
}

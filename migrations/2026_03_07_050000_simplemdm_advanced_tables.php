<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmAdvancedTables extends Migration
{
    public function up()
    {
        $capsule = new Capsule();

        if (! $capsule::schema()->hasTable('simplemdm_command')) {
            $capsule::schema()->create('simplemdm_command', function (Blueprint $table) {
                $table->increments('id');
                $table->string('command_uuid')->nullable();
                $table->string('command_type')->nullable();
                $table->string('status')->nullable();
                $table->string('device_id')->nullable();
                $table->string('serial_number')->nullable();
                $table->string('resource_id')->nullable();
                $table->text('error_message')->nullable();
                $table->string('issued_at')->nullable();
                $table->string('completed_at')->nullable();
                $table->string('updated_at')->nullable();
                $table->text('raw_json')->nullable();

                $table->unique('command_uuid');
                $table->index('status');
                $table->index('serial_number');
                $table->index('device_id');
            });
        }

        if (! $capsule::schema()->hasTable('simplemdm_webhook_event')) {
            $capsule::schema()->create('simplemdm_webhook_event', function (Blueprint $table) {
                $table->increments('id');
                $table->string('event_id')->nullable();
                $table->string('event_type')->nullable();
                $table->string('status')->nullable();
                $table->dateTime('received_at')->nullable();
                $table->string('source_ip')->nullable();
                $table->text('payload_json')->nullable();

                $table->index('event_type');
                $table->index('status');
                $table->index('received_at');
                $table->unique('event_id');
            });
        }

        if (! $capsule::schema()->hasTable('simplemdm_relationship_edge')) {
            $capsule::schema()->create('simplemdm_relationship_edge', function (Blueprint $table) {
                $table->increments('id');
                $table->string('serial_number')->nullable();
                $table->string('source_kind')->nullable();
                $table->string('source_type')->nullable();
                $table->string('source_id')->nullable();
                $table->string('target_type')->nullable();
                $table->string('target_id')->nullable();
                $table->string('source_endpoint')->nullable();
                $table->dateTime('last_seen_at')->nullable();

                $table->index('serial_number');
                $table->index('source_type');
                $table->index('target_type');
                $table->index('target_id');
                $table->index('last_seen_at');
            });
        }

        if (! $capsule::schema()->hasTable('simplemdm_device_history')) {
            $capsule::schema()->create('simplemdm_device_history', function (Blueprint $table) {
                $table->increments('id');
                $table->string('serial_number');
                $table->date('snapshot_date');
                $table->string('status')->nullable();
                $table->string('os_version')->nullable();
                $table->string('assignment_group')->nullable();
                $table->boolean('is_supervised')->nullable();
                $table->boolean('is_dep_enrollment')->nullable();
                $table->boolean('filevault_enabled')->nullable();
                $table->dateTime('updated_at')->nullable();

                $table->unique(['serial_number', 'snapshot_date']);
                $table->index('snapshot_date');
                $table->index('status');
                $table->index('os_version');
            });
        }

        $defaults = [
            'webhook_secret' => '',
            'compliance_min_os' => '',
            'sync_delta_enabled' => '0',
            'sync_commands_enabled' => '0',
            'last_sync_cursor' => '',
            'sync_last_duration_ms' => '0',
            'sync_last_api_requests' => '0',
            'sync_last_api_errors' => '0',
            'sync_last_rate_limit_hits' => '0',
            'sync_last_delta_mode' => '0',
            'sync_last_scope' => '',
        ];

        foreach ($defaults as $name => $value) {
            $row = $capsule::table('simplemdm_config')->where('name', $name)->first();
            if (! $row) {
                $capsule::table('simplemdm_config')->insert([
                    'name' => $name,
                    'value' => $value,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down()
    {
        $capsule = new Capsule();
        $capsule::schema()->dropIfExists('simplemdm_device_history');
        $capsule::schema()->dropIfExists('simplemdm_relationship_edge');
        $capsule::schema()->dropIfExists('simplemdm_webhook_event');
        $capsule::schema()->dropIfExists('simplemdm_command');
    }
}

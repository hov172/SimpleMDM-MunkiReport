<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmClientFacts extends Migration
{
    public function up()
    {
        $capsule = new Capsule();

        if (! $capsule::schema()->hasTable('simplemdm_client_fact')) {
            $capsule::schema()->create('simplemdm_client_fact', function (Blueprint $table) {
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->increments('id');
                $table->string('serial_number')->index();
                $table->string('fact_type')->index();
                $table->string('fact_key');
                $table->string('fact_value_string')->nullable();
                $table->integer('fact_value_int')->nullable();
                $table->tinyInteger('fact_value_bool')->nullable();
                $table->text('fact_value_json')->nullable();
                $table->dateTime('reported_at')->index();
                $table->string('source')->default('client_reporter');
                $table->string('client_version')->nullable();
                $table->text('raw_json')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->unique(['serial_number', 'fact_key']);
            });
        }

        if (! $capsule::schema()->hasTable('simplemdm_client_fact_history')) {
            $capsule::schema()->create('simplemdm_client_fact_history', function (Blueprint $table) {
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->increments('id');
                $table->string('serial_number')->index();
                $table->string('fact_type')->index();
                $table->string('fact_key')->index();
                $table->string('fact_value_string')->nullable();
                $table->integer('fact_value_int')->nullable();
                $table->tinyInteger('fact_value_bool')->nullable();
                $table->text('fact_value_json')->nullable();
                $table->dateTime('reported_at')->index();
                $table->string('source')->default('client_reporter');
                $table->string('client_version')->nullable();
                $table->text('raw_json')->nullable();
            });
        }

        $defaults = [
            'client_reporter_enabled' => '0',
            'client_reporter_secret' => '',
            'client_reporter_history_enabled' => '1',
            'client_reporter_max_payload_bytes' => '16384',
            'client_reporter_allowed_fact_keys_json' => json_encode([
                'mdm_profile_present',
                'console_user',
                'uptime_seconds',
                'munki_last_run_result',
                'local_filevault_enabled',
            ]),
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
        $capsule::schema()->dropIfExists('simplemdm_client_fact_history');
        $capsule::schema()->dropIfExists('simplemdm_client_fact');
        $capsule::table('simplemdm_config')->whereIn('name', [
            'client_reporter_enabled',
            'client_reporter_secret',
            'client_reporter_history_enabled',
            'client_reporter_max_payload_bytes',
            'client_reporter_allowed_fact_keys_json',
        ])->delete();
    }
}

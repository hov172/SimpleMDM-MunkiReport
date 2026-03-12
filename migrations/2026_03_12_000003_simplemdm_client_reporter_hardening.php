<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmClientReporterHardening extends Migration
{
    public function up()
    {
        $capsule = new Capsule();

        if (! $capsule::schema()->hasTable('simplemdm_client_reporter_nonce')) {
            $capsule::schema()->create('simplemdm_client_reporter_nonce', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nonce_hash')->unique();
                $table->string('serial_number')->nullable()->index();
                $table->string('request_ip')->nullable()->index();
                $table->dateTime('observed_at')->index();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (! $capsule::schema()->hasTable('simplemdm_client_reporter_token')) {
            $capsule::schema()->create('simplemdm_client_reporter_token', function (Blueprint $table) {
                $table->increments('id');
                $table->string('serial_number')->index();
                $table->string('label')->default('default');
                $table->string('token_hash');
                $table->tinyInteger('enabled')->default(1)->index();
                $table->dateTime('last_used_at')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->unique(['serial_number', 'label']);
            });
        }

        $defaults = [
            'client_reporter_hmac_enabled' => '0',
            'client_reporter_replay_protection_enabled' => '0',
            'client_reporter_per_device_tokens_enabled' => '0',
            'client_reporter_ip_allowlist' => '',
            'client_reporter_proxy_only_enabled' => '0',
            'client_reporter_trusted_proxy_ips' => '',
            'client_reporter_max_time_skew_seconds' => '300',
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
        $capsule::schema()->dropIfExists('simplemdm_client_reporter_token');
        $capsule::schema()->dropIfExists('simplemdm_client_reporter_nonce');
        $capsule::table('simplemdm_config')->whereIn('name', [
            'client_reporter_hmac_enabled',
            'client_reporter_replay_protection_enabled',
            'client_reporter_per_device_tokens_enabled',
            'client_reporter_ip_allowlist',
            'client_reporter_proxy_only_enabled',
            'client_reporter_trusted_proxy_ips',
            'client_reporter_max_time_skew_seconds',
        ])->delete();
    }
}

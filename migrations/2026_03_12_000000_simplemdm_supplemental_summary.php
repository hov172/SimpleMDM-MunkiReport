<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmSupplementalSummary extends Migration
{
    public function up()
    {
        $capsule = new Capsule();

        if (! $capsule::schema()->hasTable('simplemdm_supplemental_summary')) {
            $capsule::schema()->create('simplemdm_supplemental_summary', function (Blueprint $table) {
                $table->increments('id');
                $table->string('serial_number')->unique();
                $table->text('source_modules_json')->nullable();
                $table->dateTime('last_refresh')->nullable()->index();
                $table->string('last_refresh_status')->nullable();
                $table->text('source_freshness_json')->nullable();
                $table->tinyInteger('filevault_present')->nullable()->index();
                $table->tinyInteger('filevault_enabled')->nullable()->index();
                $table->tinyInteger('findmymac_present')->nullable();
                $table->tinyInteger('findmymac_enabled')->nullable();
                $table->tinyInteger('applecare_present')->nullable();
                $table->string('applecare_coverage_end')->nullable()->index();
                $table->string('applecare_coverage_status')->nullable();
                $table->tinyInteger('profile_present')->nullable();
                $table->integer('profile_count')->nullable()->index();
                $table->tinyInteger('managedinstalls_present')->nullable();
                $table->integer('managedinstalls_warning_count')->nullable();
                $table->integer('managedinstalls_error_count')->nullable();
            });
        }

        foreach ([
            'supplemental_enabled' => '1',
            'supplemental_default_stale_after_minutes' => '1440',
            'supplemental_registry_json' => '',
        ] as $name => $value) {
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
        $capsule::schema()->dropIfExists('simplemdm_supplemental_summary');

        $capsule::table('simplemdm_config')
            ->whereIn('name', ['supplemental_enabled', 'supplemental_default_stale_after_minutes', 'supplemental_registry_json'])
            ->delete();
    }
}

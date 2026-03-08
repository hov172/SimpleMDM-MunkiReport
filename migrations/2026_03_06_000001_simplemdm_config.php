<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmConfig extends Migration
{
    public function up()
    {
        $capsule = new Capsule();
        $capsule::schema()->create('simplemdm_config', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
        
        // Populate default settings
        $capsule::table('simplemdm_config')->insert([
            ['name' => 'api_key', 'value' => ''],
            ['name' => 'last_sync_status', 'value' => 'Never'],
            ['name' => 'last_sync_time', 'value' => '']
        ]);
    }

    public function down()
    {
        $capsule = new Capsule();
        $capsule::schema()->dropIfExists('simplemdm_config');
    }
}

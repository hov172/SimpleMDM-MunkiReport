<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmSupplementalRegistryConfig extends Migration
{
    public function up()
    {
        $capsule = new Capsule();
        $row = $capsule::table('simplemdm_config')->where('name', 'supplemental_registry_json')->first();
        if (! $row) {
            $capsule::table('simplemdm_config')->insert([
                'name' => 'supplemental_registry_json',
                'value' => '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down()
    {
        $capsule = new Capsule();
        $capsule::table('simplemdm_config')
            ->where('name', 'supplemental_registry_json')
            ->delete();
    }
}

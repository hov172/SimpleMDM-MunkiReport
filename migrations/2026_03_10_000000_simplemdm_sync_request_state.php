<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmSyncRequestState extends Migration
{
    public function up()
    {
        $defaults = [
            'sync_request_state' => 'idle',
            'sync_requested_at' => '',
            'sync_started_at' => '',
            'sync_request_source' => '',
        ];

        foreach ($defaults as $name => $value) {
            $exists = Capsule::table('simplemdm_config')->where('name', $name)->first();
            if (! $exists) {
                Capsule::table('simplemdm_config')->insert([
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
        Capsule::table('simplemdm_config')
            ->whereIn('name', [
                'sync_request_state',
                'sync_requested_at',
                'sync_started_at',
                'sync_request_source',
            ])
            ->delete();
    }
}

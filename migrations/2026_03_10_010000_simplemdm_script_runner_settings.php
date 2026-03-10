<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmScriptRunnerSettings extends Migration
{
    public function up()
    {
        $defaults = [
            'allow_module_script_execution' => '0',
            'script_runner_munkireport_url' => '',
            'script_runner_python_bin' => '/usr/bin/python3',
            'script_runner_schedule' => '* * * * *',
            'script_runner_log_path' => '/var/log/simplemdm_sync.log',
            'script_runner_max_parent_resources' => '25',
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
                'allow_module_script_execution',
                'script_runner_munkireport_url',
                'script_runner_python_bin',
                'script_runner_schedule',
                'script_runner_log_path',
                'script_runner_max_parent_resources',
            ])
            ->delete();
    }
}

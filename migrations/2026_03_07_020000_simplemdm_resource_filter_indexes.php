<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmResourceFilterIndexes extends Migration
{
    private $tableName = 'simplemdm_resource';

    public function up()
    {
        $capsule = new Capsule();
        if (! $capsule::schema()->hasTable($this->tableName)) {
            return;
        }

        $driver = $capsule::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $capsule::statement(
                "CREATE INDEX IF NOT EXISTS simplemdm_resource_resource_id_index ON {$this->tableName} (resource_id)"
            );
            $capsule::statement(
                "CREATE INDEX IF NOT EXISTS simplemdm_resource_type_id_index ON {$this->tableName} (resource_type, resource_id)"
            );
            return;
        }

        try {
            $capsule::statement(
                "ALTER TABLE {$this->tableName} ADD INDEX simplemdm_resource_resource_id_index (resource_id)"
            );
        } catch (\Throwable $e) {
        }
        try {
            $capsule::statement(
                "ALTER TABLE {$this->tableName} ADD INDEX simplemdm_resource_type_id_index (resource_type, resource_id)"
            );
        } catch (\Throwable $e) {
        }
    }

    public function down()
    {
        $capsule = new Capsule();
        if (! $capsule::schema()->hasTable($this->tableName)) {
            return;
        }

        $driver = $capsule::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $capsule::statement('DROP INDEX IF EXISTS simplemdm_resource_resource_id_index');
            $capsule::statement('DROP INDEX IF EXISTS simplemdm_resource_type_id_index');
            return;
        }

        try {
            $capsule::statement("ALTER TABLE {$this->tableName} DROP INDEX simplemdm_resource_resource_id_index");
        } catch (\Throwable $e) {
        }
        try {
            $capsule::statement("ALTER TABLE {$this->tableName} DROP INDEX simplemdm_resource_type_id_index");
        } catch (\Throwable $e) {
        }
    }
}

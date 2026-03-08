<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmResourcesUniqueEndpoint extends Migration
{
    private $tableName = 'simplemdm_resource';

    public function up()
    {
        $capsule = new Capsule();
        if (! $capsule::schema()->hasTable($this->tableName)) {
            return;
        }
        $driver = $capsule::connection()->getDriverName();

        try {
            $capsule::statement("UPDATE {$this->tableName} SET source_endpoint = '' WHERE source_endpoint IS NULL");
        } catch (\Throwable $e) {
        }

        if ($driver === 'sqlite') {
            $capsule::statement('DROP INDEX IF EXISTS simplemdm_resource_resource_type_resource_id_unique');
            $capsule::statement('DROP INDEX IF EXISTS simplemdm_resource_resource_type_resource_id_source_endpoint_unique');
            $capsule::statement(
                'CREATE UNIQUE INDEX IF NOT EXISTS simplemdm_resource_resource_type_resource_id_source_endpoint_unique '
                . "ON {$this->tableName} (resource_type, resource_id, source_endpoint)"
            );
            return;
        }

        try {
            $capsule::statement("ALTER TABLE {$this->tableName} DROP INDEX simplemdm_resource_resource_type_resource_id_unique");
        } catch (\Throwable $e) {
        }
        try {
            $capsule::statement("ALTER TABLE {$this->tableName} DROP INDEX simplemdm_resource_resource_type_resource_id_source_endpoint_unique");
        } catch (\Throwable $e) {
        }

        $capsule::statement(
            "ALTER TABLE {$this->tableName} ADD UNIQUE INDEX simplemdm_resource_resource_type_resource_id_source_endpoint_unique "
            . '(resource_type, resource_id, source_endpoint)'
        );
    }

    public function down()
    {
        $capsule = new Capsule();
        if (! $capsule::schema()->hasTable($this->tableName)) {
            return;
        }
        $driver = $capsule::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $capsule::statement('DROP INDEX IF EXISTS simplemdm_resource_resource_type_resource_id_source_endpoint_unique');
            $capsule::statement('DROP INDEX IF EXISTS simplemdm_resource_resource_type_resource_id_unique');
            $capsule::statement(
                'CREATE UNIQUE INDEX IF NOT EXISTS simplemdm_resource_resource_type_resource_id_unique '
                . "ON {$this->tableName} (resource_type, resource_id)"
            );
            return;
        }

        try {
            $capsule::statement("ALTER TABLE {$this->tableName} DROP INDEX simplemdm_resource_resource_type_resource_id_source_endpoint_unique");
        } catch (\Throwable $e) {
        }
        try {
            $capsule::statement("ALTER TABLE {$this->tableName} DROP INDEX simplemdm_resource_resource_type_resource_id_unique");
        } catch (\Throwable $e) {
        }
        $capsule::statement(
            "ALTER TABLE {$this->tableName} ADD UNIQUE INDEX simplemdm_resource_resource_type_resource_id_unique "
            . '(resource_type, resource_id)'
        );
    }
}

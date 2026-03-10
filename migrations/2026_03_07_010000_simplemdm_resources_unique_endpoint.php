<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmResourcesUniqueEndpoint extends Migration
{
    private $tableName = 'simplemdm_resource';
    private $legacyUniqueIndex = 'simplemdm_resource_resource_type_resource_id_source_endpoint_unique';
    private $uniqueIndex = 'smdm_res_rtype_rid_src_uq';
    private $legacyBaseUniqueIndex = 'simplemdm_resource_resource_type_resource_id_unique';
    private $baseUniqueIndex = 'smdm_res_rtype_rid_uq';

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
            $capsule::statement("DROP INDEX IF EXISTS {$this->legacyBaseUniqueIndex}");
            $capsule::statement("DROP INDEX IF EXISTS {$this->baseUniqueIndex}");
            $capsule::statement("DROP INDEX IF EXISTS {$this->legacyUniqueIndex}");
            $capsule::statement("DROP INDEX IF EXISTS {$this->uniqueIndex}");
            $capsule::statement(
                "CREATE UNIQUE INDEX IF NOT EXISTS {$this->uniqueIndex} "
                . "ON {$this->tableName} (resource_type, resource_id, source_endpoint)"
            );
            return;
        }

        try {
            $capsule::statement("ALTER TABLE {$this->tableName} DROP INDEX {$this->legacyBaseUniqueIndex}");
        } catch (\Throwable $e) {
        }
        try {
            $capsule::statement("ALTER TABLE {$this->tableName} DROP INDEX {$this->baseUniqueIndex}");
        } catch (\Throwable $e) {
        }
        try {
            $capsule::statement("ALTER TABLE {$this->tableName} DROP INDEX {$this->legacyUniqueIndex}");
        } catch (\Throwable $e) {
        }
        try {
            $capsule::statement("ALTER TABLE {$this->tableName} DROP INDEX {$this->uniqueIndex}");
        } catch (\Throwable $e) {
        }

        $capsule::statement(
            "ALTER TABLE {$this->tableName} ADD UNIQUE INDEX {$this->uniqueIndex} "
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
            $capsule::statement("DROP INDEX IF EXISTS {$this->legacyUniqueIndex}");
            $capsule::statement("DROP INDEX IF EXISTS {$this->uniqueIndex}");
            $capsule::statement("DROP INDEX IF EXISTS {$this->legacyBaseUniqueIndex}");
            $capsule::statement("DROP INDEX IF EXISTS {$this->baseUniqueIndex}");
            $capsule::statement(
                "CREATE UNIQUE INDEX IF NOT EXISTS {$this->baseUniqueIndex} "
                . "ON {$this->tableName} (resource_type, resource_id)"
            );
            return;
        }

        try {
            $capsule::statement("ALTER TABLE {$this->tableName} DROP INDEX {$this->legacyUniqueIndex}");
        } catch (\Throwable $e) {
        }
        try {
            $capsule::statement("ALTER TABLE {$this->tableName} DROP INDEX {$this->uniqueIndex}");
        } catch (\Throwable $e) {
        }
        try {
            $capsule::statement("ALTER TABLE {$this->tableName} DROP INDEX {$this->legacyBaseUniqueIndex}");
        } catch (\Throwable $e) {
        }
        try {
            $capsule::statement("ALTER TABLE {$this->tableName} DROP INDEX {$this->baseUniqueIndex}");
        } catch (\Throwable $e) {
        }
        $capsule::statement(
            "ALTER TABLE {$this->tableName} ADD UNIQUE INDEX {$this->baseUniqueIndex} "
            . '(resource_type, resource_id)'
        );
    }
}

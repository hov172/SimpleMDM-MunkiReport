<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmSupplementalCollationFix extends Migration
{
    private $tables = [
        'simplemdm_supplemental_summary',
        'simplemdm_client_fact',
        'simplemdm_client_fact_history',
        'simplemdm_client_reporter_nonce',
        'simplemdm_client_reporter_token',
    ];

    public function up()
    {
        foreach ($this->tables as $table) {
            if (Capsule::schema()->hasTable($table)) {
                Capsule::statement(sprintf(
                    'ALTER TABLE `%s` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                    $table
                ));
            }
        }
    }

    public function down()
    {
        // No-op: this repair migration only normalizes collations for compatibility
        // with existing SimpleMDM/MunkiReport tables.
    }
}

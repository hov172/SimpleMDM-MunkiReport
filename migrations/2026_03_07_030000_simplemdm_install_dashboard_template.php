<?php

use Illuminate\Database\Migrations\Migration;

class SimplemdmInstallDashboardTemplate extends Migration
{
    /**
     * Copy the module dashboard template into local/dashboards if missing.
     *
     * @return void
     **/
    public function up()
    {
        $source = APP_ROOT . 'local/modules/simplemdm/examples/dashboard.simplemdm.full.yml';
        $target_dir = APP_ROOT . 'local/dashboards';
        $target = $target_dir . '/simplemdm_full.yml';

        if (! is_readable($source)) {
            return;
        }

        if (! is_dir($target_dir)) {
            @mkdir($target_dir, 0755, true);
        }

        // Do not overwrite admin-customized dashboards.
        if (is_file($target)) {
            return;
        }

        @copy($source, $target);
    }

    /**
     * Keep user dashboards intact on rollback.
     *
     * @return void
     **/
    public function down()
    {
        // Intentionally no-op.
    }
}

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmDashboardSnapshots extends Migration
{
    private $tableName = 'simplemdm_dashboard_snapshot';

    public function up()
    {
        $capsule = new Capsule();

        if (! $capsule::schema()->hasTable($this->tableName)) {
            $capsule::schema()->create($this->tableName, function (Blueprint $table) {
                $table->increments('id');
                $table->dateTime('snapshot_time');
                $table->integer('device_total')->default(0);
                $table->integer('enrolled_total')->default(0);
                $table->integer('unenrolled_total')->default(0);
                $table->integer('supervised_total')->default(0);
                $table->integer('filevault_enabled_total')->default(0);
                $table->integer('dep_enrolled_total')->default(0);
                $table->integer('resource_total')->default(0);

                $table->index('snapshot_time');
            });
        }
    }

    public function down()
    {
        $capsule = new Capsule();
        $capsule::schema()->dropIfExists($this->tableName);
    }
}

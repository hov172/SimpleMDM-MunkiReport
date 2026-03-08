<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmResources extends Migration
{
    private $tableName = 'simplemdm_resource';

    public function up()
    {
        $capsule = new Capsule();

        if (! $capsule::schema()->hasTable($this->tableName)) {
            $capsule::schema()->create($this->tableName, function (Blueprint $table) {
                $table->increments('id');
                $table->string('resource_type');
                $table->string('resource_id');
                $table->string('source_endpoint')->nullable();
                $table->string('name')->nullable();
                $table->text('attributes_json')->nullable();
                $table->text('relationships_json')->nullable();
                $table->text('data_json')->nullable();
                $table->string('synced_at')->nullable();

                $table->unique(['resource_type', 'resource_id']);
                $table->index('resource_type');
                $table->index('source_endpoint');
            });
        }
    }

    public function down()
    {
        $capsule = new Capsule();
        $capsule::schema()->dropIfExists($this->tableName);
    }
}

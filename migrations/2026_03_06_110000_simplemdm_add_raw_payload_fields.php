<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmAddRawPayloadFields extends Migration
{
    private $tableName = 'simplemdm';

    public function up()
    {
        $capsule = new Capsule();

        if (! $capsule::schema()->hasColumn($this->tableName, 'attributes_json')) {
            $capsule::schema()->table($this->tableName, function (Blueprint $table) {
                $table->text('attributes_json')->nullable();
            });
        }

        if (! $capsule::schema()->hasColumn($this->tableName, 'relationships_json')) {
            $capsule::schema()->table($this->tableName, function (Blueprint $table) {
                $table->text('relationships_json')->nullable();
            });
        }
    }

    public function down()
    {
        $capsule = new Capsule();

        $capsule::schema()->table($this->tableName, function (Blueprint $table) {
            if (Capsule::schema()->hasColumn($this->tableName, 'attributes_json')) {
                $table->dropColumn('attributes_json');
            }
            if (Capsule::schema()->hasColumn($this->tableName, 'relationships_json')) {
                $table->dropColumn('relationships_json');
            }
        });
    }
}

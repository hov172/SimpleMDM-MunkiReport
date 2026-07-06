<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmMcpFinding extends Migration
{
    public function up()
    {
        $capsule = new Capsule();
        $capsule::schema()->create('simplemdm_mcp_finding', function (Blueprint $table) {
            $table->increments('id');
            $table->string('serial_number')->nullable()->index();
            $table->string('source')->index();
            $table->string('finding_type')->index();
            $table->string('severity')->index();
            $table->text('message');
            $table->text('data')->nullable();
            $table->string('reported_at');
        });
    }

    public function down()
    {
        $capsule = new Capsule();
        $capsule::schema()->dropIfExists('simplemdm_mcp_finding');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUniverseSystemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('universe_systems', function (Blueprint $table) {

            $table->bigInteger('system_id')->primary();
            $table->bigInteger('constellation_id');
            $table->string('name');
            $table->string('security_class')->nullable();
            $table->double('security_status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('universe_systems');
    }
}

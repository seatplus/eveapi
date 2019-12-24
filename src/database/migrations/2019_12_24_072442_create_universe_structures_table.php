<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUniverseStructuresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('universe_structures', function (Blueprint $table) {

            $table->bigInteger('structure_id')->primary();
            $table->string('name');
            $table->bigInteger('owner_id');
            $table->bigInteger('solar_system_id');
            $table->bigInteger('type_id')->nullable();

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
        Schema::dropIfExists('universe_structures');
    }
}

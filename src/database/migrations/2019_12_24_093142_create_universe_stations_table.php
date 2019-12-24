<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUniverseStationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('universe_stations', function (Blueprint $table) {

            $table->bigInteger('station_id')->primary();
            $table->string('name');
            $table->double('max_dockable_ship_volume');
            $table->double('office_rental_cost');
            $table->bigInteger('owner_id')->nullable();
            $table->bigInteger('race_id')->nullable();
            $table->bigInteger('system_id');
            $table->bigInteger('type_id');
            $table->double('reprocessing_efficiency');
            $table->double('reprocessing_stations_take');

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
        Schema::dropIfExists('universe_stations');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCorporationInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('corporation_infos', function (Blueprint $table) {
            $table->bigIncrements('corporation_id'); //req
            $table->string('ticker'); //req
            $table->string('name'); //req
            $table->bigInteger('member_count'); //req
            $table->bigInteger('ceo_id'); //req
            $table->bigInteger('creator_id'); //req
            $table->float('tax_rate',10,2); //req

            $table->bigInteger('alliance_id')->nullable();
            $table->dateTime('date_founded')->nullable();
            $table->string('description')->nullable();
            $table->bigInteger('faction_id')->nullable();
            $table->bigInteger('home_station_id')->nullable();
            $table->bigInteger('shares')->nullable();
            $table->string('url')->nullable();
            $table->boolean('war_eligible')->nullable();
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
        Schema::dropIfExists('corporation_infos');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllianceInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('alliance_infos', function (Blueprint $table) {
            $table->bigInteger('alliance_id')->primary();

            $table->bigInteger('creator_corporation_id');
            $table->bigInteger('creator_id');
            $table->date('date_founded');
            $table->bigInteger('executor_corporation_id')->nullable();
            $table->bigInteger('faction_id')->nullable();
            $table->string('name');
            $table->string('ticker');

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
        Schema::dropIfExists('alliance_infos');
    }
}

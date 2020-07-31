<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorporationMemberTrackingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('corporation_member_trackings', function (Blueprint $table) {

            $table->bigInteger('corporation_id');
            $table->bigInteger('character_id');
            $table->dateTime('start_date')->nullable();
            $table->bigInteger('base_id')->nullable();
            $table->dateTime('logon_date')->nullable();
            $table->dateTime('logoff_date')->nullable();
            $table->bigInteger('location_id')->nullable();
            $table->bigInteger('ship_type_id')->nullable();

            $table->index('corporation_id');
            $table->index('character_id');
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
        Schema::dropIfExists('corporation_member_trackings');
    }
}

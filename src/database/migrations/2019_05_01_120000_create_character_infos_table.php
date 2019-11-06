<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCharacterInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('character_infos', function (Blueprint $table) {

            $table->bigInteger('character_id')->primary();

            $table->string('name');
            $table->text('description')->nullable();
            $table->bigInteger('corporation_id');
            $table->bigInteger('alliance_id')->nullable();
            $table->string('birthday');
            $table->string('gender');
            $table->integer('race_id');
            $table->integer('bloodline_id');
            $table->integer('ancestry_id')->nullable();
            $table->float('security_status')->nullable();
            $table->integer('faction_id')->nullable();
            $table->integer('title')->nullable();

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

        Schema::dropIfExists('character_infos');
    }
}

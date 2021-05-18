<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateKillmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('killmails', function (Blueprint $table) {
            $table->foreignId('victim_character_id')->nullable()->change();
            $table->foreignId('victim_corporation_id')->nullable()->change();

            $table->after('victim_corporation_id', function(Blueprint $table) {
                $table->foreignId('victim_alliance_id')->nullable();
                $table->foreignId('victim_faction_id')->nullable();

                $table->unsignedBigInteger('damage_taken')->default(0);
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

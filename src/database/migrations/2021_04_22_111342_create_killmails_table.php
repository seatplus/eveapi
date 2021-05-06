<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKillmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('universe_systems', function (Blueprint $table) {
            $table->unsignedBigInteger('system_id')->change();
        });

        Schema::table('character_infos', function (Blueprint $table) {
            $table->unsignedBigInteger('character_id')->change();
        });

        Schema::table('universe_types', function (Blueprint $table) {
            $table->unsignedBigInteger('type_id')->change();
        });

        Schema::create('killmails', function (Blueprint $table) {
            $table->unsignedBigInteger('killmail_id')->primary();
            $table->string('killmail_hash');
            $table->foreignId('solar_system_id');
            $table->foreignId('victim_character_id');
            $table->foreignId('victim_corporation_id');
            $table->foreignId('ship_type_id');
            $table->boolean('complete')->default(false);
            $table->timestamps();

            $table->unique(['killmail_id', 'killmail_hash']);
            $table->index('killmail_hash');
        });

        Schema::create('killmail_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('location_id');
            $table->string('location_flag');
            $table->unsignedBigInteger('quantity');
            $table->foreignId('type_id');
            $table->double('price')->default(0.0);
            $table->boolean('singleton')->default(false);
            $table->boolean('dropped')->default(false);
            $table->boolean('destroyed')->default(false);
            $table->timestamps();

            $table->index('location_id');
        });

        Schema::table('alliance_infos', function (Blueprint $table) {
            $table->unsignedBigInteger('alliance_id')->change();
        });

        Schema::create('killmail_attackers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('killmail_id')->constrained('killmails', 'killmail_id');
            $table->foreignId('character_id');
            $table->foreignId('corporation_id');
            $table->foreignId('alliance_id')->nullable();
            $table->foreignId('ship_type_id')->nullable();
            $table->foreignId('weapon_type_id');
            $table->unsignedBigInteger('damage_done');
            $table->boolean('final_blow')->default(false);
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
        Schema::dropIfExists('killmail_attackers');
        Schema::dropIfExists('killmail_items');
        Schema::dropIfExists('killmails');
    }
}

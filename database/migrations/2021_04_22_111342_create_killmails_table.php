<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
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
};

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
        Schema::create('contracts', function (Blueprint $table) {
            $table->bigIncrements('contract_id');

            // always available
            $table->unsignedBigInteger('acceptor_id');
            $table->unsignedBigInteger('assignee_id');
            $table->string('availability');
            $table->dateTime('date_expired');
            $table->dateTime('date_issued');
            $table->boolean('for_corporation');
            $table->unsignedBigInteger('issuer_corporation_id');
            $table->unsignedBigInteger('issuer_id');
            $table->string('status');
            $table->string('type');

            // optional
            $table->double('buyout')->nullable();
            $table->double('collateral')->nullable();
            $table->dateTime('date_accepted')->nullable();
            $table->dateTime('date_completed')->nullable();
            $table->unsignedBigInteger('days_to_complete')->nullable();
            $table->double('price')->nullable();
            $table->double('reward')->nullable();
            $table->unsignedBigInteger('end_location_id')->nullable();
            $table->unsignedBigInteger('start_location_id')->nullable();
            $table->string('title')->nullable();
            $table->double('volume')->nullable();

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
        Schema::dropIfExists('contracts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractsTable extends Migration
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
}

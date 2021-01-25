<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWalletJournalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet_journals', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->morphs('wallet_journable');

            $table->dateTime('date');
            $table->text('description');
            $table->string('ref_type');

            $table->double('amount')->nullable();
            $table->double('balance')->nullable();
            $table->nullableMorphs('contextable');
            $table->bigInteger('first_party_id')->nullable();
            $table->bigInteger('second_party_id')->nullable();
            $table->text('reason')->nullable();
            $table->double('tax')->nullable();
            $table->bigInteger('tax_receiver_id')->nullable();

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
        Schema::dropIfExists('wallet_journals');
    }
}

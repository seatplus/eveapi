<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWalletTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->bigIncrements('transaction_id');

            $table->morphs('wallet_transactionable', 'morph');

            $table->bigInteger('client_id');
            $table->dateTime('date');
            $table->boolean('is_buy');
            $table->boolean('is_personal');
            $table->bigInteger('journal_ref_id');
            $table->bigInteger('location_id');
            $table->bigInteger('quantity');
            $table->bigInteger('type_id');
            $table->double('unit_price');

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
        Schema::dropIfExists('wallet_transactions');
    }
}

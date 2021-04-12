<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorporationWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('corporation_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corporation_id')->constrained('corporation_infos', 'corporation_id');
            $table->integer('division');
            $table->double('balance');
            $table->timestamps();

            $table->unique(['corporation_id', 'division']);
        });

        Schema::table('wallet_journals', function (Blueprint $table) {
            $table->after('wallet_journable_id', function ($table) {
                $table->integer('division')->nullable();
            });
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->after('wallet_transactionable_id', function ($table) {
                $table->integer('division')->nullable();
            });
        });
    }
}

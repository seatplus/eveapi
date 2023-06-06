<?php

namespace database\migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('wallet_journals', function (Blueprint $table) {
            $table->index('wallet_journable_id');
            $table->index('date');
            $table->index(['wallet_journable_id', 'date']);
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->index('wallet_transactionable_id');
            $table->index('date');
            $table->index(['wallet_transactionable_id', 'date']);
        });
    }
};

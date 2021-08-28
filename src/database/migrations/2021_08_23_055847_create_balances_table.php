<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('balances', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->morphs('balanceable');
            $table->double('balance');

            $table->integer('division')->nullable();

            $table->timestamps();
        });

        Schema::dropIfExists('corporation_wallets');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_items', function (Blueprint $table) {
            $table->bigIncrements('record_id');

            // always available
            $table->foreignId('contract_id')->constrained('contracts', 'contract_id');
            $table->boolean('is_included');
            $table->boolean('is_singleton');
            $table->unsignedBigInteger('quantity');

            $table->unsignedBigInteger('type_id');

            // optional
            $table->bigInteger('raw_quantity')->nullable();

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
        Schema::dropIfExists('contract_items');
    }
}

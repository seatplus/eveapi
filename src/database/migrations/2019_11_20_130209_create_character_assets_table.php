<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCharacterAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('character_assets', function (Blueprint $table) {

            $table->bigInteger('item_id')->primary();
            $table->bigInteger('character_id');
            $table->boolean('is_blueprint_copy')->default(false);
            $table->boolean('is_singleton');
            $table->string('location_flag');
            $table->bigInteger('location_id');
            $table->enum('location_type',['station', 'solar_system', 'other']);
            $table->integer('quantity');
            $table->integer('type_id');
            $table->string('name')->nullable();

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
        Schema::dropIfExists('character_assets');
    }
}

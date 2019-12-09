<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUniverseTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('universe_types', function (Blueprint $table) {

            $table->bigInteger('type_id')->primary();
            $table->bigInteger('group_id');
            $table->string('name');
            $table->longText('description');
            $table->boolean('published');

            $table->double('capacity')->nullable();
            $table->bigInteger('graphic_id')->nullable();
            $table->bigInteger('icon_id')->nullable();
            $table->bigInteger('market_group_id')->nullable();
            $table->double('mass')->nullable();
            $table->double('packaged_volume')->nullable();
            $table->bigInteger('portion_size')->nullable();
            $table->double('radius')->nullable();
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
        Schema::dropIfExists('universe_types');
    }
}

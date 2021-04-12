<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorporationDivisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('corporation_divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corporation_id')->constrained('corporation_infos', 'corporation_id');
            $table->string('division_type');
            $table->integer('division_id');
            $table->text('name');
            $table->timestamps();

            $table->unique(['corporation_id', 'division_type', 'division_id'], 'unique_corporation_division');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('corporation_divisions');
    }
}

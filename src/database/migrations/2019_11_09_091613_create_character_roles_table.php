<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCharacterRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('character_roles', function (Blueprint $table) {

            $table->bigInteger('character_id')->primary();
            $table->json('roles')->nullable();
            $table->json('roles_at_base')->nullable();
            $table->json('roles_at_hq')->nullable();
            $table->json('roles_at_other')->nullable();

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
        Schema::dropIfExists('character_roles');
    }
}

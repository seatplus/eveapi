<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('location_refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id')->index();
            $table->unsignedBigInteger('character_id');
            $table->boolean('resolved')->default(false);
            $table->integer('attempts')->default(0);
            $table->timestamps();
        });
    }
};

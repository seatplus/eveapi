<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailLabelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_labels', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('label_id');
            $table->bigInteger('character_id');
            $table->string('color');
            $table->string('name');
            $table->integer('unread_count')->nullable();

            $table->timestamps();

            $table->unique(['label_id', 'character_id']);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mail_labels');
    }
}

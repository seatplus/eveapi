<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailMailLabelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_mail_label', function (Blueprint $table) {
            $table->bigInteger('label_id');
            $table->bigInteger('mail_id');
            $table->bigInteger('character_id');
            $table->timestamps();

            $table->index('label_id');
            $table->index('mail_id');
            $table->index('character_id');
            $table->unique(['mail_id', 'label_id', 'character_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mail_mail_label');
    }
}

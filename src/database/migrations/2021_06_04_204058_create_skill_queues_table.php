<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSkillQueuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('skill_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('character_infos', 'character_id');
            $table->unsignedInteger('finished_level');
            $table->unsignedInteger('queue_position');
            $table->unsignedBigInteger('skill_id');

            $table->dateTime('finish_date')->nullable();
            $table->unsignedBigInteger('level_end_sp')->nullable();
            $table->unsignedBigInteger('level_start_sp')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->unsignedBigInteger('training_start_sp')->nullable();
            $table->timestamps();

            $table->unique(['character_id', 'skill_id', 'queue_position', 'finished_level'], 'unique_skill_queue');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('skill_queues');
    }
}

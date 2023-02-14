<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {

        Schema::create('batch_statistics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('batch_id')->index();
            $table->string('batch_name');
            $table->integer('total_jobs');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('queue_balancing_configuration')->index();

            $table->timestamps();
        });
    }
};

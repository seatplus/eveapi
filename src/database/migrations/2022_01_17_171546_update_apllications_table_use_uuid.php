<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up()
    {
        Schema::table('application_logs', function (Blueprint $table) {
            $table->dropForeign(['application_id']);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->uuid('id')->change();
        });

        Schema::table('application_logs', function (Blueprint $table) {
            $table->foreignUuid('application_id')->change();

            $table->foreign('application_id')->references('id')->on('applications');
        });

    }
};
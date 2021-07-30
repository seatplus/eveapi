<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('corporation_member_trackings', function (Blueprint $table) {
            if(!Schema::hasColumn('corporation_member_trackings', 'id')) {
                $table->id()->first();
            }
        });
    }
};
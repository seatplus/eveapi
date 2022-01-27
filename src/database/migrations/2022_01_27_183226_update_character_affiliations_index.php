<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::table('character_affiliations', function (Blueprint $table) {
            $table->index('alliance_id');
            $table->index('corporation_id');
            $table->index('faction_id');
            $table->index('character_id');
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corporation_infos', function (Blueprint $table) {
            // seatplus/auth's AffiliationResolver joins corporation_infos on alliance_id to expand an
            // alliance affiliation to its member corporations; the column was unindexed.
            $table->index('alliance_id');
        });
    }
};

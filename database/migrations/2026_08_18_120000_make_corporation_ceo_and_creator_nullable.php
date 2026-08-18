<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corporation_infos', function (Blueprint $table) {
            // ESI 2026-08-04 made ceo_id and creator_id optional on /corporations/{id}/ — a closed or
            // npc_owned corporation reports neither. Until esi-schema 5.0 the DTO coerced them with
            // `(int) ($data->ceo_id ?? 0)`, so a NOT NULL column could never be violated; they are now
            // `?int` and pass NULL straight through.
            //
            // Left NOT NULL, the first such corporation aborts the INSERT with SQLSTATE 23502. That
            // leaves the row absent, and CorporationInfoJob is only ever dispatched for corporations
            // whose row is missing (CharacterAffiliationJob::followUp() gates on
            // whereDoesntHave('corporation')), so it is re-dispatched on every affiliation pass and
            // burns ESI quota indefinitely rather than failing once.
            $table->bigInteger('ceo_id')->nullable()->change();
            $table->bigInteger('creator_id')->nullable()->change();
        });
    }
};

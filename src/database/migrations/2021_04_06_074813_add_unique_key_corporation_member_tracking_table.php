<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueKeyCorporationMemberTrackingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('corporation_member_trackings', function (Blueprint $table) {
            $table->unique(['corporation_id', 'character_id']);
        });
    }


}

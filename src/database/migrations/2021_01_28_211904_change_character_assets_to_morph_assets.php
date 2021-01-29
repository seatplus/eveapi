<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class ChangeCharacterAssetsToMorphAssets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('character_assets', function (Blueprint $table) {
            $table->string('assetable_type', 255)->after('character_id')->default(CharacterInfo::class);
        });

        Schema::table('character_assets', function (Blueprint $table) {
            $table->renameColumn('character_id', 'assetable_id');
            $table->string('assetable_type', 255)->change();
        });

        Schema::table('character_assets', function (Blueprint $table) {
            $table->index(['assetable_id', 'assetable_type']);
        });

        Schema::rename('character_assets', 'assets');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('character_assets', function (Blueprint $table) {
            //
        });
    }
}

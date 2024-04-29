<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Seatplus\Eveapi\Jobs\Assets\EnrichAssetTypeGroupCategoryJob;
use Seatplus\Eveapi\Jobs\Assets\UpdateAssetSystemRegionJob;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {

            // add solar_system_id and region_id columns after location_id column
            $table->after('location_id', function (Blueprint $table) {
                $table->integer('solar_system_id')->nullable()->index();
                $table->integer('region_id')->nullable()->index();
            });

            // add new columns after name_normalized column
            $table->after('name_normalized', function (Blueprint $table) {

                // type first
                $table->string('type_name_normalized')->index()->nullable();

                // group second
                $table->integer('group_id')->nullable();
                $table->string('group_name_normalized')->index()->nullable();

                // category third
                $table->integer('category_id')->nullable();
                $table->string('category_name_normalized')->index()->nullable();

            });

        });

        Schema::table('universe_categories', function (Blueprint $table) {
            $table->string('name_normalized')->virtualAs("regexp_replace(name, '[^A-Za-z0-9]', '')")->index();
        });

        EnrichAssetTypeGroupCategoryJob::dispatch()->onQueue('high');
        UpdateAssetSystemRegionJob::dispatch()->onQueue('high');
    }
};

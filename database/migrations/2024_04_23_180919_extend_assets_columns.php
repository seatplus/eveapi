<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\EnrichAssetTypeGroupCategoryJob;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {

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
            $table->string('name_normalized')->storedAs("regexp_replace(name, '[^A-Za-z0-9]', '', 'g')")->index();
        });

        EnrichAssetTypeGroupCategoryJob::dispatch()->onQueue('high');
    }
};

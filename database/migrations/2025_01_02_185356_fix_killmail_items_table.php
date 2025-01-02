<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::table('killmail_items', function (Blueprint $table) {
            $table->integer('singleton_temp')->default(0);
        });

        DB::table('killmail_items')->where('singleton', true)->update(['singleton_temp' => 1]);

        Schema::table('killmail_items', function (Blueprint $table) {
            $table->integer('singleton')->default(0)->change();
        });

        DB::table('killmail_items')->where('singleton_temp', 1)->update(['singleton' => 1]);

        Schema::table('killmail_items', function (Blueprint $table) {
            $table->dropColumn('singleton_temp');
        });

    }
};

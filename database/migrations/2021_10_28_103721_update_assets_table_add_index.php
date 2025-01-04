<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->index('item_id');
            $table->string('name_normalized')->storedAs("regexp_replace(COALESCE(name, ''), '[^A-Za-z0-9]', '', 'g')")->index();
        });
    }
};

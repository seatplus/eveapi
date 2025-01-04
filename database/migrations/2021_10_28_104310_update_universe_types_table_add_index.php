<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('universe_types', function (Blueprint $table) {
            $table->string('name_normalized')->storedAs("regexp_replace(name, '[^A-Za-z0-9]', '', 'g')")->index();
            $table->index(['group_id', 'type_id']);
        });
    }
};

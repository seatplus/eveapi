<?php

use Illuminate\Database\Migrations\Migration;
use Seatplus\Eveapi\Services\MigrateDb;

return new class extends Migration
{
    public function up(): void
    {
        // skip migration if in testing or local environment
        if (app()->environment('testing') || app()->environment('local')) {
            return;
        }

        new MigrateDb;
    }
};

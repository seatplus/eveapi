<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // skip migration if in testing or local environment
        if (app()->environment('testing') || app()->environment('local')) {
            return;
        }

        new \Seatplus\Eveapi\Services\MigrateDb;
    }
};

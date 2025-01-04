<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // skip migration if in testing environment
        if (app()->environment('testing')) {
            return;
        }

        new \Seatplus\Eveapi\Services\MigrateDb;
    }
};
